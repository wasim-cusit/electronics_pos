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

// Generate WhatsApp message for sale with complete details
function generateWhatsAppMessage($sale, $pdo) {
    try {
        // Get sale items for detailed message
        $stmt = $pdo->prepare("SELECT si.*, p.product_name, c.category FROM sale_items si 
                               LEFT JOIN products p ON si.product_id = p.id 
                               LEFT JOIN categories c ON p.category_id = c.id 
                               WHERE si.sale_id = ?");
        $stmt->execute([$sale['id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $message = "🛍️ *SALE INVOICE - TAILOR SHOP*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        // Header Information
        $message .= "📋 *Invoice No:* " . html_entity_decode($sale['sale_no']) . "\n";
        $message .= "👤 *Customer:* " . html_entity_decode($sale['customer_name']) . "\n";
        $message .= "📅 *Date:* " . date('d M Y', strtotime($sale['sale_date'])) . "\n";
        $message .= "🕐 *Time:* " . date('h:i A', strtotime($sale['sale_date'])) . "\n\n";
        
        // Items Details
        $message .= "🛒 *ITEMS PURCHASED:*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        
        foreach ($items as $index => $item) {
            $itemNo = $index + 1;
            $message .= $itemNo . ". *" . html_entity_decode($item['product_name']) . "*\n";
            if (!empty($item['category_name'])) {
                $message .= "   📂 Category: " . html_entity_decode($item['category_name']) . "\n";
            }
            if (!empty($item['product_code'])) {
                $message .= "   🏷️ Code: " . html_entity_decode($item['product_code']) . "\n";
            }
            $message .= "   📏 Qty: " . $item['quantity'] . " × PKR " . number_format($item['price'], 2) . "\n";
            $message .= "   💰 Total: PKR " . number_format($item['total_price'], 2) . "\n\n";
        }
        
        // Summary Section
        $message .= "📊 *BILL SUMMARY:*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "💰 *Subtotal:* PKR " . number_format($sale['subtotal'], 2) . "\n";
        
        if ($sale['discount'] > 0) {
            $message .= "🎯 *Discount:* PKR " . number_format($sale['discount'], 2) . "\n";
            $message .= "💵 *After Discount:* PKR " . number_format($sale['after_discount'], 2) . "\n";
        }
        
        $message .= "💳 *Total Amount:* PKR " . number_format($sale['total_amount'], 2) . "\n";
        $message .= "💸 *Paid Amount:* PKR " . number_format($sale['paid_amount'], 2) . "\n";
        
        if ($sale['due_amount'] > 0) {
            $message .= "⚠️ *Due Amount:* PKR " . number_format($sale['due_amount'], 2) . "\n";
        }
        
        // Footer
        $message .= "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "🏪 *WASEM WEARS*\n";
        $message .= "📞 Contact: +92 323 9507813\n";
        $message .= "📍 Address: Address shop #1 hameed plaza main university road\n";
        $message .= "🌐 Website: www.wasemwears.com\n\n";
        $message .= "Thank you for choosing us! 🙏\n";
        $message .= "Please visit again! ✨";
        
        return urlencode($message);
    } catch (Exception $e) {
        // Fallback to simple message if there's an error
        $message = "🛍️ *SALE INVOICE*\n\n";
        $message .= "📋 Invoice: " . html_entity_decode($sale['sale_no']) . "\n";
        $message .= "👤 Customer: " . html_entity_decode($sale['customer_name']) . "\n";
        $message .= "💰 Total: PKR " . number_format($sale['total_amount'], 2) . "\n";
        $message .= "📅 Date: " . date('d M Y', strtotime($sale['sale_date'])) . "\n\n";
        $message .= "Thank you! 🙏";
        
        return urlencode($message);
    }
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
$sales = $pdo->query("SELECT s.*, COALESCE(c.name, s.walk_in_cust_name) AS customer_name, c.mobile AS customer_mobile, u.username AS created_by_name FROM sale s LEFT JOIN customer c ON s.customer_id = c.id LEFT JOIN system_users u ON s.created_by = u.id ORDER BY s.id DESC")->fetchAll(PDO::FETCH_ASSOC);

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
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">
                            <i class="bi bi-list-ul me-2"></i> 
                            Sales History Dashboard
                        </h5>
                        <div class="header-stats">
                            <span class="badge bg-light text-dark me-2">
                                <i class="bi bi-cart-check me-1"></i>
                                Total Sales: <?= count($sales) ?>
                            </span>
                            <span class="badge bg-light text-dark">
                                <i class="bi bi-whatsapp me-1"></i>
                                WhatsApp Ready: <?= count(array_filter($sales, function($sale) { return !empty($sale['customer_mobile']); })) ?>
                            </span>
                        </div>
                    </div>
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
                                        <div class="customer-info">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="bi bi-person-circle text-primary me-2"></i> 
                                                <strong><?= htmlspecialchars($sale['customer_name']) ?></strong>
                                            </div>
                                            <?php if (!empty($sale['customer_mobile'])): ?>
                                                <div class="customer-mobile">
                                                    <i class="bi bi-phone me-1"></i> <?= htmlspecialchars($sale['customer_mobile']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
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
                                        <div class="action-buttons">
                                            <div class="btn-group-vertical" role="group">
                                                <a href="sale_details.php?id=<?= $sale['id'] ?>" class="btn btn-sm btn-info mb-1" title="View Details">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="print_invoice.php?id=<?= $sale['id'] ?>" target="_blank" class="btn btn-sm btn-success mb-1" title="Print Invoice">
                                                    <i class="bi bi-printer"></i>
                                                </a>
                                                                                            <?php if (!empty($sale['customer_mobile'])): ?>
                                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $sale['customer_mobile']) ?>?text=<?= generateWhatsAppMessage($sale, $pdo) ?>" target="_blank" class="btn btn-sm btn-whatsapp mb-1" title="Send Bill via WhatsApp" onclick="return confirm('Send bill to <?= htmlspecialchars($sale['customer_name']) ?> via WhatsApp?')">
                                                    <i class="bi bi-whatsapp"></i>
                                                </a>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-whatsapp mb-1" title="Send to another number" onclick="sendToAnotherNumber(<?= $sale['id'] ?>, '<?= htmlspecialchars($sale['customer_name']) ?>')">
                                                    <i class="bi bi-whatsapp"></i>
                                                </button>
                                            <?php endif; ?>
                                                <a href="sales.php?delete=<?= $sale['id'] ?>" class="btn btn-sm btn-danger" title="Delete Sale" onclick="return confirm('Are you sure you want to delete this sale? This action cannot be undone.')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
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

<!-- WhatsApp Number Modal -->
<div class="modal fade" id="whatsappNumberModal" tabindex="-1" aria-labelledby="whatsappNumberModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="whatsappNumberModalLabel">
                    <i class="bi bi-whatsapp me-2"></i>Send Bill via WhatsApp
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="customerName" class="form-label">Customer Name:</label>
                    <input type="text" class="form-control" id="customerName" readonly>
                </div>
                <div class="mb-3">
                    <label for="phoneNumber" class="form-label">Phone Number:</label>
                    <div class="input-group">
                        <span class="input-group-text">+92</span>
                        <input type="tel" class="form-control" id="phoneNumber" placeholder="3XX XXXXXXX" maxlength="10" pattern="[0-9]{10}">
                    </div>
                    <div class="form-text">Enter the 10-digit phone number without country code</div>
                </div>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <small>This will open WhatsApp with the bill message. Make sure the number is correct.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="sendWhatsAppMessage()">
                    <i class="bi bi-whatsapp me-2"></i>Send via WhatsApp
                </button>
            </div>
        </div>
    </div>
</div>



<?php include 'includes/footer.php'; ?>

<style>
/* Table improvements */
.table-hover tbody tr:hover {
    background-color: #f8f9fa;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.table-dark th {
    background: linear-gradient(135deg, #343a40 0%, #495057 100%);
    border-color: #454d55;
    color: white;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
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

/* WhatsApp Info Card Styling */
.whatsapp-info-card .card {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 1px solid #dee2e6;
}

.whatsapp-icon-wrapper {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
    box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
}

.feature-item {
    padding: 8px 0;
    font-size: 0.9rem;
    color: #495057;
}

.whatsapp-preview {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border: 2px dashed #25D366;
}

.preview-header {
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    color: white;
    padding: 10px 15px;
    border-radius: 8px;
    margin-bottom: 15px;
    font-weight: 600;
}

.preview-content {
    padding: 10px;
    background: #f8f9fa;
    border-radius: 8px;
}

/* Enhanced Action Buttons */
.action-buttons .btn-group-vertical {
    gap: 5px;
}

.action-buttons .btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.action-buttons .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.action-buttons .btn-whatsapp {
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    border: none;
    color: white;
    font-weight: 600;
}

.action-buttons .btn-whatsapp:hover {
    background: linear-gradient(135deg, #128C7E 0%, #075E54 100%);
    transform: translateY(-2px) scale(1.05);
}

.action-buttons .btn-whatsapp:disabled {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%) !important;
    border: none;
    color: #adb5bd !important;
    cursor: not-allowed;
    opacity: 0.7;
}

.action-buttons .btn-whatsapp:disabled:hover {
    transform: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.action-buttons .btn-info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    border: none;
}

.action-buttons .btn-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
}

.action-buttons .btn-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    border: none;
}

/* Enhanced Customer Display */
.customer-info {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 10px;
    border-radius: 8px;
    border-left: 4px solid #007bff;
}

.customer-mobile {
    background: #e3f2fd;
    color: #1976d2;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 500;
}

/* Enhanced Table Styling */
.table {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.table thead th {
    position: relative;
}

.table thead th::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, #25D366, #128C7E, #25D366);
}

/* Header Stats Styling */
.header-stats .badge {
    font-size: 0.8rem;
    padding: 8px 12px;
    border-radius: 20px;
    font-weight: 500;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.header-stats .badge:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
}

/* Enhanced Card Styling */
.card {
    border-radius: 16px;
    overflow: hidden;
}

.card-header {
    border-bottom: none;
    padding: 1.5rem;
}

/* Enhanced Badge Styling */
.badge {
    font-weight: 500;
    letter-spacing: 0.3px;
}

.badge.bg-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
}

.badge.bg-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
}

.badge.bg-warning {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%) !important;
}

.badge.bg-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
}

.badge.bg-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%) !important;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .whatsapp-info-card .row {
        flex-direction: column;
    }
    
    .whatsapp-preview {
        margin-top: 20px;
    }
    
    .action-buttons .btn-group-vertical {
        flex-direction: row;
        gap: 5px;
    }
    
    .header-stats {
        flex-direction: column;
        gap: 10px;
    }
    
    .header-stats .badge {
        margin: 0 !important;
    }
}

/* Modal Styling */
.modal-content {
    border-radius: 16px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.modal-header {
    border-radius: 16px 16px 0 0;
    border-bottom: none;
}

.modal-footer {
    border-top: none;
    border-radius: 0 0 16px 16px;
}

.input-group-text {
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    color: white;
    border: none;
    font-weight: 600;
}
</style>

<script>
let currentSaleId = null;
let currentSaleData = null;

// Function to open modal for sending to another number
function sendToAnotherNumber(saleId, customerName) {
    currentSaleId = saleId;
    document.getElementById('customerName').value = customerName;
    document.getElementById('phoneNumber').value = '';
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('whatsappNumberModal'));
    modal.show();
}

// Function to send WhatsApp message
function sendWhatsAppMessage() {
    const phoneNumber = document.getElementById('phoneNumber').value.trim();
    
    if (!phoneNumber) {
        alert('Please enter a phone number');
        return;
    }
    
    if (!/^\d{10}$/.test(phoneNumber)) {
        alert('Please enter a valid 10-digit phone number');
        return;
    }
    
    // Get the sale data and generate message
    fetch(`get_sale_data.php?id=${currentSaleId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const message = generateWhatsAppMessageFromData(data.sale);
                const whatsappUrl = `https://wa.me/92${phoneNumber}?text=${encodeURIComponent(message)}`;
                window.open(whatsappUrl, '_blank');
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('whatsappNumberModal'));
                modal.hide();
            } else {
                alert('Error: Could not load sale data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error: Could not load sale data');
        });
}

// Function to generate WhatsApp message from sale data
function generateWhatsAppMessageFromData(sale) {
    let message = "🛍️ *SALE INVOICE - TAILOR SHOP*\n";
    message += "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    // Header Information
    message += "📋 *Invoice No:* " + sale.sale_no + "\n";
    message += "👤 *Customer:* " + sale.customer_name + "\n";
    message += "📅 *Date:* " + sale.sale_date + "\n";
    message += "🕐 *Time:* " + sale.sale_time + "\n\n";
    
    // Items Details (if available)
    if (sale.items && sale.items.length > 0) {
        message += "🛒 *ITEMS PURCHASED:*\n";
        message += "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        
        sale.items.forEach((item, index) => {
            const itemNo = index + 1;
            message += itemNo + ". *" + item.product_name + "*\n";
            if (item.category_name) {
                message += "   📂 Category: " + item.category_name + "\n";
            }
            if (item.product_code) {
                message += "   🏷️ Code: " + item.product_code + "\n";
            }
            message += "   📏 Qty: " + item.quantity + " × PKR " + parseFloat(item.price).toFixed(2) + "\n";
            message += "   💰 Total: PKR " + parseFloat(item.total_price).toFixed(2) + "\n\n";
        });
    }
    
    // Summary Section
    message += "📊 *BILL SUMMARY:*\n";
    message += "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    message += "💰 *Subtotal:* PKR " + parseFloat(sale.subtotal).toFixed(2) + "\n";
    
    if (parseFloat(sale.discount) > 0) {
        message += "🎯 *Discount:* PKR " + parseFloat(sale.discount).toFixed(2) + "\n";
        message += "💵 *After Discount:* PKR " + parseFloat(sale.after_discount).toFixed(2) + "\n";
    }
    
    message += "💳 *Total Amount:* PKR " + parseFloat(sale.total_amount).toFixed(2) + "\n";
    message += "💸 *Paid Amount:* PKR " + parseFloat(sale.paid_amount).toFixed(2) + "\n";
    
    if (parseFloat(sale.due_amount) > 0) {
        message += "⚠️ *Due Amount:* PKR " + parseFloat(sale.due_amount).toFixed(2) + "\n";
    }
    
    // Footer
    message += "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    message += "🏪 *Tailor Shop*\n";
    message += "📞 Contact: +92 XXX XXXXXXX\n";
    message += "📍 Address: Your Shop Address\n";
    message += "🌐 Website: www.yourshop.com\n\n";
    message += "Thank you for choosing us! 🙏\n";
    message += "Please visit again! ✨";
    
    return message;
}

// Phone number input validation
document.addEventListener('DOMContentLoaded', function() {
    const phoneInput = document.getElementById('phoneNumber');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            // Remove non-numeric characters
            this.value = this.value.replace(/\D/g, '');
            
            // Limit to 10 digits
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
        });
    }
});
</script>