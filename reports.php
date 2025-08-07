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

// Get data for charts
// Monthly sales data for the last 6 months
$monthly_sales_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $month_name = date('M Y', strtotime("-$i months"));
    $monthly_sales = 0;
    foreach ($sales as $sale) {
        if (date('Y-m', strtotime($sale['sale_date'])) == $month) {
            $monthly_sales += $sale['total_amount'];
        }
    }
    $monthly_sales_data[] = [
        'month' => $month_name,
        'amount' => $monthly_sales
    ];
}

// Category-wise product count
$categories = $pdo->query("SELECT c.name, COUNT(p.id) as count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id, c.name")->fetchAll(PDO::FETCH_ASSOC);

// Top selling products (if you have sales_details table)
$top_products = [];
try {
    $top_products = $pdo->query("SELECT p.name, SUM(sd.quantity) as total_sold FROM products p LEFT JOIN sales_details sd ON p.id = sd.product_id GROUP BY p.id, p.name ORDER BY total_sold DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // If sales_details table doesn't exist, use dummy data
    $top_products = [];
}

include 'includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
            <h2 class="mb-4">📊 Reports Dashboard</h2>

            <!-- Summary Cards -->
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
                <div class="col-md-3">
                    <div class="card text-bg-info">
                        <div class="card-body">
                            <h5 class="card-title">Net Profit</h5>
                            <p class="card-text display-6">PKR <?= number_format($total_sales - $total_purchases - $total_expenses, 2) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row 1 -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">📈 Monthly Sales Trend (Last 6 Months)</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="salesChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">💰 Financial Overview</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="financialChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row 2 -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">📦 Product Categories Distribution</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">📊 Sales vs Purchases vs Expenses</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="comparisonChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Low Stock Report with Chart -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">⚠️ Low Stock Products (Below 5)</div>
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
                                            <td><span class="badge bg-danger"><?= htmlspecialchars($product['stock_quantity']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($low_stock_products)): ?>
                                        <tr><td colspan="4" class="text-center text-success">✅ No low stock products.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">📊 Stock Levels Overview</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="stockChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Sales Trend Chart
const salesCtx = document.getElementById('salesChart').getContext('2d');
new Chart(salesCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($monthly_sales_data, 'month')) ?>,
        datasets: [{
            label: 'Sales (PKR)',
            data: <?= json_encode(array_column($monthly_sales_data, 'amount')) ?>,
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: 'Monthly Sales Trend'
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

// Financial Overview Chart
const financialCtx = document.getElementById('financialChart').getContext('2d');
new Chart(financialCtx, {
    type: 'doughnut',
    data: {
        labels: ['Sales', 'Purchases', 'Expenses'],
        datasets: [{
            data: [<?= $total_sales ?>, <?= $total_purchases ?>, <?= $total_expenses ?>],
            backgroundColor: [
                'rgba(75, 192, 192, 0.8)',
                'rgba(255, 99, 132, 0.8)',
                'rgba(255, 205, 86, 0.8)'
            ],
            borderColor: [
                'rgba(75, 192, 192, 1)',
                'rgba(255, 99, 132, 1)',
                'rgba(255, 205, 86, 1)'
            ],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': PKR ' + context.parsed.toLocaleString();
                    }
                }
            }
        }
    }
});

// Category Distribution Chart
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_column($categories, 'name')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($categories, 'count')) ?>,
            backgroundColor: [
                'rgba(255, 99, 132, 0.8)',
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 205, 86, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(153, 102, 255, 0.8)',
                'rgba(255, 159, 64, 0.8)'
            ],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'right',
            },
            title: {
                display: true,
                text: 'Products by Category'
            }
        }
    }
});

// Comparison Chart
const comparisonCtx = document.getElementById('comparisonChart').getContext('2d');
new Chart(comparisonCtx, {
    type: 'bar',
    data: {
        labels: ['Today', 'This Month', 'Total'],
        datasets: [{
            label: 'Sales',
            data: [<?= $today_sales ?>, <?= $month_sales ?>, <?= $total_sales ?>],
            backgroundColor: 'rgba(75, 192, 192, 0.8)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
        }, {
            label: 'Purchases',
            data: [<?= $today_purchases ?>, <?= $month_purchases ?>, <?= $total_purchases ?>],
            backgroundColor: 'rgba(255, 99, 132, 0.8)',
            borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 1
        }, {
            label: 'Expenses',
            data: [<?= $today_expenses ?>, <?= $month_expenses ?>, <?= $total_expenses ?>],
            backgroundColor: 'rgba(255, 205, 86, 0.8)',
            borderColor: 'rgba(255, 205, 86, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: 'Sales vs Purchases vs Expenses'
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

// Stock Levels Chart
const stockCtx = document.getElementById('stockChart').getContext('2d');
new Chart(stockCtx, {
    type: 'bar',
    data: {
        labels: ['Low Stock (<5)', 'Normal Stock (≥5)'],
        datasets: [{
            label: 'Products',
            data: [<?= count($low_stock_products) ?>, <?= $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity >= 5")->fetchColumn() ?>],
            backgroundColor: [
                'rgba(255, 99, 132, 0.8)',
                'rgba(75, 192, 192, 0.8)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(75, 192, 192, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            },
            title: {
                display: true,
                text: 'Stock Status'
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>