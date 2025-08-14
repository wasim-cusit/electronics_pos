<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

// Filter parameters
$customer_filter = $_GET['customer_id'] ?? '';
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Today

// Fetch customers for filter dropdown
$customers = $pdo->query("SELECT id, name FROM customer ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Build query for customer balances
$balance_query = "
    SELECT 
        c.id,
        c.name,
        c.mobile,
        c.address,
        COALESCE(SUM(s.total_amount), 0) as total_sales,
        COALESCE(SUM(cp.paid), 0) as total_payments,
        (COALESCE(SUM(s.total_amount), 0) - COALESCE(SUM(cp.paid), 0)) as balance
    FROM customer c
    LEFT JOIN sale s ON c.id = s.customer_id
    LEFT JOIN customer_payment cp ON c.id = cp.customer_id
    GROUP BY c.id, c.name, c.mobile, c.address
    ORDER BY balance DESC
";

$customer_balances = $pdo->query($balance_query)->fetchAll(PDO::FETCH_ASSOC);

// If specific customer is selected, get detailed transactions
$selected_customer = null;
$transactions = [];
$opening_balance = 0;

if (!empty($customer_filter)) {
    // Get customer details
    $stmt = $pdo->prepare("SELECT * FROM customer WHERE id = ?");
    $stmt->execute([$customer_filter]);
    $selected_customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selected_customer) {
        // Get sales transactions
        $sales_stmt = $pdo->prepare("
            SELECT 
                'sale' as type,
                s.sale_date as date,
                s.total_amount as amount,
                'Sale' as description,
                s.id as reference_id,
                'debit' as entry_type
            FROM sale s 
            WHERE s.customer_id = ? AND s.sale_date BETWEEN ? AND ?
            ORDER BY s.sale_date
        ");
        $sales_stmt->execute([$customer_filter, $date_from, $date_to]);
        $sales = $sales_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get payment transactions
        $payments_stmt = $pdo->prepare("
            SELECT 
                'payment' as type,
                cp.payment_date as date,
                cp.paid as amount,
                CONCAT('Payment - ', cp.details) as description,
                cp.id as reference_id,
                'credit' as entry_type
            FROM customer_payment cp 
            WHERE cp.customer_id = ? AND cp.payment_date BETWEEN ? AND ?
            ORDER BY cp.payment_date
        ");
        $payments_stmt->execute([$customer_filter, $date_from, $date_to]);
        $payments = $payments_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Combine and sort transactions
        $transactions = array_merge($sales, $payments);
        usort($transactions, function($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });
        
        // Calculate opening balance (balance before the selected date range)
        $opening_stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(s.total_amount), 0) as total_sales,
                COALESCE(SUM(cp.paid), 0) as total_payments
            FROM customer c
            LEFT JOIN sale s ON c.id = s.customer_id AND s.sale_date < ?
            LEFT JOIN customer_payment cp ON c.id = cp.customer_id AND cp.payment_date < ?
            WHERE c.id = ?
        ");
        $opening_stmt->execute([$date_from, $date_from, $customer_filter]);
        $opening_data = $opening_stmt->fetch(PDO::FETCH_ASSOC);
        $opening_balance = $opening_data['total_sales'] - $opening_data['total_payments'];
    }
}

// Calculate summary totals
$total_customers = count($customer_balances);
$total_receivables = 0;
$total_payables = 0;

foreach ($customer_balances as $balance) {
    if ($balance['balance'] > 0) {
        $total_receivables += $balance['balance'];
    } else {
        $total_payables += abs($balance['balance']);
    }
}

$page_title = "Customer Ledger";
$activePage = "customer_ledger";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">📊 Customer Ledger</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="customer_payment.php" class="btn btn-primary me-2">
                        <i class="bi bi-plus-circle"></i> Add Payment
                    </a>
                    <a href="customer_payment_list.php" class="btn btn-outline-secondary">
                        <i class="bi bi-list-ul"></i> Payment List
                    </a>
                </div>
            </div>

            <?php include 'includes/flash.php'; ?>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Customers</h6>
                                    <h4 class="mb-0"><?= $total_customers ?></h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-people fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Receivables</h6>
                                    <h4 class="mb-0">PKR <?= number_format($total_receivables, 2) ?></h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-arrow-up-circle fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Payables</h6>
                                    <h4 class="mb-0">PKR <?= number_format($total_payables, 2) ?></h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-arrow-down-circle fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Net Balance</h6>
                                    <h4 class="mb-0">PKR <?= number_format($total_receivables - $total_payables, 2) ?></h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-calculator fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">🔍 Filters</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Customer</label>
                            <select name="customer_id" class="form-control" onchange="this.form.submit()">
                                <option value="">All Customers</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?= $customer['id'] ?>" <?= $customer_filter == $customer['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($customer['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($selected_customer): ?>
                <!-- Customer Ledger Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">📋 Ledger for <?= htmlspecialchars($selected_customer['name']) ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Customer:</strong> <?= htmlspecialchars($selected_customer['name']) ?><br>
                                <strong>Phone:</strong> <?= htmlspecialchars($selected_customer['mobile']) ?><br>
                                <strong>Address:</strong> <?= htmlspecialchars($selected_customer['address'] ?: 'N/A') ?>
                            </div>
                            <div class="col-md-6 text-end">
                                <strong>Period:</strong> <?= date('d/m/Y', strtotime($date_from)) ?> - <?= date('d/m/Y', strtotime($date_to)) ?><br>
                                <strong>Opening Balance:</strong> PKR <?= number_format($opening_balance, 2) ?>
                            </div>
                        </div>
                        
                        <?php if (empty($transactions)): ?>
                            <p class="text-muted text-center">No transactions found for the selected period.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Description</th>
                                            <th>Reference</th>
                                            <th class="text-end">Debit</th>
                                            <th class="text-end">Credit</th>
                                            <th class="text-end">Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $running_balance = $opening_balance;
                                        foreach ($transactions as $transaction): 
                                            if ($transaction['entry_type'] == 'debit') {
                                                $running_balance += $transaction['amount'];
                                            } else {
                                                $running_balance -= $transaction['amount'];
                                            }
                                        ?>
                                            <tr>
                                                <td><?= date('d/m/Y', strtotime($transaction['date'])) ?></td>
                                                <td><?= htmlspecialchars($transaction['description']) ?></td>
                                                <td>
                                                    <a href="<?= $transaction['type'] == 'sale' ? 'sale_details.php?id=' . $transaction['reference_id'] : 'customer_payment_details.php?id=' . $transaction['reference_id'] ?>" 
                                                       class="text-decoration-none">
                                                        #<?= $transaction['reference_id'] ?>
                                                    </a>
                                                </td>
                                                <td class="text-end">
                                                    <?php if ($transaction['entry_type'] == 'debit'): ?>
                                                        <span class="text-danger">PKR <?= number_format($transaction['amount'], 2) ?></span>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <?php if ($transaction['entry_type'] == 'credit'): ?>
                                                        <span class="text-success">PKR <?= number_format($transaction['amount'], 2) ?></span>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end fw-bold">
                                                    PKR <?= number_format($running_balance, 2) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Customer Balances Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">💰 Customer Balances</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Phone</th>
                                    <th>Total Sales</th>
                                    <th>Total Payments</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customer_balances as $balance): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($balance['name']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($balance['address'] ?: 'No address') ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($balance['mobile']) ?></td>
                                        <td class="text-success">PKR <?= number_format($balance['total_sales'], 2) ?></td>
                                        <td class="text-info">PKR <?= number_format($balance['total_payments'], 2) ?></td>
                                        <td class="fw-bold <?= $balance['balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                                            PKR <?= number_format(abs($balance['balance']), 2) ?>
                                            <?= $balance['balance'] > 0 ? '(Receivable)' : '(Payable)' ?>
                                        </td>
                                        <td>
                                            <?php if ($balance['balance'] > 0): ?>
                                                <span class="badge bg-warning">Outstanding</span>
                                            <?php elseif ($balance['balance'] < 0): ?>
                                                <span class="badge bg-info">Advance</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Settled</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="?customer_id=<?= $balance['id'] ?>" class="btn btn-outline-info" title="View Ledger">
                                                    <i class="bi bi-journal-text"></i>
                                                </a>
                                                <a href="customer_payment.php?customer_id=<?= $balance['id'] ?>" class="btn btn-outline-primary" title="Add Payment">
                                                    <i class="bi bi-plus-circle"></i>
                                                </a>
                                                <a href="customers.php?edit=<?= $balance['id'] ?>" class="btn btn-outline-secondary" title="Edit Customer">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
