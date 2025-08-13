<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'purchases';

// Get the next invoice number
function get_next_invoice_no($pdo) {
    $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM purchase");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $next = ($row && $row['max_id']) ? $row['max_id'] + 1 : 1;
    return 'INV-' . str_pad($next, 3, '0', STR_PAD_LEFT);
}

// Handle Add Purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_purchase'])) {
    $supplier_id = $_POST['supplier_id'];
    $purchase_no = $_POST['purchase_no'];
    $purchase_date = $_POST['purchase_date'];
    $subtotal = floatval($_POST['subtotal']);
    $discount = floatval($_POST['discount'] ?? 0);
    $total_amount = floatval($_POST['total_amount']);
    $paid_amount = floatval($_POST['paid_amount'] ?? 0);
    $due_amount = floatval($_POST['due_amount'] ?? 0);
    $payment_method_id = $_POST['payment_method_id'] ?? null;
    $notes = $_POST['notes'] ?? '';
    $created_by = $_SESSION['user_id'];

    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO purchase (supplier_id, purchase_no, purchase_date, subtotal, discount, total_amount, paid_amount, due_amount, payment_method_id, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$supplier_id, $purchase_no, $purchase_date, $subtotal, $discount, $total_amount, $paid_amount, $due_amount, $payment_method_id, $notes, $created_by]);
        $purchase_id = $pdo->lastInsertId();

        // Handle purchase items
        $product_ids = $_POST['product_id'];
        $colors = $_POST['color'] ?? [];
        $quantities = $_POST['quantity'];
        $unit_prices = $_POST['unit_price'];
        $total_prices = $_POST['total_price'];
        
        // Debug: Log color data
        error_log("Colors received: " . print_r($colors, true));

        for ($i = 0; $i < count($product_ids); $i++) {
            if (!empty($product_ids[$i])) {
                // Get product details for product_code
                $stmt = $pdo->prepare("SELECT product_name, product_code FROM products WHERE id = ?");
                $stmt->execute([$product_ids[$i]]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                $product_code = $product ? $product['product_code'] : '';
                
                $color = $colors[$i] ?? '#000000';
                
                // Debug: Log color being inserted
                error_log("Inserting color: $color for product_id: {$product_ids[$i]}");
                
                $stmt = $pdo->prepare("INSERT INTO purchase_items (purchase_id, product_id, product_code, color, purchase_price, sale_price, quantity, purchase_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$purchase_id, $product_ids[$i], $product_code, $color, $unit_prices[$i], $unit_prices[$i], $quantities[$i], $total_prices[$i]]);

                // Add to stock_items table for inventory management
                try {
                    error_log("Inserting into stock_items - Color: $color, Product ID: {$product_ids[$i]}");
                    $stmt = $pdo->prepare("INSERT INTO stock_items (product_id, purchase_item_id, product_code, color, quantity, purchase_price, sale_price, stock_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), 'available')");
                    $stmt->execute([$product_ids[$i], $purchase_id, $product_code, $color, $quantities[$i], $unit_prices[$i], $unit_prices[$i]]);
                } catch (Exception $e) {
                    error_log("Stock items update failed: " . $e->getMessage());
                }

                // Check for low stock and create notification if needed
                try {
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
                } catch (Exception $e) {
                    // If any of these operations fail, skip notification
                    error_log("Low stock check failed: " . $e->getMessage());
                }
            }
        }

        // Log successful purchase with colors
        error_log("Purchase completed successfully with colors: " . implode(', ', array_filter($colors)));
        
        $pdo->commit();
        header("Location: purchases.php?success=added");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Purchase failed: " . $e->getMessage());
        header("Location: purchases.php?error=purchase_failed");
        exit;
    }
}

// Handle Delete Purchase
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Get purchase items to reverse stock
    $stmt = $pdo->prepare("SELECT product_id, quantity FROM purchase_items WHERE purchase_id = ?");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($items as $item) {
        try {
            // Remove from stock_items table
            $stmt = $pdo->prepare("DELETE FROM stock_items WHERE purchase_item_id = ? AND product_id = ? LIMIT ?");
            $stmt->execute([$id, $item['product_id'], $item['quantity']]);
        } catch (Exception $e) {
            error_log("Stock items removal failed: " . $e->getMessage());
        }
    }
    
    $stmt = $pdo->prepare("DELETE FROM purchase_items WHERE purchase_id = ?");
    $stmt->execute([$id]);
    $stmt = $pdo->prepare("DELETE FROM purchase WHERE id = ?");
    $stmt->execute([$id]);
    
    header("Location: purchases.php?success=deleted");
    exit;
}

// Fetch suppliers and products for dropdowns
$suppliers = $pdo->query("SELECT * FROM supplier ORDER BY supplier_name")->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query("SELECT * FROM products ORDER BY product_name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all purchases
$purchases = $pdo->query("SELECT p.*, s.supplier_name, u.username AS created_by_name FROM purchase p LEFT JOIN supplier s ON p.supplier_id = s.id LEFT JOIN system_users u ON p.created_by = u.id ORDER BY p.id DESC")->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>
<style>
.color-preview {
    transition: background-color 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.form-control-color {
    cursor: pointer;
    border: 1px solid #ced4da;
}

.form-control-color:hover {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

.purchase-item-row {
    background-color: #f8f9fa;
    border-radius: 5px;
    padding: 10px;
    margin-bottom: 10px;
}
</style>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" 5" style="margin-top: 25px;">
            <h2 class="mb-4">Purchases</h2>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php
                    if ($_GET['success'] === 'added') echo "Purchase added successfully!";
                    if ($_GET['success'] === 'deleted') echo "Purchase deleted successfully!";
                    ?>
                </div>
            <?php endif; ?>

            <!-- Add Purchase Form -->
            <div class="card mb-4">
                <div class="card-header">Add Purchase</div>
                <div class="card-body">
                    <form method="post" id="purchaseForm">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Supplier</label>
                                <select name="supplier_id" class="form-control" required id="supplierSelect">
                                    <option value="">Select Supplier</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['supplier_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3 d-none">
                                <label class="form-label">Purchase No</label>
                                <input type="text" name="purchase_no" class="form-control" value="<?= get_next_invoice_no($pdo) ?>" readonly>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Purchase Date</label>
                                <input type="date" name="purchase_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" class="btn btn-outline-primary w-100" onclick="openAddSupplierModal()">
                                    <i class="bi bi-plus-circle"></i> Add New Supplier
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Purchase Items</label>
                            <div id="purchaseItems">
                                <div class="row mb-2 align-items-end">
                                    <div class="col-md-2">
                                        <label class="form-label small">Product</label>
                                        <select name="product_id[]" class="form-control product-select" required>
                                            <option value="">Select Product</option>
                                            <?php foreach ($products as $product): ?>
                                                <option value="<?= $product['id'] ?>" data-unit="<?= $product['product_unit'] ?>"><?= htmlspecialchars($product['product_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label small">Color</label>
                                        <div class="d-flex align-items-center">
                                            <input type="color" name="color[]" class="form-control form-control-color me-2" value="#000000" title="Click to choose fabric color" style="height: 38px; width: 80px;">
                                            <span class="color-preview" style="width: 20px; height: 20px; border: 1px solid #ddd; border-radius: 3px; background-color: #000000;" title="Selected color preview"></span>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">Quantity</label>
                                        <input type="number" step="0.01" name="quantity[]" class="form-control quantity" placeholder="Qty" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">Unit Price</label>
                                        <input type="number" step="0.01" name="unit_price[]" class="form-control unit-price" placeholder="Unit Price" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">Total</label>
                                        <input type="number" step="0.01" name="total_price[]" class="form-control total-price" placeholder="Total" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                                        <button type="button" class="btn btn-secondary btn-sm" id="addItem">Add Item</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Details Section -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">💰 Payment Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">Subtotal</label>
                                        <input type="number" step="0.01" name="subtotal" id="subtotal" class="form-control" readonly>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">Discount</label>
                                        <input type="number" step="0.01" name="discount" id="discount" class="form-control" value="0.00" step="0.01">
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">Final Amount</label>
                                        <input type="number" step="0.01" name="total_amount" id="grandTotal" class="form-control" required readonly>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">Paid Amount</label>
                                        <input type="number" step="0.01" name="paid_amount" id="paid_amount" class="form-control" value="0.00" step="0.01">
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">Remaining Amount</label>
                                        <input type="number" step="0.01" name="due_amount" id="due_amount" class="form-control" readonly>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Payment Method</label>
                                        <select name="payment_method_id" class="form-control">
                                            <option value="">Select Method</option>
                                            <?php 
                                            $payment_methods = $pdo->query("SELECT * FROM payment_method WHERE status = 1")->fetchAll(PDO::FETCH_ASSOC);
                                            foreach ($payment_methods as $method): ?>
                                                <option value="<?= $method['id'] ?>"><?= htmlspecialchars($method['method']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-5 mb-3">
                                        <label class="form-label">Details/Notes</label>
                                        <textarea name="notes" class="form-control" rows="3" placeholder="Enter payment details, notes, or other information"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" name="add_purchase">Add Purchase</button>
                    </form>
                </div>
            </div>

            <!-- Purchase List Table -->
            <div class="card">
                <div class="card-header">Purchase List</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Purchase No</th>
                                <th>Supplier</th>
                                <th>Purchase Date & Time</th>
                                <th>Total Amount</th>
                                <th>Paid Amount</th>
                                <th>Remaining</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($purchases as $purchase): ?>
                                <tr>
                                    <td><?= htmlspecialchars($purchase['purchase_no']) ?></td>
                                    <td><?= htmlspecialchars($purchase['supplier_name']) ?></td>
                                    <td>
                                        <small>
                                            <?php if ($purchase['purchase_date']): ?>
                                                <?= date('d M Y', strtotime($purchase['purchase_date'])) ?><br>
                                                <span class="text-secondary"><?= date('H:i') ?></span>
                                            <?php else: ?>
                                                <span class="text-secondary">N/A</span>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>PKR <?= number_format($purchase['total_amount'], 2) ?></td>
                                    <td>PKR <?= number_format($purchase['paid_amount'], 2) ?></td>
                                    <td>
                                        <span class="badge <?= $purchase['due_amount'] > 0 ? 'bg-warning' : 'bg-success' ?>">
                                            PKR <?= number_format($purchase['due_amount'], 2) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($purchase['payment_method_id']) {
                                            $stmt = $pdo->prepare("SELECT method FROM payment_method WHERE id = ?");
                                            $stmt->execute([$purchase['payment_method_id']]);
                                            $method = $stmt->fetch(PDO::FETCH_ASSOC);
                                            echo htmlspecialchars($method['method'] ?? 'N/A');
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $purchase['due_amount'] > 0 ? 'bg-warning' : 'bg-success' ?>">
                                            <?= $purchase['due_amount'] > 0 ? 'Pending' : 'Paid' ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($purchase['created_by_name']) ?></td>
                                    <td>
                                        <a href="purchase_details.php?id=<?= $purchase['id'] ?>" class="btn btn-sm btn-info">View</a>
                                        <a href="print_purchase.php?id=<?= $purchase['id'] ?>" class="btn btn-sm btn-secondary" target="_blank">Print</a>
                                        <a href="purchases.php?delete=<?= $purchase['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this purchase?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($purchases)): ?>
                                <tr><td colspan="10" class="text-center">No purchases found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1" aria-labelledby="addSupplierModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSupplierModalLabel">Add New Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addSupplierForm">
                    <div class="mb-3">
                        <label for="supplierName" class="form-label">Supplier Name *</label>
                        <input type="text" class="form-control" id="supplierName" name="supplier_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="supplierContact" class="form-label">Contact Number</label>
                        <input type="text" class="form-control" id="supplierContact" name="supplier_contact">
                    </div>
                    <div class="mb-3">
                        <label for="supplierEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="supplierEmail" name="supplier_email">
                    </div>
                    <div class="mb-3">
                        <label for="supplierAddress" class="form-label">Address</label>
                        <textarea class="form-control" id="supplierAddress" name="supplier_address" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveSupplier()">Add Supplier</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('addItem').addEventListener('click', function() {
    const container = document.getElementById('purchaseItems');
    const newRow = container.children[0].cloneNode(true);
    
    // Reset all form fields
    newRow.querySelectorAll('input, select').forEach(input => {
        if (input.type === 'color') {
            input.value = '#000000'; // Reset color to default
            // Reset color preview
            const colorPreview = input.parentElement.querySelector('.color-preview');
            if (colorPreview) {
                colorPreview.style.backgroundColor = '#000000';
            }
        } else {
            input.value = '';
        }
    });
    
    container.appendChild(newRow);
    
    // Recalculate grand total after adding new row
    calculateGrandTotal();
});

// Payment calculations
document.getElementById('discount').addEventListener('input', calculateFinalAmount);
document.getElementById('paid_amount').addEventListener('input', calculateRemainingAmount);

function calculateFinalAmount() {
    const subtotal = parseFloat(document.getElementById('subtotal').value) || 0;
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    
    const finalAmount = subtotal - discount;
    document.getElementById('grandTotal').value = finalAmount.toFixed(2);
    
    // Recalculate remaining amount
    calculateRemainingAmount();
}

function calculateRemainingAmount() {
    const finalAmount = parseFloat(document.getElementById('grandTotal').value) || 0;
    const paidAmount = parseFloat(document.getElementById('paid_amount').value) || 0;
    
    const remaining = finalAmount - paidAmount;
    document.getElementById('due_amount').value = remaining.toFixed(2);
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-item')) {
        if (document.querySelectorAll('.remove-item').length > 1) {
            e.target.closest('.row').remove();
            // Recalculate grand total after removing row
            calculateGrandTotal();
        }
    }
});

document.addEventListener('input', function(e) {
    if (e.target.classList.contains('quantity') || e.target.classList.contains('unit-price')) {
        const row = e.target.closest('.row');
        const quantity = row.querySelector('.quantity').value;
        const unitPrice = row.querySelector('.unit-price').value;
        const totalPrice = row.querySelector('.total-price');
        
        if (quantity && unitPrice) {
            totalPrice.value = (quantity * unitPrice).toFixed(2);
        } else {
            totalPrice.value = '';
        }
        
        // Calculate grand total
        calculateGrandTotal();
    }
});

function calculateGrandTotal() {
    const totalPrices = document.querySelectorAll('.total-price');
    let grandTotal = 0;
    
    totalPrices.forEach(input => {
        if (input.value && !isNaN(input.value)) {
            grandTotal += parseFloat(input.value);
        }
    });
    
    document.getElementById('subtotal').value = grandTotal.toFixed(2);
    document.getElementById('grandTotal').value = grandTotal.toFixed(2);
    
    // Recalculate final amount with discount
    calculateFinalAmount();
}

// Calculate grand total when page loads
document.addEventListener('DOMContentLoaded', function() {
    calculateGrandTotal();
    
    // Initialize color preview functionality
    initializeColorPreviews();
    
    // Add form validation for colors
    document.getElementById('purchaseForm').addEventListener('submit', function(e) {
        const colorInputs = document.querySelectorAll('input[name="color[]"]');
        let hasValidColors = true;
        
        colorInputs.forEach((input, index) => {
            if (!input.value || input.value === '#000000') {
                console.log(`Color ${index + 1} is default or empty`);
            } else {
                console.log(`Color ${index + 1} selected: ${input.value}`);
            }
        });
        
        // Log all form data for debugging
        const formData = new FormData(this);
        for (let [key, value] of formData.entries()) {
            if (key === 'color[]') {
                console.log(`Form color data: ${key} = ${value}`);
            }
        }
    });
});

    // Color preview functionality
    function initializeColorPreviews() {
        document.addEventListener('input', function(e) {
            if (e.target.type === 'color') {
                const colorPreview = e.target.parentElement.querySelector('.color-preview');
                if (colorPreview) {
                    colorPreview.style.backgroundColor = e.target.value;
                }
            }
        });
    }
    
    // Supplier modal functionality
    function openAddSupplierModal() {
        // Clear the form
        document.getElementById('addSupplierForm').reset();
        // Show the modal
        new bootstrap.Modal(document.getElementById('addSupplierModal')).show();
    }
    
    function saveSupplier() {
        const form = document.getElementById('addSupplierForm');
        const formData = new FormData(form);
        
        // Validate required fields
        if (!formData.get('supplier_name').trim()) {
            alert('Supplier name is required!');
            return;
        }
        
        // Send data to PHP endpoint
        fetch('add_supplier_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Add new supplier to the select dropdown
                const supplierSelect = document.getElementById('supplierSelect');
                const newOption = document.createElement('option');
                newOption.value = data.supplier_id;
                newOption.textContent = formData.get('supplier_name');
                supplierSelect.appendChild(newOption);
                
                // Select the new supplier
                supplierSelect.value = data.supplier_id;
                
                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('addSupplierModal')).hide();
                
                // Show success message
                alert('Supplier added successfully!');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while adding supplier');
        });
    }
</script>

<?php include 'includes/footer.php'; ?>