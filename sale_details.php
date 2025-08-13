<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'sales';

$sale_id = intval($_GET['id'] ?? 0);
if (!$sale_id) {
    header("Location: sales.php");
    exit;
}

// Fetch sale details
$stmt = $pdo->prepare("
    SELECT s.*, COALESCE(c.name, s.walk_in_cust_name) AS customer_name, c.mobile AS customer_contact, c.address AS customer_address, c.email AS customer_email,
           u.username AS created_by_name
    FROM sale s
    LEFT JOIN customer c ON s.customer_id = c.id
    LEFT JOIN system_users u ON s.created_by = u.id
    WHERE s.id = ?
");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    header("Location: sales.php");
    exit;
}

// Fetch sale items with product details
$stmt = $pdo->prepare("
    SELECT si.*, p.product_name, p.product_unit, cat.name AS category_name
    FROM sale_items si
    LEFT JOIN products p ON si.product_id = p.id
    LEFT JOIN categories cat ON p.category_id = cat.id
    WHERE si.sale_id = ?
    ORDER BY si.id
");
$stmt->execute([$sale_id]);
$sale_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
            <div class="d-flex justify-content-end mb-3">
                <a href="sales.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to Sales</a>
            </div>
            <h2 class="mb-4">Sale Details</h2>
            <div class="card mb-4">
                <div class="card-header">Sale Information</div>
                <div class="card-body row">
                    <div class="col-md-6 mb-3">
                        <h5>Customer</h5>
                        <strong><?= htmlspecialchars($sale['customer_name']) ?></strong><br>
                        Contact: <?= htmlspecialchars($sale['customer_contact']) ?><br>
                        Address: <?= htmlspecialchars($sale['customer_address']) ?><br>
                        Email: <?= htmlspecialchars($sale['customer_email']) ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h5>Invoice</h5>
                        <strong>Invoice No:</strong> <?= htmlspecialchars($sale['sale_no']) ?><br>
                        <strong>Sale Date:</strong> <?= htmlspecialchars($sale['sale_date']) ?><br>
                        <strong>Delivery Date:</strong> <?= htmlspecialchars($sale['delivery_date']) ?><br>
                        <strong>Created By:</strong> <?= htmlspecialchars($sale['created_by_name']) ?><br>
                        <strong>Total Amount:</strong> <?= htmlspecialchars($sale['total_amount']) ?>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">Sale Items</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Unit</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $counter = 1; $grand_total = 0; foreach ($sale_items as $item): $grand_total += $item['total_price']; ?>
                                <tr>
                                    <td><?= $counter++ ?></td>
                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                    <td><?= htmlspecialchars($item['category_name']) ?></td>
                                    <td><?= htmlspecialchars($item['product_unit']) ?></td>
                                    <td><?= number_format($item['quantity'], 2) ?></td>
                                    <td><?= htmlspecialchars($item['unit_price']) ?></td>
                                    <td><?= htmlspecialchars($item['total_price']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="table-warning">
                                <td colspan="6" class="text-end"><strong>Grand Total:</strong></td>
                                <td><strong><?= htmlspecialchars($grand_total) ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                    <?php if (empty($sale_items)): ?>
                        <div class="text-center text-muted">No items found for this sale.</div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
