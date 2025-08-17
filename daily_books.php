<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

// Set active page for sidebar
$activePage = 'daily_books';

// Get date filter (default to today)
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get daily sales
$daily_sales = 0;
$sales_count = 0;
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count, SUM(total_amount) as total 
    FROM sale 
    WHERE DATE(sale_date) = ? AND status = 'completed'
");
$stmt->execute([$selected_date]);
$sales_data = $stmt->fetch();
if ($sales_data) {
    $sales_count = $sales_data['count'];
    $daily_sales = $sales_data['total'] ?: 0;
}

// Get daily purchases
$daily_purchases = 0;
$purchases_count = 0;
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count, SUM(total_amount) as total 
    FROM purchase 
    WHERE DATE(purchase_date) = ? AND status = 'completed'
");
$stmt->execute([$selected_date]);
$purchases_data = $stmt->fetch();
if ($purchases_data) {
    $purchases_count = $purchases_data['count'];
    $daily_purchases = $purchases_data['total'] ?: 0;
}

// Get daily expenses
$daily_expenses = 0;
$expenses_count = 0;
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count, SUM(amount) as total 
    FROM expenses 
    WHERE DATE(exp_date) = ?
");
$stmt->execute([$selected_date]);
$expenses_data = $stmt->fetch();
if ($expenses_data) {
    $expenses_count = $expenses_data['count'];
    $daily_expenses = $expenses_data['total'] ?: 0;
}

// Calculate daily profit
$daily_profit = $daily_sales - $daily_purchases - $daily_expenses;

// Get detailed sales for the day
$stmt = $pdo->prepare("
    SELECT s.*, c.name as customer_name 
    FROM sale s 
    LEFT JOIN customer c ON s.customer_id = c.id 
    WHERE DATE(s.sale_date) = ? AND s.status = 'completed'
    ORDER BY s.sale_date DESC
");
$stmt->execute([$selected_date]);
$sales_list = $stmt->fetchAll();

// Get detailed purchases for the day
$stmt = $pdo->prepare("
    SELECT p.*, s.supplier_name 
    FROM purchase p 
    LEFT JOIN supplier s ON p.supplier_id = s.id 
    WHERE DATE(p.purchase_date) = ? AND p.status = 'completed'
    ORDER BY p.purchase_date DESC
");
$stmt->execute([$selected_date]);
$purchases_list = $stmt->fetchAll();

// Get detailed expenses for the day
$stmt = $pdo->prepare("
    SELECT e.*, ec.expense_cat as category_name 
    FROM expenses e 
    LEFT JOIN expenses_category ec ON e.cat_id = ec.id 
    WHERE DATE(e.exp_date) = ?
    ORDER BY e.exp_date DESC
");
$stmt->execute([$selected_date]);
$expenses_list = $stmt->fetchAll();

// Get date range for navigation
$prev_date = date('Y-m-d', strtotime($selected_date . ' -1 day'));
$next_date = date('Y-m-d', strtotime($selected_date . ' +1 day'));
$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Books - Tailor Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .profit-positive { color: #28a745; }
        .profit-negative { color: #dc3545; }
        .profit-neutral { color: #6c757d; }
        .card {
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4" style="margin-top: 25px;">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-journal-text me-2"></i>Daily Books
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="?date=<?= $prev_date ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-chevron-left"></i> Previous
                            </a>
                            <a href="?date=<?= $today ?>" class="btn btn-sm btn-outline-primary">
                                Today
                            </a>
                            <a href="?date=<?= $next_date ?>" class="btn btn-sm btn-outline-secondary">
                                Next <i class="bi bi-chevron-right"></i>
                            </a>
                        </div>
                        <input type="date" class="form-control form-control-sm" value="<?= $selected_date ?>" 
                               onchange="window.location.href='?date=' + this.value" style="width: auto;">
                    </div>
                </div>

                <!-- Date Display -->
                <div class="alert alert-info">
                    <h5 class="mb-0">
                        <i class="bi bi-calendar-event me-2"></i>
                        Daily Summary for <?= date('l, F j, Y', strtotime($selected_date)) ?>
                    </h5>
                </div>

                <!-- Summary Cards -->
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
                                        <h6 class="card-title text-muted mb-1">Daily Sales</h6>
                                        <h4 class="mb-0 text-primary">Rs <?= number_format($daily_sales, 2) ?></h4>
                                        <small class="text-muted"><?= $sales_count ?> transactions</small>
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
                                            <i class="bi bi-cart-plus text-success fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="card-title text-muted mb-1">Daily Purchases</h6>
                                        <h4 class="mb-0 text-success">Rs <?= number_format($daily_purchases, 2) ?></h4>
                                        <small class="text-muted"><?= $purchases_count ?> transactions</small>
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
                                            <i class="bi bi-receipt text-warning fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="card-title text-muted mb-1">Daily Expenses</h6>
                                        <h4 class="mb-0 text-warning">Rs <?= number_format($daily_expenses, 2) ?></h4>
                                        <small class="text-muted"><?= $expenses_count ?> transactions</small>
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
                                            <i class="bi bi-graph-up-arrow text-info fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="card-title text-muted mb-1">Daily Profit</h6>
                                        <h4 class="mb-0 <?= $daily_profit > 0 ? 'profit-positive' : ($daily_profit < 0 ? 'profit-negative' : 'profit-neutral') ?>">
                                            Rs <?= number_format($daily_profit, 2) ?>
                                        </h4>
                                        <small class="text-muted">
                                            <?= $daily_profit > 0 ? 'Profit' : ($daily_profit < 0 ? 'Loss' : 'Break Even') ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Transactions -->
                <div class="row g-4">
                    <!-- Sales Details -->
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-transparent border-0 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="bi bi-cash-coin me-2"></i>Sales Details
                                </h6>
                                <span class="badge bg-primary"><?= $sales_count ?></span>
                            </div>
                            <div class="card-body">
                                <?php if (empty($sales_list)): ?>
                                    <p class="text-muted text-center py-3">No sales recorded for this date</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Invoice</th>
                                                    <th>Customer</th>
                                                    <th>Amount</th>
                                                    <th>Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($sales_list as $sale): ?>
                                                <tr>
                                                    <td>
                                                        <a href="sale_details.php?id=<?= $sale['id'] ?>" class="text-decoration-none">
                                                            <?= $sale['sale_no'] ?>
                                                        </a>
                                                    </td>
                                                    <td><?= $sale['customer_name'] ?: 'Walk-in' ?></td>
                                                    <td class="text-success">Rs <?= number_format($sale['total_amount'], 2) ?></td>
                                                    <td><?= date('H:i', strtotime($sale['sale_date'])) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Purchase Details -->
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-transparent border-0 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-success">
                                    <i class="bi bi-cart-plus me-2"></i>Purchase Details
                                </h6>
                                <span class="badge bg-success"><?= $purchases_count ?></span>
                            </div>
                            <div class="card-body">
                                <?php if (empty($purchases_list)): ?>
                                    <p class="text-muted text-center py-3">No purchases recorded for this date</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>PO Number</th>
                                                    <th>Supplier</th>
                                                    <th>Amount</th>
                                                    <th>Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($purchases_list as $purchase): ?>
                                                <tr>
                                                    <td>
                                                        <a href="purchase_details.php?id=<?= $purchase['id'] ?>" class="text-decoration-none">
                                                            <?= $purchase['purchase_no'] ?>
                                                        </a>
                                                    </td>
                                                    <td><?= $purchase['supplier_name'] ?></td>
                                                    <td class="text-danger">Rs <?= number_format($purchase['total_amount'], 2) ?></td>
                                                    <td><?= date('H:i', strtotime($purchase['purchase_date'])) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expenses Details -->
                <div class="row g-4 mt-2">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-transparent border-0 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-warning">
                                    <i class="bi bi-receipt me-2"></i>Expenses Details
                                </h6>
                                <span class="badge bg-warning text-dark"><?= $expenses_count ?></span>
                            </div>
                            <div class="card-body">
                                <?php if (empty($expenses_list)): ?>
                                    <p class="text-muted text-center py-3">No expenses recorded for this date</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Description</th>
                                                    <th>Category</th>
                                                    <th>Amount</th>
                                                    <th>Person</th>
                                                    <th>Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($expenses_list as $expense): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($expense['details']) ?></td>
                                                    <td>
                                                        <span class="badge bg-secondary">
                                                            <?= $expense['category_name'] ?: 'Uncategorized' ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-danger">Rs <?= number_format($expense['amount'], 2) ?></td>
                                                    <td><?= $expense['expense_person'] ?: 'N/A' ?></td>
                                                    <td><?= date('H:i', strtotime($expense['exp_date'])) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row g-4 mt-2">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-transparent border-0">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="bi bi-lightning me-2"></i>Quick Actions
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-3 col-sm-6">
                                        <a href="add_sale.php" class="btn btn-primary w-100">
                                            <i class="bi bi-plus-circle me-1"></i>Add Sale
                                        </a>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <a href="add_purchase.php" class="btn btn-success w-100">
                                            <i class="bi bi-plus-circle me-1"></i>Add Purchase
                                        </a>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <a href="expense_entry.php" class="btn btn-warning w-100">
                                            <i class="bi bi-plus-circle me-1"></i>Add Expense
                                        </a>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <a href="reports.php" class="btn btn-info w-100">
                                            <i class="bi bi-graph-up me-1"></i>View Reports
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh every 5 minutes to keep data current
        setInterval(function() {
            if (document.visibilityState === 'visible') {
                location.reload();
            }
        }, 300000); // 5 minutes
    </script>
</body>
</html>
