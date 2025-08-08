<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'customers';

// Get customer ID from URL
$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$customer_id) {
    header("Location: customers.php?error=invalid_customer");
    exit;
}

// Fetch customer details
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    header("Location: customers.php?error=customer_not_found");
    exit;
}

// Fetch customer's sales history
$stmt = $pdo->prepare("
    SELECT s.*, u.username as created_by_name 
    FROM sales s 
    LEFT JOIN users u ON s.created_by = u.id 
    WHERE s.customer_id = ? 
    ORDER BY s.sale_date DESC
");
$stmt->execute([$customer_id]);
$sales_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate customer statistics
$total_sales = 0;
$total_orders = count($sales_history);
$last_order_date = null;

foreach ($sales_history as $sale) {
    $total_sales += $sale['total_amount'];
    if (!$last_order_date || $sale['sale_date'] > $last_order_date) {
        $last_order_date = $sale['sale_date'];
    }
}

// Get recent sales with items
$stmt = $pdo->prepare("
    SELECT s.*, si.product_id, si.quantity, si.unit_price, si.total_price,
           p.name as product_name, p.unit as product_unit
    FROM sales s
    JOIN sale_items si ON s.id = si.sale_id
    JOIN products p ON si.product_id = p.id
    WHERE s.customer_id = ?
    ORDER BY s.sale_date DESC, s.id DESC
    LIMIT 20
");
$stmt->execute([$customer_id]);
$recent_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
            
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="customers.php">Customers</a></li>
                    <li class="breadcrumb-item active">Customer History</li>
                </ol>
            </nav>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <?php
                    if ($_GET['error'] === 'invalid_customer') echo "Invalid customer ID provided.";
                    if ($_GET['error'] === 'customer_not_found') echo "Customer not found.";
                    ?>
                </div>
            <?php endif; ?>

            <!-- Customer Details Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Customer Information</h5>
                    <a href="customers.php?edit=<?= $customer_id ?>" class="btn btn-primary btn-sm">
                        <i class="bi bi-pencil"></i> Edit Customer
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Personal Information</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Name:</strong></td>
                                    <td><?= htmlspecialchars($customer['name']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Contact:</strong></td>
                                    <td><?= htmlspecialchars($customer['contact']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><?= htmlspecialchars($customer['email']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Address:</strong></td>
                                    <td><?= htmlspecialchars($customer['address']) ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Purchase Statistics</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Total Orders:</strong></td>
                                    <td><span class="badge bg-primary"><?= $total_orders ?></span></td>
                                </tr>
                                <tr>
                                    <td><strong>Total Spent:</strong></td>
                                    <td><span class="badge bg-success">PKR <?= number_format($total_sales, 2) ?></span></td>
                                </tr>
                                <tr>
                                    <td><strong>Last Order:</strong></td>
                                    <td>
                                        <?php if ($last_order_date): ?>
                                            <span class="badge bg-info"><?= date('d M Y', strtotime($last_order_date)) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">No orders yet</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Member Since:</strong></td>
                                    <td><span class="badge bg-warning text-dark"><?= date('d M Y', strtotime($customer['created_at'])) ?></span></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales History -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Sales History</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($sales_history)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-cart-x fs-1 text-muted"></i>
                            <p class="text-muted mt-2">No sales history found for this customer.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Date</th>
                                        <th>Delivery Date</th>
                                        <th>Amount</th>
                                        <th>Created By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sales_history as $sale): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($sale['invoice_no']) ?></strong>
                                            </td>
                                            <td><?= date('d M Y', strtotime($sale['sale_date'])) ?></td>
                                            <td>
                                                <?php if ($sale['delivery_date']): ?>
                                                    <?= date('d M Y', strtotime($sale['delivery_date'])) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">
                                                    PKR <?= number_format($sale['total_amount'], 2) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($sale['created_by_name']) ?></td>
                                            <td>
                                                <a href="sale_details.php?id=<?= $sale['id'] ?>" class="btn btn-sm btn-info">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                                <a href="print_invoice.php?id=<?= $sale['id'] ?>" class="btn btn-sm btn-secondary" target="_blank">
                                                    <i class="bi bi-printer"></i> Print
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Items Purchased -->
            <?php if (!empty($recent_items)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Items Purchased</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Invoice #</th>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_items as $item): ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($item['sale_date'])) ?></td>
                                        <td>
                                            <a href="sale_details.php?id=<?= $item['id'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($item['invoice_no']) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                                        <td>
                                            <?= htmlspecialchars($item['quantity']) ?> 
                                            <?= htmlspecialchars($item['product_unit']) ?>
                                        </td>
                                        <td>PKR <?= number_format($item['unit_price'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-primary">
                                                PKR <?= number_format($item['total_price'], 2) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
