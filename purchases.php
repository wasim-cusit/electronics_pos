<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'purchases';

// Get the next invoice number
function get_next_invoice_no($pdo) {
    $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM purchases");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $next = ($row && $row['max_id']) ? $row['max_id'] + 1 : 1;
    return 'INV-' . str_pad($next, 3, '0', STR_PAD_LEFT);
}

// Handle Add Purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_purchase'])) {
    $supplier_id = $_POST['supplier_id'];
    $invoice_no = get_next_invoice_no($pdo);
    $purchase_date = $_POST['purchase_date'];
    $total_amount = $_POST['total_amount'];
    $created_by = $_SESSION['user_id'];

    $stmt = $pdo->prepare("INSERT INTO purchases (supplier_id, invoice_no, purchase_date, total_amount, created_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$supplier_id, $invoice_no, $purchase_date, $total_amount, $created_by]);
    $purchase_id = $pdo->lastInsertId();

    // Handle purchase items
    $product_ids = $_POST['product_id'];
    $quantities = $_POST['quantity'];
    $unit_prices = $_POST['unit_price'];
    $total_prices = $_POST['total_price'];

    for ($i = 0; $i < count($product_ids); $i++) {
        if (!empty($product_ids[$i])) {
            $stmt = $pdo->prepare("INSERT INTO purchase_items (purchase_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$purchase_id, $product_ids[$i], $quantities[$i], $unit_prices[$i], $total_prices[$i]]);

            // Update stock
            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
            $stmt->execute([$quantities[$i], $product_ids[$i]]);

            // Add stock movement
            $stmt = $pdo->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, note, created_by) VALUES (?, 'purchase', ?, 'Purchase from supplier', ?)");
            $stmt->execute([$product_ids[$i], $quantities[$i], $created_by]);

            // Check for low stock and create notification if needed
            $stmt = $pdo->prepare("SELECT name, stock_quantity, low_stock_threshold FROM products WHERE id = ?");
            $stmt->execute([$product_ids[$i]]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($product && $product['stock_quantity'] <= $product['low_stock_threshold']) {
                $msg = 'Low stock alert: ' . $product['name'] . ' stock is ' . $product['stock_quantity'] . ' (threshold: ' . $product['low_stock_threshold'] . ')';
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'Low Stock', ?)");
                $stmt->execute([$created_by, $msg]);
            }
        }
    }

    header("Location: purchases.php?success=added");
    exit;
}

// Handle Delete Purchase
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Get purchase items to reverse stock
    $stmt = $pdo->prepare("SELECT product_id, quantity FROM purchase_items WHERE purchase_id = ?");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($items as $item) {
        $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
        $stmt->execute([$item['quantity'], $item['product_id']]);
    }
    
    $stmt = $pdo->prepare("DELETE FROM purchase_items WHERE purchase_id = ?");
    $stmt->execute([$id]);
    $stmt = $pdo->prepare("DELETE FROM purchases WHERE id = ?");
    $stmt->execute([$id]);
    
    header("Location: purchases.php?success=deleted");
    exit;
}

// Fetch suppliers and products for dropdowns
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query("SELECT * FROM products ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all purchases
$purchases = $pdo->query("SELECT p.*, s.name AS supplier_name, u.username AS created_by_name FROM purchases p LEFT JOIN suppliers s ON p.supplier_id = s.id LEFT JOIN users u ON p.created_by = u.id ORDER BY p.id DESC")->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>
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
                                <select name="supplier_id" class="form-control" required>
                                    <option value="">Select Supplier</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3 d-none">
                                <label class="form-label">Invoice No</label>
                                <input type="text" name="invoice_no" class="form-control" value="<?= get_next_invoice_no($pdo) ?>" readonly>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Purchase Date</label>
                                <input type="date" name="purchase_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                            </div>
                            
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Purchase Items</label>
                            <div id="purchaseItems">
                                <div class="row mb-2">
                                    <div class="col-md-3">
                                        <select name="product_id[]" class="form-control product-select" required>
                                            <option value="">Select Product</option>
                                            <?php foreach ($products as $product): ?>
                                                <option value="<?= $product['id'] ?>" data-unit="<?= $product['unit'] ?>"><?= htmlspecialchars($product['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div style="width: 15%;">
                                        <input type="number" step="0.01" name="quantity[]" class="form-control quantity" placeholder="Qty" required>
                                    </div>
                                    <div style="width: 15%;">
                                        <input type="number" step="0.01" name="unit_price[]" class="form-control unit-price" placeholder="Unit Price" required>
                                    </div>
                                    <div style="width: 15%;">
                                        <input type="number" step="0.01" name="total_price[]" class="form-control total-price" placeholder="Total" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                                        <button type="button" class="btn btn-secondary btn-sm" id="addItem">Add Item</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" name="add_purchase">Add Purchase</button>
                        <div class="col-md-3 mb-3 float-end">
                            <label class="form-label">Total Amount</label>
                            <input type="number" step="0.01" name="total_amount" id="grandTotal" class="form-control" required readonly>
                        </div>
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
                                <th>Invoice No</th>
                                <th>Supplier</th>
                                <th>Purchase Date</th>
                                <th>Total Amount</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($purchases as $purchase): ?>
                                <tr>
                                    <td><?= htmlspecialchars($purchase['invoice_no']) ?></td>
                                    <td><?= htmlspecialchars($purchase['supplier_name']) ?></td>
                                    <td><?= htmlspecialchars($purchase['purchase_date']) ?></td>
                                    <td><?= htmlspecialchars($purchase['total_amount']) ?></td>
                                    <td><?= htmlspecialchars($purchase['created_by_name']) ?></td>
                                    <td>
                                        <a href="purchase_details.php?id=<?= $purchase['id'] ?>" class="btn btn-sm btn-info">View</a>
                                        <a href="print_purchase.php?id=<?= $purchase['id'] ?>" class="btn btn-sm btn-secondary" target="_blank">Print</a>
                                        <a href="purchases.php?delete=<?= $purchase['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this purchase?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($purchases)): ?>
                                <tr><td colspan="6" class="text-center">No purchases found.</td></tr>
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
    const container = document.getElementById('purchaseItems');
    const newRow = container.children[0].cloneNode(true);
    newRow.querySelectorAll('input, select').forEach(input => input.value = '');
    container.appendChild(newRow);
    
    // Recalculate grand total after adding new row
    calculateGrandTotal();
});

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
    
    document.getElementById('grandTotal').value = grandTotal.toFixed(2);
}

// Calculate grand total when page loads
document.addEventListener('DOMContentLoaded', function() {
    calculateGrandTotal();
});
</script>

<?php include 'includes/footer.php'; ?>