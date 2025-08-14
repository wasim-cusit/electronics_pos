<?php
// Start output buffering to prevent any accidental output
ob_start();

require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'reports';

// Sales summary
try {
    $sales = $pdo->query("SELECT * FROM sale")->fetchAll(PDO::FETCH_ASSOC);
    $total_sales = 0;
    $today_sales = 0;
    $month_sales = 0;
    foreach ($sales as $sale) {
        $total_sales += $sale['total_amount'];
        if ($sale['sale_date'] == date('Y-m-d')) $today_sales += $sale['total_amount'];
        if (date('Y-m', strtotime($sale['sale_date'])) == date('Y-m')) $month_sales += $sale['total_amount'];
    }
} catch (Exception $e) {
    $sales = [];
    $total_sales = 0;
    $today_sales = 0;
    $month_sales = 0;
}

// Purchases summary
try {
    $purchases = $pdo->query("SELECT * FROM purchase")->fetchAll(PDO::FETCH_ASSOC);
    $total_purchases = 0;
    $today_purchases = 0;
    $month_purchases = 0;
    foreach ($purchases as $purchase) {
        $total_purchases += $purchase['total_amount'];
        if ($purchase['purchase_date'] == date('Y-m-d')) $today_purchases += $purchase['total_amount'];
        if (date('Y-m', strtotime($purchase['purchase_date'])) == date('Y-m')) $month_purchases += $purchase['total_amount'];
    }
} catch (Exception $e) {
    $purchases = [];
    $total_purchases = 0;
    $today_purchases = 0;
    $month_purchases = 0;
}

// Expenses summary
try {
    $expenses = $pdo->query("SELECT * FROM expenses")->fetchAll(PDO::FETCH_ASSOC);
    $total_expenses = 0;
    $today_expenses = 0;
    $month_expenses = 0;
    foreach ($expenses as $expense) {
        $total_expenses += $expense['amount'];
        if ($expense['date'] == date('Y-m-d')) $today_expenses += $expense['amount'];
        if (date('Y-m', strtotime($expense['date'])) == date('Y-m')) $month_expenses += $expense['amount'];
    }
} catch (Exception $e) {
    $expenses = [];
    $total_expenses = 0;
    $today_expenses = 0;
    $month_expenses = 0;
}

// Low stock report - using stock_items table
try {
    $low_stock_products = $pdo->query("
        SELECT p.*, COALESCE(SUM(si.quantity), 0) as current_stock 
        FROM products p 
        LEFT JOIN stock_items si ON p.id = si.product_id AND si.status = 'available' 
        GROUP BY p.id 
        HAVING current_stock < 5 
        ORDER BY current_stock ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $low_stock_products = [];
}

// Get data for charts
// Monthly sales data for the last 6 months (default)
$monthly_sales_data = [];
$default_start = date('Y-m-d', strtotime('-6 months'));
$default_end = date('Y-m-d');

// Generate monthly sales data for default period
$current = new DateTime($default_start);
$end = new DateTime($default_end);
$end->modify('last day of this month');

while ($current <= $end) {
    $month = $current->format('Y-m');
    $month_name = $current->format('M Y');
    $monthly_sales = 0;
    
    foreach ($sales as $sale) {
        if (date('Y-m', strtotime($sale['sale_date'])) == $month) {
            $monthly_sales += $sale['total_amount'];
        }
    }
    
    $monthly_sales_data[] = [
        'month' => $month_name,
        'amount' => $monthly_sales,
        'year_month' => $month
    ];
    
    $current->modify('+1 month');
}

// Category-wise product count
try {
    $categories = $pdo->query("SELECT c.category, COUNT(p.id) as count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id, c.category")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [];
}

// Top selling products
$top_products = [];
try {
    $top_products = $pdo->query("SELECT p.product_name, SUM(si.quantity) as total_sold FROM products p LEFT JOIN sale_items si ON p.id = si.product_id GROUP BY p.id, p.product_name ORDER BY total_sold DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // If there's an error, use empty array
    $top_products = [];
}

include 'includes/header.php';

// Handle AJAX request for chart data
if (isset($_GET['ajax']) && $_GET['ajax'] === 'sales_chart') {
    // Ensure no output before this point
    ob_clean();
    
    try {
        $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-6 months'));
        $end_date = $_GET['end_date'] ?? date('Y-m-d');
        
        // Validate dates
        if (!strtotime($start_date) || !strtotime($end_date)) {
            throw new Exception('Invalid date format');
        }
        
        // Fetch fresh sales data for the AJAX request
        $sales_query = $pdo->query("SELECT * FROM sale")->fetchAll(PDO::FETCH_ASSOC);
        
        $monthly_sales_data = [];
        $current = new DateTime($start_date);
        $end = new DateTime($end_date);
        $end->modify('last day of this month');
        
        while ($current <= $end) {
            $month = $current->format('Y-m');
            $month_name = $current->format('M Y');
            $monthly_sales = 0;
            
            foreach ($sales_query as $sale) {
                if (date('Y-m', strtotime($sale['sale_date'])) == $month) {
                    $monthly_sales += $sale['total_amount'];
                }
            }
            
            $monthly_sales_data[] = [
                'month' => $month_name,
                'amount' => $monthly_sales
            ];
            
            $current->modify('+1 month');
        }
        
        // Clear any output buffers and set proper headers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        echo json_encode($monthly_sales_data);
        exit;
        
    } catch (Exception $e) {
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        http_response_code(500);
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
            <h2 class="mb-4">📊 Reports Dashboard</h2>
            
            <?php if (empty($sales) && empty($purchases) && empty($expenses)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>No Data Available:</strong> The reports are showing empty data. This could mean:
                    <ul class="mb-0 mt-2">
                        <li>No sales, purchases, or expenses have been recorded yet</li>
                        <li>Database tables are empty or not properly configured</li>
                        <li>Check if you have imported the database structure correctly</li>
                    </ul>
                </div>
            <?php endif; ?>

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
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">📈 Monthly Sales Trend</h5>
                                <div class="d-flex gap-2">
                                    <input type="date" id="startDate" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('-6 months')) ?>">
                                    <span class="align-self-center">to</span>
                                    <input type="date" id="endDate" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                                    <button type="button" class="btn btn-primary btn-sm" onclick="updateSalesChart()">
                                        <i class="bi bi-search"></i> Update
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 p-2 rounded me-2">
                                            <i class="bi bi-graph-up text-primary"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted">Selected Period Sales</small>
                                            <div class="fw-bold" id="periodSalesTotal">PKR <?= number_format(array_sum(array_column($monthly_sales_data, 'amount')), 2) ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-success bg-opacity-10 p-2 rounded me-2">
                                            <i class="bi bi-calendar-range text-success"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted">Period</small>
                                            <div class="fw-bold" id="selectedPeriod"><?= date('M Y', strtotime('-6 months')) ?> - <?= date('M Y') ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                        <div class="card-header">⚠️ Low Stock Products (Below 5 units)</div>
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
                                            <td><?= htmlspecialchars($product['product_name']) ?></td>
                                            <td><?= htmlspecialchars($product['category_id']) ?></td>
                                            <td><?= htmlspecialchars($product['product_unit']) ?></td>
                                            <td><span class="badge bg-danger"><?= htmlspecialchars($product['current_stock']) ?></span></td>
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
// Global chart variable for updates
let salesChart;

// Sales Trend Chart
const salesCtx = document.getElementById('salesChart').getContext('2d');
salesChart = new Chart(salesCtx, {
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
        labels: <?= json_encode(array_column($categories, 'category')) ?>,
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
            data: [<?= count($low_stock_products) ?>, <?php 
                try { 
                    echo $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn() - count($low_stock_products); 
                } catch (Exception $e) { 
                    echo 0; 
                } 
            ?>],
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

// Function to update sales chart based on date range
function updateSalesChart() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    if (!startDate || !endDate) {
        alert('Please select both start and end dates');
        return;
    }
    
    if (new Date(startDate) > new Date(endDate)) {
        alert('Start date cannot be after end date');
        return;
    }
    
    // Show loading state
    const updateBtn = document.querySelector('button[onclick="updateSalesChart()"]');
    const originalText = updateBtn.innerHTML;
    updateBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Loading...';
    updateBtn.disabled = true;
    
    // Fetch new chart data
    fetch(`reports.php?ajax=sales_chart&start_date=${startDate}&end_date=${endDate}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Response text:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            
            // Update chart data
            salesChart.data.labels = data.map(item => item.month);
            salesChart.data.datasets[0].data = data.map(item => item.amount);
            salesChart.update();
            
            // Update summary values
            const totalSales = data.reduce((sum, item) => sum + parseFloat(item.amount), 0);
            document.getElementById('periodSalesTotal').textContent = 'PKR ' + totalSales.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            
            const startDateValue = document.getElementById('startDate').value;
            const endDateValue = document.getElementById('endDate').value;
            const startMonth = new Date(startDateValue).toLocaleDateString('en-US', {month: 'short', year: 'numeric'});
            const endMonth = new Date(endDateValue).toLocaleDateString('en-US', {month: 'short', year: 'numeric'});
            document.getElementById('selectedPeriod').textContent = `${startMonth} - ${endMonth}`;
            
            // Show success message
            showNotification('Chart updated successfully!', 'success');
        })
        .catch(error => {
            console.error('Error updating chart:', error);
            showNotification('Error updating chart: ' + error.message, 'error');
        })
        .finally(() => {
            // Restore button state
            updateBtn.innerHTML = originalText;
            updateBtn.disabled = false;
        });
}

// Function to show notifications
function showNotification(message, type = 'info') {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 'alert-info';
    
    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}

// Add event listeners for date inputs
document.addEventListener('DOMContentLoaded', function() {
    // Allow Enter key to trigger chart update
    document.getElementById('startDate').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') updateSalesChart();
    });
    
    document.getElementById('endDate').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') updateSalesChart();
    });
    
    // Auto-update chart when dates change (optional - uncomment if you want this behavior)
    // document.getElementById('startDate').addEventListener('change', updateSalesChart);
    // document.getElementById('endDate').addEventListener('change', updateSalesChart);
});
</script>

<?php include 'includes/footer.php'; ?>