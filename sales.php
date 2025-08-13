<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'sales';

// Get the next sale invoice number
function get_next_sale_invoice_no($pdo) {
    $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM sale");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $next = ($row && $row['max_id']) ? $row['max_id'] + 1 : 1;
    return 'SALE-' . str_pad($next, 3, '0', STR_PAD_LEFT);
}

// Handle Add Sale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_sale'])) {
    $customer_id = $_POST['customer_id'];
    $walk_in_cust_name = $_POST['walk_in_cust_name'];
    $invoice_no = get_next_sale_invoice_no($pdo);
    $sale_date = $_POST['sale_date'];
    $delivery_date = !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : null;
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
            $customer_id = null; // Use null for walk-in customers (database now supports this)
        }
    }

    // If no error, proceed with the sale
    if (!isset($error)) {
        $after_discount = $subtotal - $discount;
        $stmt = $pdo->prepare("INSERT INTO sale (customer_id, walk_in_cust_name, sale_no, sale_date, delivery_date, subtotal, discount, after_discount, total_amount, paid_amount, due_amount, payment_method_id, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$customer_id, $walk_in_cust_name, $invoice_no, $sale_date, $delivery_date, $subtotal, $discount, $after_discount, $total_amount, $paid_amount, $due_amount, $payment_method_id, $notes, $created_by]);
        $sale_id = $pdo->lastInsertId();

        // Handle sale items
        $product_ids = $_POST['product_id'];
        $quantities = $_POST['quantity'];
        $purchase_prices = $_POST['purchase_price'];
        $unit_prices = $_POST['unit_price'];
        $total_prices = $_POST['total_price'];

        for ($i = 0; $i < count($product_ids); $i++) {
            if (!empty($product_ids[$i])) {
                try {
                    // Get product details and stock item details
                    $stmt = $pdo->prepare("SELECT p.product_name, si.id as stock_item_id, si.product_code, si.purchase_price, si.sale_price 
                                          FROM products p 
                                          JOIN stock_items si ON p.id = si.product_id 
                                          WHERE p.id = ? AND si.status = 'available' AND si.quantity >= ? 
                                          ORDER BY si.id ASC LIMIT 1");
                    $stmt->execute([$product_ids[$i], $quantities[$i]]);
                    $stock_item = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($stock_item) {
                        $product_code = $stock_item['product_code'] ?: '';
                        $stock_item_id = $stock_item['stock_item_id'];
                        
                        // Get category name for the product
                        $stmt = $pdo->prepare("SELECT c.category FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
                        $stmt->execute([$product_ids[$i]]);
                        $category = $stmt->fetch(PDO::FETCH_ASSOC);
                        $category_name = $category ? $category['category'] : '';
                        
                        $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, warehouse_id, product_code, price, stock_qty, quantity, total_price, category_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$sale_id, $product_ids[$i], 0, $product_code, $unit_prices[$i], $quantities[$i], $quantities[$i], $total_prices[$i], $category_name]);

                        // Update stock - remove from stock_items using specific stock item ID
                        $stmt = $pdo->prepare("UPDATE stock_items SET quantity = quantity - ? WHERE id = ?");
                        $stmt->execute([$quantities[$i], $stock_item_id]);

                        // Check for low stock and create notification if needed
                        $stmt = $pdo->prepare("SELECT p.product_name, p.alert_quantity, COALESCE(SUM(si.quantity), 0) as current_stock FROM products p LEFT JOIN stock_items si ON p.id = si.product_id AND si.status = 'available' WHERE p.id = ? GROUP BY p.id");
                        $stmt->execute([$product_ids[$i]]);
                        $product = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($product && $product['current_stock'] <= $product['alert_quantity']) {
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
                        // Log error if no stock available
                        error_log("No available stock found for product ID: " . $product_ids[$i] . " with quantity: " . $quantities[$i]);
                    }
                } catch (Exception $e) {
                    // Log any database errors
                    error_log("Error processing sale item: " . $e->getMessage());
                }
            }
        }

        header("Location: sales.php?success=added&sale_id=" . $sale_id);
        exit;
    } else {
        // If there was an error, redirect back to the form with error message
        header("Location: sales.php?error=" . urlencode($error));
        exit;
    }
}

// Handle Delete Sale
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Get sale items to reverse stock
    $stmt = $pdo->prepare("SELECT product_id, quantity FROM sale_items WHERE sale_id = ?");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($items as $item) {
        // Restore stock to stock_items table
        $stmt = $pdo->prepare("UPDATE stock_items SET quantity = quantity + ? WHERE product_id = ? AND status = 'available' LIMIT 1");
        $stmt->execute([$item['quantity'], $item['product_id']]);
    }
    
    $stmt = $pdo->prepare("DELETE FROM sale_items WHERE sale_id = ?");
    $stmt->execute([$id]);
    $stmt = $pdo->prepare("DELETE FROM sale WHERE id = ?");
    $stmt->execute([$id]);
    
    header("Location: sales.php?success=deleted");
    exit;
}

// Fetch customers and products for dropdowns
$customers = $pdo->query("SELECT * FROM customer ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query("SELECT p.*, COALESCE(SUM(si.quantity), 0) as stock_quantity, ROUND(COALESCE(AVG(si.sale_price), 0), 2) as sale_price, ROUND(COALESCE(AVG(si.purchase_price), 0), 2) as purchase_price FROM products p LEFT JOIN stock_items si ON p.id = si.product_id AND si.status = 'available' GROUP BY p.id HAVING stock_quantity > 0 ORDER BY p.product_name")->fetchAll(PDO::FETCH_ASSOC);
$payment_methods = $pdo->query("SELECT * FROM payment_method WHERE status = 1 ORDER BY method")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all sales
$sales = $pdo->query("SELECT s.*, COALESCE(c.name, s.walk_in_cust_name) AS customer_name, u.username AS created_by_name FROM sale s LEFT JOIN customer c ON s.customer_id = c.id LEFT JOIN system_users u ON s.created_by = u.id ORDER BY s.id DESC")->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
            <h2 class="mb-4">Sales</h2>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php
                    if ($_GET['success'] === 'added') echo "Sale added successfully! <a href='print_invoice.php?id=" . $_GET['sale_id'] . "' target='_blank'>Print Invoice</a>";
                    if ($_GET['success'] === 'deleted') echo "Sale deleted successfully!";
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
                                <select name="customer_id" id="customerSelect" class="form-select" required>
                                    <option value="">Select Customer</option>
                                    <option value="walk_in">🚶 Walk-in Customer</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?= $customer['id'] ?>">👤 <?= htmlspecialchars($customer['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3" id="walkInCustomerField" style="display: none; width: 18%;">
                                <label class="form-label fw-bold">Walk-in Customer Name <span class="text-danger">*</span></label>
                                <input type="text" name="walk_in_cust_name" class="form-control" placeholder="Enter customer name" required>
                            </div>
                            <div class="mb-3" style="width: 14%;">
                                <label class="form-label fw-bold">Sale Date <span class="text-danger">*</span></label>
                                <input type="date" name="sale_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="mb-3" style="width: 16%;">
                                <label class="form-label fw-bold">Delivery Date <small class="text-muted">(Optional)</small></label>
                                <input type="date" name="delivery_date" class="form-control">
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
                                                        <span class="text-muted">(Stock: <?= $product['stock_quantity'] ?>)</span>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div style="width:13%">
                                            <label class="form-label small fw-bold">Qty <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" name="quantity[]" class="form-control quantity" placeholder="Qty" required min="0.01">
                                        </div>
                                        <div style="width:13%">
                                            <label class="form-label small fw-bold">Purchase Price</label>
                                            <input type="number" step="0.01" name="purchase_price[]" class="form-control purchase-price" placeholder="P.Price" readonly>
                                        </div>
                                        <div style="width:13%">
                                            <label class="form-label small fw-bold">Sale Price <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" name="unit_price[]" class="form-control unit-price" placeholder="S.Price" required min="0.01">
                                        </div>
                                        <div style="width:13%">
                                            <label class="form-label small fw-bold">Total</label>
                                            <input type="number" step="0.01" name="total_price[]" class="form-control total-price" placeholder="Total" readonly>
                                        </div>
                                        <div class="col-md-2">
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
                                <button type="submit" class="btn btn-primary btn-lg" name="add_sale">
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

            <!-- Sale List Table -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Sales History</h5>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th><i class="bi bi-receipt"></i> Invoice No</th>
                                <th><i class="bi bi-person"></i> Customer</th>
                                <th><i class="bi bi-calendar-event"></i> Sale Date</th>
                                                                 <th><i class="bi bi-calendar-check"></i> Delivery Date</th>
                                 <th><i class="bi bi-percent"></i> Discount</th>
                                <th><i class="bi bi-calculator"></i> After Discount</th>
                                <th><i class="bi bi-currency-dollar"></i> Total Amount</th>
                                <th><i class="bi bi-cash"></i> Paid Amount</th>
                                <th><i class="bi bi-exclamation-triangle"></i> Due Amount</th>
                                <th><i class="bi bi-credit-card"></i> Payment Method</th>
                                <th><i class="bi bi-person-badge"></i> Created By</th>
                                <th><i class="bi bi-gear"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales as $sale): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-primary fs-6">
                                            <i class="bi bi-receipt"></i> <?= htmlspecialchars($sale['sale_no']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <i class="bi bi-person-circle"></i> 
                                        <?= htmlspecialchars($sale['customer_name']) ?>
                                    </td>
                                    <td>
                                        <i class="bi bi-calendar-event"></i> 
                                        <?= date('d M Y', strtotime($sale['sale_date'])) ?>
                                    </td>
                                                                         <td>
                                         <?php if ($sale['delivery_date']): ?>
                                             <i class="bi bi-calendar-check text-success"></i> 
                                             <?= date('d M Y', strtotime($sale['delivery_date'])) ?>
                                         <?php else: ?>
                                             <span class="text-muted">-</span>
                                         <?php endif; ?>
                                     </td>
                                    <td>
                                        <?php if ($sale['discount'] > 0): ?>
                                            <span class="badge bg-warning text-dark">
                                                PKR <?= number_format($sale['discount'], 2) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            PKR <?= number_format($sale['after_discount'], 2) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success fs-6">
                                            <strong>PKR <?= number_format($sale['total_amount'], 2) ?></strong>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">
                                            PKR <?= number_format($sale['paid_amount'], 2) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($sale['due_amount'] > 0): ?>
                                            <span class="badge bg-danger">
                                                PKR <?= number_format($sale['due_amount'], 2) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Paid</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($sale['payment_method_id']) {
                                            $stmt = $pdo->prepare("SELECT method FROM payment_method WHERE id = ?");
                                            $stmt->execute([$sale['payment_method_id']]);
                                            $method = $stmt->fetch(PDO::FETCH_ASSOC);
                                            echo '<span class="badge bg-primary"><i class="bi bi-credit-card"></i> ' . htmlspecialchars($method['method'] ?? '') . '</span>';
                                        } else {
                                            echo '<span class="text-muted">-</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <i class="bi bi-person-badge"></i> 
                                        <?= htmlspecialchars($sale['created_by_name']) ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="sale_details.php?id=<?= $sale['id'] ?>" class="btn btn-sm btn-info" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="print_invoice.php?id=<?= $sale['id'] ?>" target="_blank" class="btn btn-sm btn-success" title="Print Invoice">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                            <a href="sales.php?delete=<?= $sale['id'] ?>" class="btn btn-sm btn-danger" title="Delete Sale" onclick="return confirm('Are you sure you want to delete this sale? This action cannot be undone.')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($sales)): ?>
                                <tr>
                                    <td colspan="13" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="bi bi-cart-x fs-1"></i>
                                            <h5 class="mt-3">No sales found</h5>
                                            <p>Start creating your first sale using the form above.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
            itemRow.remove();
            updateTotals(); // Update totals after removing item
        }
    }
});

// Handle customer selection change
document.getElementById('customerSelect').addEventListener('change', function() {
    const walkInField = document.getElementById('walkInCustomerField');
    if (this.value === 'walk_in') {
        walkInField.style.display = 'block';
        walkInField.querySelector('input').required = true;
    } else {
        walkInField.style.display = 'none';
        walkInField.querySelector('input').required = false;
        walkInField.querySelector('input').value = '';
    }
});

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('product-select')) {
        const row = e.target.closest('.sale-item-row');
        const option = e.target.options[e.target.selectedIndex];
        const unitPrice = row.querySelector('.unit-price');
        const purchasePrice = row.querySelector('.purchase-price');
        
        // Get prices and round them to 2 decimal places
        const salePrice = parseFloat(option.dataset.salePrice) || 0;
        const purchasePriceValue = parseFloat(option.dataset.purchasePrice) || 0;
        
        // Don't auto-fill sale price - leave it empty for user input
        unitPrice.value = '';
        purchasePrice.value = purchasePriceValue > 0 ? purchasePriceValue.toFixed(2) : '';
        
        // Clear total price since sale price is empty
        const totalPrice = row.querySelector('.total-price');
        totalPrice.value = '';
        
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
        
        if (quantity > 0 && unitPrice > 0) {
            totalPrice.value = (quantity * unitPrice).toFixed(2);
        } else {
            totalPrice.value = '';
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

// Handle delivery date field - make it optional and allow clearing
document.querySelector('input[name="delivery_date"]').addEventListener('change', function() {
    // Allow empty delivery date - this field is optional
    if (this.value === '') {
        this.classList.remove('is-invalid');
        this.classList.remove('is-valid');
    } else {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
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
    
    // Validate all sale price fields
    const salePriceFields = document.querySelectorAll('.unit-price');
    let isValid = true;
    
    salePriceFields.forEach(field => {
        const value = parseFloat(field.value);
        if (!value || value <= 0) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    // Validate payment method is selected
    const paymentMethod = document.getElementById('paymentMethod');
    if (!paymentMethod.value) {
        paymentMethod.classList.add('is-invalid');
        isValid = false;
    } else {
        paymentMethod.classList.remove('is-invalid');
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
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    margin-bottom: 15px;
    transition: all 0.3s ease;
}

.sale-item-row:hover {
    background-color: #e9ecef;
    border-color: #adb5bd;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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

.form-control:focus, .form-select:focus {
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

/* Table improvements */
.table-hover tbody tr:hover {
    background-color: #f8f9fa;
}

.table-dark th {
    background-color: #343a40;
    border-color: #454d55;
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

/* Enhanced modal styles */
.modal-header.bg-primary {
    border-bottom: 2px solid #0056b3;
}

.modal-content {
    border: none;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

/* Success/error message enhancements */
.alert {
    border-radius: 8px;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
}
</style>