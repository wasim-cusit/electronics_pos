<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'add_sale';

// Get the next sale invoice number
function get_next_sale_invoice_no($pdo)
{
    $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM sale");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $next = ($row && $row['max_id']) ? $row['max_id'] + 1 : 1;
    return 'SALE-' . str_pad($next, 3, '0', STR_PAD_LEFT);
}

// Function to check if product has sufficient stock
function check_product_stock($pdo, $product_id, $quantity)
{
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(si.quantity), 0) as available_stock 
                           FROM stock_items si 
                           WHERE si.product_id = ? AND si.status = 'available'");
    $stmt->execute([$product_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result['available_stock'] >= $quantity;
}

// Function to get available stock items for a product
function get_available_stock_items($pdo, $product_id, $quantity)
{
    $stmt = $pdo->prepare("SELECT si.id, si.quantity, si.purchase_price, si.sale_price, si.product_code
                           FROM stock_items si 
                           WHERE si.product_id = ? AND si.status = 'available' AND si.quantity > 0
                           ORDER BY si.stock_date ASC, si.id ASC");
    $stmt->execute([$product_id]);
    $stock_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $allocated_items = [];
    $remaining_quantity = $quantity;

    // First, allocate from available stock
    foreach ($stock_items as $item) {
        if ($remaining_quantity <= 0) break;

        $allocated_qty = min($item['quantity'], $remaining_quantity);
        $allocated_items[] = [
            'stock_item_id' => $item['id'],
            'quantity' => $allocated_qty,
            'purchase_price' => $item['purchase_price'],
            'sale_price' => $item['sale_price'],
            'product_code' => $item['product_code']
        ];

        $remaining_quantity -= $allocated_qty;
    }

    // If there's still remaining quantity, handle backorder scenario
    if ($remaining_quantity > 0) {
        // Get the first available stock item to use as base for backorder
        $base_stock_item = $stock_items[0] ?? null;
        if ($base_stock_item) {
            // Add backorder entry (negative stock)
            $allocated_items[] = [
                'stock_item_id' => $base_stock_item['id'],
                'quantity' => $remaining_quantity,
                'purchase_price' => $base_stock_item['purchase_price'],
                'sale_price' => $base_stock_item['sale_price'],
                'product_code' => $base_stock_item['product_code']
            ];
        }
    }

    return $allocated_items;
}

// Handle Add Sale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_sale'])) {
    $customer_id = $_POST['customer_id'];
    $walk_in_cust_name = $_POST['walk_in_cust_name'];
    $invoice_no = get_next_sale_invoice_no($pdo);
    $sale_date = $_POST['sale_date'];

    // Calculate subtotal from sale items
    $subtotal = 0;
    if (isset($_POST['total_price']) && is_array($_POST['total_price'])) {
        foreach ($_POST['total_price'] as $total_price) {
            if (!empty($total_price) && is_numeric($total_price)) {
                $subtotal += floatval($total_price);
            }
        }
    }

    $discount = floatval($_POST['discount'] ?? 0);
    $total_amount = floatval($_POST['total_amount']);
    $paid_amount = floatval($_POST['paid_amount'] ?? 0);
    $due_amount = floatval($_POST['due_amount'] ?? 0);
    $payment_method_id = $_POST['payment_method_id'] ?? null;
    $notes = $_POST['notes'] ?? '';
    $created_by = $_SESSION['user_id'];

    // If walk-in customer is selected, use walk_in_cust_name
    if ($customer_id === 'walk_in') {
        if (empty(trim($walk_in_cust_name))) {
            $error = "Walk-in customer name is required when selecting walk-in customer.";
        } else {
            $customer_id = null; // Use null for walk-in customers
        }
    }

    // Validate stock availability before proceeding
    $stock_errors = [];
    foreach ($product_ids as $i => $product_id) {
        if (!empty($product_id)) {
            $available_stock = check_product_stock($pdo, $product_id, $quantities[$i]);
            if (!$available_stock) {
                $stmt = $pdo->prepare("SELECT product_name FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                $stock_errors[] = "Insufficient stock for " . ($product['product_name'] ?? 'Product ID: ' . $product_id) . " (Requested: {$quantities[$i]})";
            }
        }
    }
    
    if (!empty($stock_errors)) {
        $error = "Stock validation failed: " . implode(", ", $stock_errors);
        header("Location: add_sale.php?error=" . urlencode($error));
        exit;
    }

    // If no error, proceed with the sale using transaction
    if (!isset($error)) {
        try {
            $pdo->beginTransaction();

            $after_discount = $subtotal - $discount;
            $stmt = $pdo->prepare("INSERT INTO sale (customer_id, walk_in_cust_name, sale_no, sale_date, subtotal, discount, after_discount, total_amount, paid_amount, due_amount, payment_method_id, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$customer_id, $walk_in_cust_name, $invoice_no, $sale_date, $subtotal, $discount, $after_discount, $total_amount, $paid_amount, $due_amount, $payment_method_id, $notes, $created_by]);
            $sale_id = $pdo->lastInsertId();

            // Handle sale items
            $product_ids = $_POST['product_id'];
            $quantities = $_POST['quantity'];
            $colors = $_POST['color'] ?? [];
            $custom_colors = $_POST['custom_color'] ?? [];
            $purchase_prices = $_POST['purchase_price'];
            $unit_prices = $_POST['unit_price'];
            $total_prices = $_POST['total_price'];

            for ($i = 0; $i < count($product_ids); $i++) {
                if (!empty($product_ids[$i])) {
                    // Get available stock items for this product
                    $stock_items = get_available_stock_items($pdo, $product_ids[$i], $quantities[$i]);

                    if ($stock_items) {
                        // Get product details and category
                        $stmt = $pdo->prepare("SELECT p.product_name, c.category 
                                              FROM products p 
                                              LEFT JOIN categories c ON p.category_id = c.id 
                                              WHERE p.id = ?");
                        $stmt->execute([$product_ids[$i]]);
                        $product = $stmt->fetch(PDO::FETCH_ASSOC);
                        $category_name = $product && $product['category'] ? $product['category'] : '';

                        // Get color information
                        $color = '';
                        if (isset($colors[$i]) && $colors[$i] === 'custom') {
                            $color = $custom_colors[$i] ?? '';
                        } elseif (isset($colors[$i]) && $colors[$i] !== '') {
                            $color = $colors[$i];
                        }

                        // Create notes with color information
                        $notes = $color ? "Color: " . $color : '';

                        // Insert sale item
                        $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, warehouse_id, product_code, price, stock_qty, quantity, total_price, category_name, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$sale_id, $product_ids[$i], 0, $stock_items[0]['product_code'], $unit_prices[$i], $quantities[$i], $quantities[$i], $total_prices[$i], $category_name, $notes]);

                        // Update stock items
                        foreach ($stock_items as $stock_item) {
                            $stmt = $pdo->prepare("UPDATE stock_items SET quantity = quantity - ? WHERE id = ?");
                            $stmt->execute([$stock_item['quantity'], $stock_item['stock_item_id']]);

                            // Check if stock becomes 0 or negative
                            $stmt = $pdo->prepare("SELECT quantity FROM stock_items WHERE id = ?");
                            $stmt->execute([$stock_item['stock_item_id']]);
                            $current_qty = $stmt->fetchColumn();
                            
                            if ($current_qty <= 0) {
                                // Mark as sold if quantity is 0 or negative
                                $stmt = $pdo->prepare("UPDATE stock_items SET status = 'sold' WHERE id = ?");
                                $stmt->execute([$stock_item['stock_item_id']]);
                            }
                        }

                        // Check for low stock and create notification if needed
                        $stmt = $pdo->prepare("SELECT p.product_name, p.alert_quantity, COALESCE(SUM(si.quantity), 0) as current_stock 
                                              FROM products p 
                                              LEFT JOIN stock_items si ON p.id = si.product_id AND si.status = 'available' 
                                              WHERE p.id = ? 
                                              GROUP BY p.id");
                        $stmt->execute([$product_ids[$i]]);
                        $product = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($product && $product['alert_quantity'] > 0 && $product['current_stock'] <= $product['alert_quantity']) {
                            $msg = 'Low stock alert: ' . $product['product_name'] . ' stock is ' . $product['current_stock'] . ' (threshold: ' . $product['alert_quantity'] . ')';

                            // Prevent duplicate unread notifications for this product and user
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND type = 'Low Stock' AND message = ? AND is_read = 0");
                            $stmt->execute([$created_by, $msg]);
                            $exists = $stmt->fetchColumn();

                            if (!$exists) {
                                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'Low Stock', ?)");
                                $stmt->execute([$created_by, $msg]);
                            }
                        }
                    } else {
                        throw new Exception("Failed to allocate stock for product ID: " . $product_ids[$i]);
                    }
                }
            }

            $pdo->commit();
            header("Location: add_sale.php?success=added&sale_id=" . $sale_id);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Error in add_sale: " . $e->getMessage());
            $error = "An error occurred while processing the sale. Please try again.";
            header("Location: add_sale.php?error=" . urlencode($error));
            exit;
        }
    } else {
        // If there was an error, redirect back to the form with error message
        header("Location: add_sale.php?error=" . urlencode($error));
        exit;
    }
}

// Fetch customers and products for dropdowns
$customers = $pdo->query("SELECT * FROM customer ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query("SELECT p.*, COALESCE(SUM(si.quantity), 0) as stock_quantity, ROUND(COALESCE(AVG(si.sale_price), 0), 2) as sale_price, ROUND(COALESCE(AVG(si.purchase_price), 0), 2) as purchase_price FROM products p LEFT JOIN stock_items si ON p.id = si.product_id AND si.status = 'available' GROUP BY p.id ORDER BY p.product_name")->fetchAll(PDO::FETCH_ASSOC);
$payment_methods = $pdo->query("SELECT * FROM payment_method WHERE status = 1 ORDER BY method")->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="bi bi-cart-plus text-primary"></i> Add New Sale</h2>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php
                    if ($_GET['success'] === 'added') echo "Sale added successfully! <a href='print_invoice.php?id=" . $_GET['sale_id'] . "' target='_blank'>Print Invoice</a>";
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>

            <!-- Add Sale Form -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-cart-plus"></i> Create New Sale</h5>
                </div>
                <div class="card-body">
                    <form method="post" id="saleForm">
                        <!-- Customer Information Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="bi bi-person-circle"></i> Customer Information
                                </h6>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-bold">Customer <span class="text-danger">*</span></label>
                                <div class="customer-dropdown-container">
                                    <button type="button" class="customer-dropdown-btn" id="customerDropdownBtn">
                                        <span class="customer-selected-text">Select Customer</span>
                                        <i class="bi bi-chevron-down dropdown-arrow"></i>
                                    </button>
                                    <div class="customer-dropdown-list" id="customerDropdownList">
                                        <div class="customer-search-box">
                                            <input type="text" id="customerSearchInput" class="form-control form-control-sm" placeholder="🔍 Search customers...">
                                        </div>
                                        <div class="customer-dropdown-separator"></div>
                                        <div class="customer-option" data-value="walk_in">
                                            🚶 Walk-in Customer
                                        </div>
                                        <?php foreach ($customers as $customer): ?>
                                            <div class="customer-option" data-value="<?= $customer['id'] ?>">
                                                👤 <?= htmlspecialchars($customer['name']) ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="customer_id" id="customerSelect" required>
                                </div>
                            </div>
                            <div class="mb-3" id="walkInCustomerField" style="display: none; width: 18%;">
                                <label class="form-label fw-bold">Walk-in Customer Name <span class="text-danger">*</span></label>
                                <input type="text" name="walk_in_cust_name" class="form-control" placeholder="Enter customer name" required>
                            </div>
                            <div class="mb-3" style="width: 14%;">
                                <label class="form-label fw-bold">Sale Date <span class="text-danger">*</span></label>
                                <input type="date" name="sale_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                            </div>

                            <div class="col-md-3 mb-3" style="margin-top: 30px;">
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                                    <i class="bi bi-person-plus"></i> Add New Customer
                                </button>
                            </div>
                        </div>

                        <!-- Sale Items Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="bi bi-box-seam"></i> Sale Items
                                </h6>
                                <div id="saleItems">
                                    <div class="row mb-3 align-items-end sale-item-row">
                                        <div class="col-md-3">
                                            <label class="form-label small fw-bold">Product <span class="text-danger">*</span></label>
                                            <select name="product_id[]" class="form-select product-select" required>
                                                <option value="">Select Product</option>
                                                <?php foreach ($products as $product): ?>
                                                    <option value="<?= $product['id'] ?>"
                                                        data-unit="<?= htmlspecialchars($product['product_unit']) ?>"
                                                        data-stock="<?= $product['stock_quantity'] ?>"
                                                        data-sale-price="<?= $product['sale_price'] > 0 ? $product['sale_price'] : 0 ?>"
                                                        data-purchase-price="<?= $product['purchase_price'] > 0 ? $product['purchase_price'] : 0 ?>">
                                                        📦 <?= htmlspecialchars($product['product_name']) ?>
                                                        <span class="<?= $product['stock_quantity'] >= 0 ? 'text-muted' : 'text-danger' ?>">(Stock: <?= $product['stock_quantity'] >= 0 ? $product['stock_quantity'] : '0 (Backorder: ' . abs($product['stock_quantity']) . ')' ?>)</span>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div style="width:13%" class="quantity-container">
                                            <label class="form-label small fw-bold">Qty <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" name="quantity[]" class="form-control quantity" placeholder="Qty" required min="0.01">
                                            <!-- Stock indicator will be dynamically added here -->
                                        </div>
                                        <div style="width:13%">
                                            <label class="form-label fw-bold">Color/Names</label>
                                            <select name="color[]" class="form-control color-select" style="height: 38px;">
                                                <option value="">Select Color</option>
                                                <option value="Red">Red</option>
                                                <option value="Blue">Blue</option>
                                                <option value="Green">Green</option>
                                                <option value="Yellow">Yellow</option>
                                                <option value="Black">Black</option>
                                                <option value="White">White</option>
                                                <option value="Purple">Purple</option>
                                                <option value="custom">+ Add Color</option>
                                            </select>
                                            <input type="text" name="custom_color[]" class="form-control custom-color-input mt-1" placeholder="Enter custom color names" style="height: 32px; display: none;">
                                        </div>
                                        <div style="width:11%">
                                            <label class="form-label fw-bold">Purchase Price</label>
                                            <input type="number" step="0.01" name="purchase_price[]" class="form-control purchase-price" placeholder="P.Price" readonly>
                                        </div>
                                        <div style="width:11%">
                                            <label class="form-label fw-bold">Sale Price <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" name="unit_price[]" class="form-control unit-price" placeholder="S.Price" required min="0.01">
                                        </div>
                                        <div style="width:11%">
                                            <label class="form-label fw-bold">Total</label>
                                            <input type="number" step="0.01" name="total_price[]" class="form-control total-price" placeholder="Total" readonly>
                                        </div>
                                        <div class="col-md-1" style="margin-top: 30px;">
                                            <button type="button" class="btn btn-danger btn-sm remove-item" title="Remove Item">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                            <button type="button" class="btn btn-success btn-sm" id="addItem" title="Add Another Item">
                                                <i class="bi bi-plus-circle-fill"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing Summary Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="bi bi-calculator"></i> Pricing Summary
                                </h6>
                            </div>

                            <div class="col-md-2 mb-3">
                                <label class="form-label fw-bold">Discount</label>
                                <div class="input-group">
                                    <span class="input-group-text">PKR</span>
                                    <input type="number" step="0.01" name="discount" id="discount" class="form-control" value="0.00" min="0">
                                </div>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label fw-bold text-success">Final Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">PKR</span>
                                    <input type="number" step="0.01" name="total_amount" id="totalAmount" class="form-control fw-bold" required readonly>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Information Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="bi bi-credit-card"></i> Payment Information
                                </h6>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label fw-bold">Paid Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">PKR</span>
                                    <input type="number" step="0.01" name="paid_amount" id="paidAmount" class="form-control" value="0.00" min="0">
                                </div>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label fw-bold text-warning">Remaining Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">PKR</span>
                                    <input type="number" step="0.01" name="due_amount" id="dueAmount" class="form-control" readonly>
                                </div>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label fw-bold">Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method_id" id="paymentMethod" class="form-select" required>
                                    <option value="">Select Method</option>
                                    <?php foreach ($payment_methods as $method): ?>
                                        <option value="<?= $method['id'] ?>">💳 <?= htmlspecialchars($method['method']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Notes & Details</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Enter payment details, delivery instructions, or other important notes..."></textarea>
                            </div>
                        </div>

                        <!-- Submit Section -->
                        <div class="row">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-primary btn-lg" name="add_sale" onclick="return validateColors()">
                                    <i class="bi bi-check-circle"></i> Create Sale
                                </button>
                                <button type="reset" class="btn btn-secondary btn-lg ms-2">
                                    <i class="bi bi-arrow-clockwise"></i> Reset Form
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Add Customer Modal -->
            <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form id="addCustomerForm">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="addCustomerModalLabel">
                                    <i class="bi bi-person-plus"></i> Add New Customer
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                                            <input type="text" name="name" class="form-control" placeholder="Enter customer full name" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Contact Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                            <input type="text" name="contact" class="form-control" placeholder="Enter contact number">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Email Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                            <input type="email" name="email" class="form-control" placeholder="Enter email address">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                            <input type="text" name="address" class="form-control" placeholder="Enter address">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Opening Balance</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₨</span>
                                            <input type="number" step="0.01" name="opening_balance" class="form-control" placeholder="0.00" value="0.00" min="0">
                                        </div>
                                        <small class="text-muted">Enter any existing balance the customer owes or credit they have</small>
                                    </div>
                                </div>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>Note:</strong> Only the customer name is required. Other fields are optional and can be filled later.
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Add Customer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    document.getElementById('addItem').addEventListener('click', function() {
        const container = document.getElementById('saleItems');
        const newRow = container.children[0].cloneNode(true);

        // Clear all input values in the new row
        newRow.querySelectorAll('input, select').forEach(input => input.value = '');

        // Hide custom color input in new row
        const customColorInput = newRow.querySelector('.custom-color-input');
        if (customColorInput) {
            customColorInput.style.display = 'none';
            customColorInput.required = false;
        }

        // Remove any existing stock indicators - COMMENTED OUT
        /*
        const stockIndicator = newRow.querySelector('.stock-indicator');
        if (stockIndicator) {
            stockIndicator.remove();
        }
        */

        // Ensure the remove button has the correct class and event handling
        const removeBtn = newRow.querySelector('.remove-item');
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                if (document.querySelectorAll('.sale-item-row').length > 1) {
                    this.closest('.sale-item-row').remove();
                    updateTotals(); // Update totals after removing item
                }
            });
        }

        container.appendChild(newRow);
    });

    // Handle remove button clicks for existing and new rows
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-item') || e.target.closest('.remove-item')) {
            const removeBtn = e.target.classList.contains('remove-item') ? e.target : e.target.closest('.remove-item');
            const itemRow = removeBtn.closest('.sale-item-row');

            if (document.querySelectorAll('.sale-item-row').length > 1) {
                // Clear stock indicators before removing - COMMENTED OUT
                /*
                const stockIndicator = itemRow.querySelector('.stock-indicator');
                if (stockIndicator) {
                    stockIndicator.remove();
                }
                */

                itemRow.remove();
                updateTotals(); // Update totals after removing item
            }
        }
    });

    // Initialize customer dropdown functionality
    document.addEventListener('DOMContentLoaded', function() {
        const dropdownBtn = document.getElementById('customerDropdownBtn');
        const dropdownList = document.getElementById('customerDropdownList');
        const customerSelect = document.getElementById('customerSelect');
        const customerSearchInput = document.getElementById('customerSearchInput');
        const selectedText = document.querySelector('.customer-selected-text');

        // Toggle dropdown on click
        dropdownBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownList.classList.toggle('show');
            dropdownBtn.classList.toggle('active');

            if (dropdownList.classList.contains('show')) {
                customerSearchInput.focus();
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!dropdownBtn.contains(e.target) && !dropdownList.contains(e.target)) {
                dropdownList.classList.remove('show');
                dropdownBtn.classList.remove('active');
            }
        });

        // Handle customer option selection
        dropdownList.addEventListener('click', function(e) {
            const customerOption = e.target.closest('.customer-option');
            if (customerOption) {
                const value = customerOption.dataset.value;
                const text = customerOption.textContent;

                // Update hidden input and display text
                customerSelect.value = value;
                selectedText.textContent = text;

                // Update visual selection
                dropdownList.querySelectorAll('.customer-option').forEach(item => {
                    item.classList.remove('selected');
                });
                customerOption.classList.add('selected');

                // Close dropdown
                dropdownList.classList.remove('show');
                dropdownBtn.classList.remove('active');

                // Handle customer selection
                handleCustomerSelection(value);
            }
        });

        // Handle search functionality
        customerSearchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const customerOptions = dropdownList.querySelectorAll('.customer-option');

            customerOptions.forEach(option => {
                const optionText = option.textContent.toLowerCase();
                if (optionText.includes(searchTerm) || option.dataset.value === 'walk_in') {
                    option.classList.remove('hidden');
                } else {
                    option.classList.add('hidden');
                }
            });
        });

        // Clear search when dropdown opens
        dropdownBtn.addEventListener('click', function() {
            customerSearchInput.value = '';
            dropdownList.querySelectorAll('.customer-option').forEach(option => {
                option.classList.remove('hidden');
            });
        });

        // Initialize color dropdown functionality
        initializeColorDropdowns();
    });

    // Color dropdown functionality
    function initializeColorDropdowns() {
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('color-select')) {
                const row = e.target.closest('.sale-item-row');
                const customInput = row.querySelector('.custom-color-input');

                if (e.target.value === 'custom') {
                    customInput.style.display = 'block';
                    customInput.required = true;
                    e.target.required = false;
                } else {
                    customInput.style.display = 'none';
                    customInput.required = false;
                    e.target.required = true;
                }
            }
        });
    }

    // Color validation function
    function validateColors() {
        const colorSelects = document.querySelectorAll('select[name="color[]"]');
        const customColorInputs = document.querySelectorAll('input[name="custom_color[]"]');
        let isValid = true;

        colorSelects.forEach((select, index) => {
            if (select.value === 'custom') {
                // Check if custom color input has value
                const customInput = select.closest('.sale-item-row').querySelector('.custom-color-input');

                if (!customInput.value.trim()) {
                    alert(`Please enter a custom color name for item ${index + 1}`);
                    isValid = false;
                    return;
                }
            } else if (select.value === '') {
                alert(`Please select a color for item ${index + 1}`);
                isValid = false;
                return;
            }
        });

        return isValid;
    }

    // Function to handle customer selection changes
    function handleCustomerSelection(customerId) {
        const walkInField = document.getElementById('walkInCustomerField');
        if (customerId === 'walk_in') {
            walkInField.style.display = 'block';
            walkInField.querySelector('input').required = true;
        } else {
            walkInField.style.display = 'none';
            walkInField.querySelector('input').required = false;
            walkInField.querySelector('input').value = '';
        }
    }

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('product-select')) {
            const row = e.target.closest('.sale-item-row');
            const option = e.target.options[e.target.selectedIndex];
            const unitPrice = row.querySelector('.unit-price');
            const purchasePrice = row.querySelector('.purchase-price');
            const quantity = row.querySelector('.quantity');

            // Get prices and round them to 2 decimal places
            const salePrice = parseFloat(option.dataset.salePrice) || 0;
            const purchasePriceValue = parseFloat(option.dataset.purchasePrice) || 0;
            const availableStock = parseInt(option.dataset.stock) || 0;

            // Don't auto-fill sale price - leave it empty for user input
            unitPrice.value = '';
            purchasePrice.value = purchasePriceValue > 0 ? purchasePriceValue.toFixed(2) : '';

            // Clear total price since sale price is empty
            const totalPrice = row.querySelector('.total-price');
            totalPrice.value = '';

            // Set max quantity to available stock (allow negative stock)
            quantity.max = availableStock >= 0 ? availableStock : 999999; // Allow any quantity for backordered items

            // Add stock indicator - COMMENTED OUT
            /*
            let stockIndicator = row.querySelector('.stock-indicator');
            if (!stockIndicator) {
                stockIndicator = document.createElement('small');
                stockIndicator.className = 'stock-indicator text-muted d-block mb-1';
                // Find the quantity field's container div and insert the stock indicator at the beginning
                const quantityContainer = quantity.closest('.quantity-container');
                if (quantityContainer) {
                    // Insert the stock indicator before the label
                    const label = quantityContainer.querySelector('label');
                    if (label) {
                        quantityContainer.insertBefore(stockIndicator, label);
                    } else {
                        quantityContainer.appendChild(stockIndicator);
                    }
                }
            }
            
            if (option.value && availableStock > 0) {
                if (availableStock <= 5) {
                    stockIndicator.textContent = `⚠️ Low Stock: ${availableStock} remaining`;
                    stockIndicator.className = 'stock-indicator text-warning d-block mt-1';
                } else {
                    stockIndicator.textContent = `Available Stock: ${availableStock}`;
                    stockIndicator.className = 'stock-indicator text-success d-block mt-1';
                }
            } else if (option.value && availableStock <= 0) {
                stockIndicator.textContent = '❌ Out of Stock';
                stockIndicator.className = 'stock-indicator text-danger d-block mt-1';
            } else {
                stockIndicator.textContent = '';
                stockIndicator.className = 'stock-indicator d-none';
            }
            */

            // Update totals
            updateTotals();
        }
    });

    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('quantity') || e.target.classList.contains('unit-price')) {
            const row = e.target.closest('.sale-item-row');
            const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
            const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
            const totalPrice = row.querySelector('.total-price');
            const productSelect = row.querySelector('.product-select');
            const selectedOption = productSelect.options[productSelect.selectedIndex];

            // Validate quantity against available stock
            if (selectedOption && selectedOption.value) {
                const availableStock = parseInt(selectedOption.dataset.stock) || 0;
                if (quantity <= 0) {
                    row.querySelector('.quantity').setCustomValidity('Quantity must be greater than 0');
                    row.querySelector('.quantity').classList.add('is-invalid');
                    totalPrice.value = '';
                } else if (availableStock >= 0 && quantity > availableStock) {
                    // Show warning but don't block - user can proceed if they want
                    row.querySelector('.quantity').setCustomValidity(`⚠️ Warning: Quantity exceeds available stock (${availableStock}). This will result in negative stock.`);
                    row.querySelector('.quantity').classList.add('is-warning');
                    row.querySelector('.quantity').classList.remove('is-invalid');

                    if (quantity > 0 && unitPrice > 0) {
                        totalPrice.value = (quantity * unitPrice).toFixed(2);
                    } else {
                        totalPrice.value = '';
                    }
                } else {
                    row.querySelector('.quantity').setCustomValidity('');
                    row.querySelector('.quantity').classList.remove('is-invalid');
                    row.querySelector('.quantity').classList.remove('is-warning');

                    if (quantity > 0 && unitPrice > 0) {
                        totalPrice.value = (quantity * unitPrice).toFixed(2);
                    } else {
                        totalPrice.value = '';
                    }
                }
            }

            // Update totals
            updateTotals();
        }
    });

    // Handle discount changes
    document.getElementById('discount').addEventListener('input', updateTotals);
    document.getElementById('paidAmount').addEventListener('input', updateDueAmount);

    // Handle payment method validation
    document.getElementById('paymentMethod').addEventListener('change', function() {
        if (this.value) {
            this.classList.remove('is-invalid');
        } else {
            this.classList.add('is-invalid');
        }
    });



    // Format all numeric fields on page load
    document.addEventListener('DOMContentLoaded', function() {
        const numericFields = document.querySelectorAll('input[type="number"]');
        numericFields.forEach(field => {
            if (field.value && !isNaN(field.value)) {
                field.value = parseFloat(field.value).toFixed(2);
            }
        });

        // Handle form reset to clear stock indicators - COMMENTED OUT
        /*
        const resetButton = document.querySelector('button[type="reset"]');
        if (resetButton) {
            resetButton.addEventListener('click', function() {
                setTimeout(() => {
                    const stockIndicators = document.querySelectorAll('.stock-indicator');
                    stockIndicators.forEach(indicator => indicator.remove());
                }, 100);
            });
        }
        */
    });

    function updateTotals() {
        const totalPrices = document.querySelectorAll('.total-price');
        let subtotal = 0;
        totalPrices.forEach(input => {
            if (input.value && !isNaN(input.value)) {
                subtotal += parseFloat(input.value);
            }
        });

        const discount = parseFloat(document.getElementById('discount').value) || 0;
        const totalAmount = subtotal - discount;

        document.getElementById('totalAmount').value = totalAmount.toFixed(2);

        updateDueAmount();
    }

    function updateDueAmount() {
        const totalAmount = parseFloat(document.getElementById('totalAmount').value) || 0;
        const paidAmount = parseFloat(document.getElementById('paidAmount').value) || 0;
        const dueAmount = totalAmount - paidAmount;

        document.getElementById('dueAmount').value = dueAmount.toFixed(2);
    }

    function validateSalePrice(input) {
        const value = parseFloat(input.value);
        if (value <= 0) {
            input.setCustomValidity('Sale Price must be greater than 0');
            input.classList.add('is-invalid');
        } else {
            input.setCustomValidity('');
            input.classList.remove('is-invalid');
        }
    }

    document.getElementById('saleForm').addEventListener('submit', function(e) {
        // Format all numeric fields to 2 decimal places before submission
        const numericFields = this.querySelectorAll('input[type="number"]');
        numericFields.forEach(field => {
            if (field.value && !isNaN(field.value)) {
                field.value = parseFloat(field.value).toFixed(2);
            }
        });

        // Validate all sale price fields and stock quantities
        const salePriceFields = document.querySelectorAll('.unit-price');
        const quantityFields = document.querySelectorAll('.quantity');
        const productSelects = document.querySelectorAll('.product-select');
        let isValid = true;
        let stockError = false;
        let stockWarningItems = [];

        // Check each product row
        for (let i = 0; i < productSelects.length; i++) {
            const productSelect = productSelects[i];
            const quantity = parseFloat(quantityFields[i].value) || 0;
            const unitPrice = parseFloat(salePriceFields[i].value) || 0;

            if (productSelect.value && quantity > 0) {
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                const availableStock = parseInt(selectedOption.dataset.stock) || 0;
                const productName = selectedOption.textContent.split('📦')[1]?.split('(')[0]?.trim() || 'Unknown Product';

                // Validate stock
                if (availableStock >= 0 && quantity > availableStock) {
                    stockWarningItems.push({
                        product: productName,
                        requested: quantity,
                        available: availableStock
                    });
                    stockError = true;
                } else {
                    quantityFields[i].setCustomValidity('');
                    quantityFields[i].classList.remove('is-invalid');
                }

                // Validate sale price
                if (!unitPrice || unitPrice <= 0) {
                    salePriceFields[i].classList.add('is-invalid');
                    isValid = false;
                } else {
                    salePriceFields[i].classList.remove('is-invalid');
                }
            }
        }

        // Validate payment method is selected
        const paymentMethod = document.getElementById('paymentMethod');
        if (!paymentMethod.value) {
            paymentMethod.classList.add('is-invalid');
            isValid = false;
        } else {
            paymentMethod.classList.remove('is-invalid');
        }

        // If there are stock warnings, show confirmation dialog
        if (stockError && stockWarningItems.length > 0) {
            e.preventDefault();

            let warningMessage = '⚠️ STOCK WARNING ⚠️\n\n';
            warningMessage += 'The following items exceed available stock:\n\n';

            stockWarningItems.forEach(item => {
                warningMessage += `• ${item.product}\n`;
                warningMessage += `  Requested: ${item.requested}\n`;
                warningMessage += `  Available: ${item.available}\n`;
                warningMessage += `  Shortage: ${item.requested - item.available}\n\n`;
            });

            warningMessage += '⚠️ This will result in NEGATIVE STOCK!\n\n';
            warningMessage += 'Do you want to proceed with the sale anyway?';

            if (!confirm(warningMessage)) {
                // User cancelled, highlight the problematic fields
                stockWarningItems.forEach((item, index) => {
                    const row = productSelects[index].closest('.sale-item-row');
                    if (row) {
                        const quantityField = row.querySelector('.quantity');
                        quantityField.classList.add('is-invalid');
                        quantityField.setCustomValidity(`Quantity exceeds available stock (${item.available})`);
                    }
                });
                return false;
            } else {
                // User confirmed, clear validation errors and proceed
                stockWarningItems.forEach((item, index) => {
                    const row = productSelects[index].closest('.sale-item-row');
                    if (row) {
                        const quantityField = row.querySelector('.quantity');
                        quantityField.classList.remove('is-invalid');
                        quantityField.setCustomValidity('');
                    }
                });
            }
        }

        if (!isValid) {
            e.preventDefault();
            if (!paymentMethod.value) {
                alert('Please select a payment method.');
            } else {
                alert('Please ensure all Sale Price fields have valid values greater than 0.');
            }
            return false;
        }
    });

    document.getElementById('addCustomerForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);
        fetch('add_customer_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add new customer to dropdown
                    var select = document.getElementById('customerSelect');
                    var option = document.createElement('option');
                    option.value = data.customer.id;
                    option.textContent = data.customer.name;
                    select.appendChild(option);
                    // Close modal
                    var modal = bootstrap.Modal.getInstance(document.getElementById('addCustomerModal'));
                    modal.hide();
                    form.reset();
                } else {
                    alert(data.error || 'Failed to add customer.');
                }
            })
            .catch(() => alert('Failed to add customer.'));
    });
</script>

<?php include 'includes/footer.php'; ?>

<style>
    /* Custom styles for the sales form */
    .sale-item-row {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        padding: 20px;
        border-radius: 12px;
        border: 2px solid #e9ecef;
        margin-bottom: 20px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        position: relative;
        overflow: hidden;
    }

    .sale-item-row::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #007bff, #28a745, #ffc107);
        opacity: 0.7;
    }

    .sale-item-row:hover {
        background: linear-gradient(135deg, #ffffff 0%, #f1f3f4 100%);
        border-color: #007bff;
        box-shadow: 0 4px 16px rgba(0, 123, 255, 0.15);
        transform: translateY(-2px);
    }

    .form-label.fw-bold {
        color: #495057;
        font-size: 0.9rem;
    }

    .input-group-text {
        background-color: #f8f9fa;
        border-color: #ced4da;
        color: #6c757d;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .form-control.is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }

    .form-control.is-invalid:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }

    .btn-group .btn {
        margin-right: 2px;
    }

    .btn-group .btn:last-child {
        margin-right: 0;
    }

    /* Form section headers */
    .text-primary.border-bottom {
        border-bottom: 2px solid #007bff !important;
    }

    /* Success message styling */
    .alert-success {
        border-left: 4px solid #28a745;
    }

    /* Responsive improvements */
    @media (max-width: 768px) {

        .sale-item-row .col-md-1,
        .sale-item-row .col-md-2,
        .sale-item-row .col-md-3 {
            margin-bottom: 10px;
        }

        .btn-group {
            display: flex;
            flex-direction: column;
        }

        .btn-group .btn {
            margin-bottom: 2px;
            margin-right: 0;
        }
    }

    /* Animation for form sections */
    .row.mb-4 {
        animation: fadeInUp 0.5s ease-out;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Enhanced button styles */
    .btn-lg {
        padding: 12px 24px;
        font-size: 1.1rem;
        font-weight: 500;
    }

    .btn-outline-primary:hover {
        background-color: #007bff;
        border-color: #007bff;
        color: white;
    }

    /* Form validation visual feedback */
    .form-control.is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }

    .form-control.is-valid {
        border-color: #28a745;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }

    .form-control.is-warning {
        border-color: #ffc107;
        box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
        background-color: rgba(255, 193, 7, 0.05);
    }

    .form-control.is-warning:focus {
        border-color: #ffc107;
        box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
        background-color: rgba(255, 193, 7, 0.05);
    }

    .form-control.is-warning:hover {
        border-color: #e0a800;
        background-color: rgba(255, 193, 7, 0.1);
    }

    /* Enhanced modal styles */
    .modal-header.bg-primary {
        border-bottom: 2px solid #0056b3;
    }

    .modal-content {
        border: none;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    /* Success/error message enhancements */
    .alert {
        border-radius: 8px;
        border: none;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
    }

    /* Stock indicator styles */
    .stock-indicator {
        font-size: 0.8rem;
        font-weight: 500;
        padding: 4px 8px;
        border-radius: 6px;
        background-color: rgba(0, 0, 0, 0.05);
        margin-bottom: 8px;
        display: block;
        text-align: center;
        width: 100%;
        box-sizing: border-box;
    }

    .quantity-container {
        position: relative;
        padding-top: 8px;
    }

    .stock-indicator.text-success {
        background-color: rgba(40, 167, 69, 0.1);
        color: #155724 !important;
    }

    .stock-indicator.text-danger {
        background-color: rgba(220, 53, 69, 0.1);
        color: #721c24 !important;
    }

    .stock-indicator.text-warning {
        background-color: rgba(255, 193, 7, 0.1);
        color: #856404 !important;
    }

    .stock-indicator.text-muted {
        background-color: rgba(108, 117, 125, 0.1);
        color: #6c757d !important;
    }

    /* Customer dropdown styling */
    .customer-dropdown-container {
        position: relative;
        width: 100%;
    }

    .customer-dropdown-btn {
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        font-weight: 400;
        line-height: 1.5;
        color: #212529;
        background-color: #fff;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        cursor: pointer;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        text-align: left;
    }

    .customer-dropdown-btn:hover {
        border-color: #86b7fe;
    }

    .customer-dropdown-btn:focus {
        border-color: #86b7fe;
        outline: 0;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    .dropdown-arrow {
        transition: transform 0.2s ease;
    }

    .customer-dropdown-btn.active .dropdown-arrow {
        transform: rotate(180deg);
    }

    .customer-dropdown-list {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 1000;
        display: none;
        background-color: #fff;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        max-height: 300px;
        overflow-y: auto;
        margin-top: 2px;
    }

    .customer-dropdown-list.show {
        display: block;
    }

    .customer-search-box {
        padding: 0.75rem;
        border-bottom: 1px solid #dee2e6;
    }

    .customer-search-box input {
        width: 100%;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }

    .customer-dropdown-separator {
        height: 1px;
        background-color: #dee2e6;
        margin: 0;
    }

    .customer-option {
        padding: 0.75rem 1rem;
        cursor: pointer;
        transition: background-color 0.15s ease-in-out;
        border-bottom: 1px solid #f8f9fa;
    }

    .customer-option:hover {
        background-color: #f8f9fa;
    }

    .customer-option.selected {
        background-color: #0d6efd;
        color: #fff;
    }

    .customer-option.hidden {
        display: none;
    }
</style>