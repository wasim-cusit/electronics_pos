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
        $stmt = $pdo->prepare("INSERT INTO sale (customer_id, walk_in_cust_name, sale_no, sale_date, subtotal, discount, after_discount, total_amount, paid_amount, due_amount, payment_method_id, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$customer_id, $walk_in_cust_name, $invoice_no, $sale_date, $subtotal, $discount, $after_discount, $total_amount, $paid_amount, $due_amount, $payment_method_id, $notes, $created_by]);
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
$products = $pdo->query("SELECT p.*, COALESCE(SUM(si.quantity), 0) as stock_quantity, ROUND(COALESCE(SUM(si.quantity * si.sale_price) / SUM(si.quantity), 0), 2) as sale_price, ROUND(COALESCE(SUM(si.quantity * si.purchase_price) / SUM(si.quantity), 0), 2) as purchase_price FROM products p LEFT JOIN stock_items si ON p.id = si.product_id AND si.status = 'available' GROUP BY p.id HAVING stock_quantity > 0 ORDER BY p.product_name")->fetchAll(PDO::FETCH_ASSOC);
$payment_methods = $pdo->query("SELECT * FROM payment_method WHERE status = 1 ORDER BY method")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all sales
$sales = $pdo->query("SELECT s.*, COALESCE(c.name, s.walk_in_cust_name) AS customer_name, u.username AS created_by_name FROM sale s LEFT JOIN customer c ON s.customer_id = c.id LEFT JOIN system_users u ON s.created_by = u.id ORDER BY s.id DESC")->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="bi bi-list-ul text-primary"></i> Sales History</h2>
                <!-- <a href="add_sale.php" class="btn btn-primary">
                    <i class="bi bi-cart-plus"></i> Add New Sale
                </a> -->
            </div>

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
                                    <td colspan="12" class="text-center py-5">
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



<?php include 'includes/footer.php'; ?>

<style>
/* Table improvements */
.table-hover tbody tr:hover {
    background-color: #f8f9fa;
}

.table-dark th {
    background-color: #343a40;
    border-color: #454d55;
}

/* Success message styling */
.alert-success {
    border-left: 4px solid #28a745;
}

/* Enhanced button styles */
.btn-lg {
    padding: 12px 24px;
    font-size: 1.1rem;
    font-weight: 500;
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