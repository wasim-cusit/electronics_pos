<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'reports';

// Sales summary
$sales = $pdo->query("SELECT * FROM sales")->fetchAll(PDO::FETCH_ASSOC);
$total_sales = 0;
$today_sales = 0;
$month_sales = 0;
foreach ($sales as $sale) {
    $total_sales += $sale['total_amount'];
    if ($sale['sale_date'] == date('Y-m-d')) $today_sales += $sale['total_amount'];
    if (date('Y-m', strtotime($sale['sale_date'])) == date('Y-m')) $month_sales += $sale['total_amount'];
}

// Purchases summary
$purchases = $pdo->query("SELECT * FROM purchases")->fetchAll(PDO::FETCH_ASSOC);
$total_purchases = 0;
$today_purchases = 0;
$month_purchases = 0;
foreach ($purchases as $purchase) {
    $total_purchases += $purchase['total_amount'];
    if ($purchase['purchase_date'] == date('Y-m-d')) $today_purchases += $purchase['total_amount'];
    if (date('Y-m', strtotime($purchase['purchase_date'])) == date('Y-m')) $month_purchases += $purchase['total_amount'];
}

// Expenses summary
$expenses = $pdo->query("SELECT * FROM expenses")->fetchAll(PDO::FETCH_ASSOC);
$total_expenses = 0;
$today_expenses = 0;
$month_expenses = 0;
foreach ($expenses as $expense) {
    $total_expenses += $expense['amount'];
    if ($expense['date'] == date('Y-m-d')) $today_expenses += $expense['amount'];
    if (date('Y-m', strtotime($expense['date'])) == date('Y-m')) $month_expenses += $expense['amount'];
}

// Low stock report
$low_stock_products = $pdo->query("SELECT * FROM products WHERE stock_quantity < 5 ORDER BY stock_quantity ASC")->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
            <h2 class="mb-4">Reports</h2>

            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">Today's Sales</h5>
                            <p class="card-text display-6">PKR <?= number_format($today_sales, 2) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-bg-success">
                        <div class="card-body">
                            <h5 class="card-title">This Month's Sales</h5>
                            <p class="card-text display-6">PKR <?= number_format($month_sales, 2) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">Total Sales</h5>
                            <p class="card-text display-6">PKR <?= number_format($total_sales, 2) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">Today's Purchases</h5>
                            <p class="card-text display-6">PKR <?= number_format($today_purchases, 2) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-bg-success">
                        <div class="card-body">
                            <h5 class="card-title">This Month's Purchases</h5>
                            <p class="card-text display-6">PKR <?= number_format($month_purchases, 2) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">Total Purchases</h5>
                            <p class="card-text display-6">PKR <?= number_format($total_purchases, 2) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">Today's Expenses</h5>
                            <p class="card-text display-6">PKR <?= number_format($today_expenses, 2) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-bg-success">
                        <div class="card-body">
                            <h5 class="card-title">This Month's Expenses</h5>
                            <p class="card-text display-6">PKR <?= number_format($month_expenses, 2) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">Total Expenses</h5>
                            <p class="card-text display-6">PKR <?= number_format($total_expenses, 2) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Low Stock Report -->
            <div class="card mt-4">
                <div class="card-header">Low Stock Products (Below 5)</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Unit</th>
                                <th>Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($low_stock_products as $product): ?>
                                <tr>
                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                    <td><?= htmlspecialchars($product['category_id']) ?></td>
                                    <td><?= htmlspecialchars($product['unit']) ?></td>
                                    <td><?= htmlspecialchars($product['stock_quantity']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($low_stock_products)): ?>
                                <tr><td colspan="4" class="text-center">No low stock products.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
<?php include 'includes/footer.php'; ?>