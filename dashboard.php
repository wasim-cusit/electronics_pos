<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';
$activePage = 'dashboard';

// Get real-time data for dashboard
try {
    // Today's sales
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as today_sales FROM sale WHERE DATE(sale_date) = CURDATE()");
    $stmt->execute();
    $today_sales = $stmt->fetchColumn();

    // This month's sales
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as month_sales FROM sale WHERE DATE_FORMAT(sale_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')");
    $stmt->execute();
    $month_sales = $stmt->fetchColumn();

    // Total sales
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total_sales FROM sale");
    $total_sales = $stmt->fetchColumn();

    // Total stock value (using stock_items table)
    $stmt = $pdo->query("SELECT COALESCE(SUM(si.quantity * si.purchase_price), 0) as stock_value FROM stock_items si WHERE si.status = 'available'");
    $stock_value = $stmt->fetchColumn();

    // Upcoming deliveries (next 7 days)
    $stmt = $pdo->prepare("SELECT COUNT(*) as upcoming_deliveries FROM sale WHERE delivery_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND delivery_date IS NOT NULL");
    $stmt->execute();
    $upcoming_deliveries = $stmt->fetchColumn();

    // Low stock alerts (using products table with alert_quantity)
    $stmt = $pdo->query("SELECT COUNT(*) as low_stock_count FROM products p JOIN stock_items si ON p.id = si.product_id WHERE si.quantity <= p.alert_quantity AND si.status = 'available'");
    $low_stock_count = $stmt->fetchColumn();

    // Today's expenses
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as today_expenses FROM expenses WHERE DATE(exp_date) = CURDATE()");
    $stmt->execute();
    $today_expenses = $stmt->fetchColumn();

    // Today's purchases
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as today_purchases FROM purchase WHERE DATE(purchase_date) = CURDATE() AND status = 'completed'");
    $stmt->execute();
    $today_purchases = $stmt->fetchColumn();

    // Total expenses
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total_expenses FROM expenses");
    $total_expenses = $stmt->fetchColumn();

    // Total purchases
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total_purchases FROM purchase");
    $total_purchases = $stmt->fetchColumn();

    // Monthly sales trend (last 6 months)
    $stmt = $pdo->query("SELECT DATE_FORMAT(sale_date, '%Y-%m') as month, SUM(total_amount) as total FROM sale WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(sale_date, '%Y-%m') ORDER BY month");
    $monthly_sales = $stmt->fetchAll();

    // Recent sales with customer names
    $stmt = $pdo->query("SELECT s.*, COALESCE(c.name, s.walk_in_cust_name) as customer_name FROM sale s LEFT JOIN customer c ON s.customer_id = c.id ORDER BY s.created_at DESC LIMIT 5");
    $recent_sales = $stmt->fetchAll();

    // Low stock products
    $stmt = $pdo->query("SELECT p.product_name, p.alert_quantity, COALESCE(SUM(si.quantity), 0) as current_stock FROM products p LEFT JOIN stock_items si ON p.id = si.product_id AND si.status = 'available' GROUP BY p.id HAVING current_stock <= p.alert_quantity ORDER BY current_stock ASC LIMIT 5");
    $low_stock_products = $stmt->fetchAll();

    // Total customers
    $stmt = $pdo->query("SELECT COUNT(*) as total_customers FROM customer");
    $total_customers = $stmt->fetchColumn();

    // Total products
    $stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
    $total_products = $stmt->fetchColumn();

} catch (Exception $e) {
    // Handle errors gracefully
    $today_sales = 0;
    $month_sales = 0;
    $total_sales = 0;
    $stock_value = 0;
    $upcoming_deliveries = 0;
    $low_stock_count = 0;
    $today_expenses = 0;
    $today_purchases = 0;
    $total_expenses = 0;
    $total_purchases = 0;
    $monthly_sales = [];
    $recent_sales = [];
    $low_stock_products = [];
    $total_customers = 0;
    $total_products = 0;
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 " style=" padding-top: 20px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">
                    <i class="bi bi-speedometer2 text-warning me-2"></i>
                    Dashboard
                </h1>
                
            </div>

            <!-- Stats Cards Row 1 -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-cash-coin text-primary fs-4"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="card-title text-muted mb-1">Today's Sales</h6>
                                    <h4 class="mb-0 text-primary">PKR <?= number_format($today_sales, 2) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-success bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-graph-up text-success fs-4"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="card-title text-muted mb-1">This Month</h6>
                                    <h4 class="mb-0 text-success">PKR <?= number_format($month_sales, 2) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-info bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-box-seam text-info fs-4"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="card-title text-muted mb-1">Stock Value</h6>
                                    <h4 class="mb-0 text-info">PKR <?= number_format($stock_value, 2) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-people text-warning fs-4"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="card-title text-muted mb-1">Total Customers</h6>
                                    <h4 class="mb-0 text-warning"><?= number_format($total_customers) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards Row 2 -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-danger bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-exclamation-triangle text-danger fs-4"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="card-title text-muted mb-1">Low Stock Alerts</h6>
                                    <h4 class="mb-0 text-danger"><?= $low_stock_count ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-secondary bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-calendar-check text-secondary fs-4"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="card-title text-muted mb-1">Upcoming Deliveries</h6>
                                    <h4 class="mb-0 text-secondary"><?= $upcoming_deliveries ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-dark bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-box text-dark fs-4"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="card-title text-muted mb-1">Total Products</h6>
                                    <h4 class="mb-0 text-dark"><?= number_format($total_products) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-success bg-opacity-10 p-3 rounded">
                                        <i class="bi bi-calculator text-success fs-4"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="card-title text-muted mb-1">Net Profit</h6>
                                    <h4 class="mb-0 text-success">PKR <?= number_format($total_sales - $total_purchases - $total_expenses, 2) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily Books Summary -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-journal-text text-info me-2"></i>
                                Today's Daily Books Summary
                            </h5>
                            <a href="daily_books.php" class="btn btn-sm btn-outline-info">
                                <i class="bi bi-arrow-right me-1"></i>View Full Report
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center p-3 bg-primary bg-opacity-10 rounded">
                                        <div class="flex-shrink-0">
                                            <i class="bi bi-cash-coin text-primary fs-4"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1 text-muted">Today's Sales</h6>
                                            <h5 class="mb-0 text-primary">PKR <?= number_format($today_sales, 2) ?></h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center p-3 bg-success bg-opacity-10 rounded">
                                        <div class="flex-shrink-0">
                                            <i class="bi bi-cart-plus text-success fs-4"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1 text-muted">Today's Purchases</h6>
                                            <h5 class="mb-0 text-success">PKR <?= number_format($today_purchases ?? 0, 2) ?></h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center p-3 bg-warning bg-opacity-10 rounded">
                                        <div class="flex-shrink-0">
                                            <i class="bi bi-receipt text-warning fs-4"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1 text-muted">Today's Expenses</h6>
                                            <h5 class="mb-0 text-warning">PKR <?= number_format($today_expenses, 2) ?></h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center p-3 bg-info bg-opacity-10 rounded">
                                        <div class="flex-shrink-0">
                                            <i class="bi bi-graph-up-arrow text-info fs-4"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1 text-muted">Today's Profit</h6>
                                            <h5 class="mb-0 text-info">PKR <?= number_format(($today_sales - ($today_purchases ?? 0) - $today_expenses), 2) ?></h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Sales Chart -->
                <div class="col-xl-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-graph-up text-primary me-2"></i>
                                Sales Trend (Last 6 Months)
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="salesChart" height="100"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="col-xl-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-lightning text-warning me-2"></i>
                                Quick Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="add_sale.php" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-2"></i>New Sale
                                </a>
                                <a href="add_purchase.php" class="btn btn-success">
                                    <i class="bi bi-cart-plus me-2"></i>New Purchase
                                </a>
                                <a href="add_customer_ajax.php" class="btn btn-info">
                                    <i class="bi bi-person-plus me-2"></i>Add Customer
                                </a>
                                <a href="add_product.php" class="btn btn-warning">
                                    <i class="bi bi-box-seam me-2"></i>Add Product
                                </a>
                                <a href="expense_entry.php" class="btn btn-secondary">
                                    <i class="bi bi-receipt me-2"></i>Add Expense
                                </a>
                                <a href="daily_books.php" class="btn btn-info">
                                    <i class="bi bi-journal-text me-2"></i>Daily Books
                                </a>
                                <a href="company_settings.php" class="btn btn-outline-primary">
                                    <i class="bi bi-building me-2"></i>Company Settings
                                </a>
                                <a href="users.php" class="btn btn-outline-success">
                                    <i class="bi bi-people-fill me-2"></i>User Management
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mt-2">
                <!-- Recent Sales -->
                <div class="col-xl-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-clock-history text-info me-2"></i>
                                Recent Sales
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($recent_sales)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Invoice</th>
                                                <th>Customer</th>
                                                <th>Amount</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_sales as $sale): ?>
                                                <tr>
                                                    <td>
                                                        <a href="sale_details.php?id=<?= $sale['id'] ?>" class="text-decoration-none">
                                                            <?= htmlspecialchars($sale['sale_no'] ?? 'SALE-' . $sale['id']) ?>
                                                        </a>
                                                    </td>
                                                    <td><?= htmlspecialchars($sale['customer_name'] ?? 'Walk-in') ?></td>
                                                    <td class="text-success">PKR <?= number_format($sale['total_amount'], 2) ?></td>
                                                    <td><?= date('M j', strtotime($sale['sale_date'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center py-3">No recent sales</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Alerts -->
                <div class="col-xl-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                                Low Stock Alerts
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($low_stock_products)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Current Stock</th>
                                                <th>Alert Level</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($low_stock_products as $product): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($product['product_name']) ?></td>
                                                    <td>
                                                        <span class="badge bg-danger"><?= $product['current_stock'] ?></span>
                                                    </td>
                                                    <td><?= $product['alert_quantity'] ?></td>
                                                    <td>
                                                        <a href="add_purchase.php" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-cart-plus"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center py-3">All products are well stocked</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Chart.js for sales chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    // Prepare data for chart
    const months = <?= json_encode(array_column($monthly_sales, 'month')) ?>;
    const sales = <?= json_encode(array_column($monthly_sales, 'total')) ?>;
    
    // Format months for display
    const formattedMonths = months.map(month => {
        const date = new Date(month + '-01');
        return date.toLocaleDateString('en-US', { month: 'short', year: '2-digit' });
    });
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: formattedMonths,
            datasets: [{
                label: 'Monthly Sales (PKR)',
                data: sales,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'PKR ' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>