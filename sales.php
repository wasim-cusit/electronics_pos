<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'sales';

// Get the next sale invoice number
function get_next_sale_invoice_no($pdo) {
    $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM sales");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $next = ($row && $row['max_id']) ? $row['max_id'] + 1 : 1;
    return 'SALE-' . str_pad($next, 3, '0', STR_PAD_LEFT);
}

// Handle Add Sale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_sale'])) {
    $customer_id = $_POST['customer_id'];
    $invoice_no = get_next_sale_invoice_no($pdo);
    $sale_date = $_POST['sale_date'];
    $delivery_date = $_POST['delivery_date'];
    $total_amount = $_POST['total_amount'];
    $created_by = $_SESSION['user_id'];

    $stmt = $pdo->prepare("INSERT INTO sales (customer_id, invoice_no, sale_date, delivery_date, total_amount, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$customer_id, $invoice_no, $sale_date, $delivery_date, $total_amount, $created_by]);
    $sale_id = $pdo->lastInsertId();

    // Handle sale items
    $product_ids = $_POST['product_id'];
    $quantities = $_POST['quantity'];
    $unit_prices = $_POST['unit_price'];
    $total_prices = $_POST['total_price'];

    for ($i = 0; $i < count($product_ids); $i++) {
        if (!empty($product_ids[$i])) {
            $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$sale_id, $product_ids[$i], $quantities[$i], $unit_prices[$i], $total_prices[$i]]);

            // Update stock
            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
            $stmt->execute([$quantities[$i], $product_ids[$i]]);

            // Add stock movement
            $stmt = $pdo->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, note, created_by) VALUES (?, 'sale', ?, 'Sale to customer', ?)");
            $stmt->execute([$product_ids[$i], $quantities[$i], $created_by]);

            // Check for low stock and create notification if needed
            $stmt = $pdo->prepare("SELECT name, stock_quantity, low_stock_threshold FROM products WHERE id = ?");
            $stmt->execute([$product_ids[$i]]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($product && $product['stock_quantity'] <= $product['low_stock_threshold']) {
                $msg = 'Low stock alert: ' . $product['name'] . ' stock is ' . $product['stock_quantity'] . ' (threshold: ' . $product['low_stock_threshold'] . ')';
                // Prevent duplicate unread notifications for this product and user
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND type = 'Low Stock' AND message = ? AND is_read = 0");
                $stmt->execute([$created_by, $msg]);
                $exists = $stmt->fetchColumn();
                if (!$exists) {
                    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'Low Stock', ?)");
                    $stmt->execute([$created_by, $msg]);
                }
            }
        }
    }

    header("Location: sales.php?success=added&sale_id=" . $sale_id);
    exit;
}

// Handle Delete Sale
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Get sale items to reverse stock
    $stmt = $pdo->prepare("SELECT product_id, quantity FROM sale_items WHERE sale_id = ?");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($items as $item) {
        $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
        $stmt->execute([$item['quantity'], $item['product_id']]);
    }
    
    $stmt = $pdo->prepare("DELETE FROM sale_items WHERE sale_id = ?");
    $stmt->execute([$id]);
    $stmt = $pdo->prepare("DELETE FROM sales WHERE id = ?");
    $stmt->execute([$id]);
    
    header("Location: sales.php?success=deleted");
    exit;
}

// Fetch customers and products for dropdowns
$customers = $pdo->query("SELECT * FROM customers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query("SELECT * FROM products WHERE stock_quantity > 0 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all sales
$sales = $pdo->query("SELECT s.*, c.name AS customer_name, u.username AS created_by_name FROM sales s LEFT JOIN customers c ON s.customer_id = c.id LEFT JOIN users u ON s.created_by = u.id ORDER BY s.id DESC")->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" 5" style="margin-top: 25px;">
            <h2 class="mb-4">Sales</h2>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php
                    if ($_GET['success'] === 'added') echo "Sale added successfully! <a href='print_invoice.php?id=" . $_GET['sale_id'] . "' target='_blank'>Print Invoice</a>";
                    if ($_GET['success'] === 'deleted') echo "Sale deleted successfully!";
                    ?>
                </div>
            <?php endif; ?>

            <!-- Add Sale Form -->
            <div class="card mb-4">
                <div class="card-header">Add Sale</div>
                <div class="card-body">
                    <form method="post" id="saleForm">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Customer</label>
                                <div class="input-group">
                                    <select name="customer_id" id="customerSelect" class="form-control" required>
                                        <option value="">Select Customer</option>
                                        <?php foreach ($customers as $customer): ?>
                                            <option value="<?= $customer['id'] ?>"><?= htmlspecialchars($customer['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3 d-none">
                                <label class="form-label">Invoice No</label>
                                <input type="text" name="invoice_no" class="form-control" value="<?= get_next_sale_invoice_no($pdo) ?>" readonly>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Sale Date</label>
                                <input type="date" name="sale_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Delivery Date (Optional)</label>
                                <input type="date" name="delivery_date" class="form-control">
                            </div>
                            <div class="col-md-2 mb-3" style="margin-top: 30px;">
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal"><i class="bi bi-person-plus"></i> Add Customer</button>

                            </div>
                            
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Sale Items</label>
                            <div id="saleItems">
                                <div class="row mb-2">
                                    <div class="col-md-4">
                                        <select name="product_id[]" class="form-control product-select" required>
                                            <option value="">Select Product</option>
                                            <?php foreach ($products as $product): ?>
                                                <option value="<?= $product['id'] ?>" data-unit="<?= $product['unit'] ?>" data-stock="<?= $product['stock_quantity'] ?>" data-price="<?= $product['sale_price'] ?>"><?= htmlspecialchars($product['name']) ?> (Stock: <?= $product['stock_quantity'] ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" step="0.01" name="quantity[]" class="form-control quantity" placeholder="Qty" required>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" step="0.01" name="unit_price[]" class="form-control unit-price" placeholder="Unit Price" required>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" step="0.01" name="total_price[]" class="form-control total-price" placeholder="Total" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                                        <button type="button" class="btn btn-secondary btn-sm" id="addItem">Add Item</button>
                                    </div>
                                </div>
                            </div>
                          
                        </div>

                        
                        
                            
                        <button type="submit" class="btn btn-primary" name="add_sale">Add Sale</button>
                            <div class="col-md-3 mb-3 float-end">
                                
                                <label class="form-label">Total Amount</label>
                                <input type="number" step="0.01" name="total_amount" class="form-control" required>
                            </div>
                       

                    </form>
                </div>
            </div>

            <!-- Add Customer Modal -->
            <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form id="addCustomerForm">
                    <div class="modal-header">
                      <h5 class="modal-title" id="addCustomerModalLabel">Add Customer</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Contact</label>
                        <input type="text" name="contact" class="form-control">
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" class="form-control">
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-primary">Add Customer</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
            <!-- Sale List Table -->
            <div class="card">
                <div class="card-header">Sale List</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Invoice No</th>
                                <th>Customer</th>
                                <th>Sale Date</th>
                                <th>Delivery Date</th>
                                <th>Total Amount</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales as $sale): ?>
                                <tr>
                                    <td><?= htmlspecialchars($sale['invoice_no']) ?></td>
                                    <td><?= htmlspecialchars($sale['customer_name']) ?></td>
                                    <td><?= htmlspecialchars($sale['sale_date']) ?></td>
                                    <td><?= htmlspecialchars($sale['delivery_date']) ?></td>
                                    <td><?= htmlspecialchars($sale['total_amount']) ?></td>
                                    <td><?= htmlspecialchars($sale['created_by_name']) ?></td>
                                    <td>
                                        <a href="sale_details.php?id=<?= $sale['id'] ?>" class="btn btn-sm btn-info">View</a>
                                        <a href="print_invoice.php?id=<?= $sale['id'] ?>" target="_blank" class="btn btn-sm btn-success">Print</a>
                                        <a href="sales.php?delete=<?= $sale['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this sale?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($sales)): ?>
                                <tr><td colspan="7" class="text-center">No sales found.</td></tr>
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
    newRow.querySelectorAll('input, select').forEach(input => input.value = '');
    container.appendChild(newRow);
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-item')) {
        if (document.querySelectorAll('.remove-item').length > 1) {
            e.target.closest('.row').remove();
        }
    }
});

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('product-select')) {
        const row = e.target.closest('.row');
        const option = e.target.options[e.target.selectedIndex];
        const unitPrice = row.querySelector('.unit-price');
        unitPrice.value = option.dataset.price || '';
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
        }
        
        // Update total amount
        updateTotalAmount();
    }
});

function updateTotalAmount() {
    const totalPrices = document.querySelectorAll('.total-price');
    let total = 0;
    totalPrices.forEach(input => {
        if (input.value) {
            total += parseFloat(input.value);
        }
    });
    document.querySelector('input[name="total_amount"]').value = total.toFixed(2);
}

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
            option.selected = true;
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