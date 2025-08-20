<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

// Handle Add Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    $customer_id = $_POST['customer_id'];
    $amount = $_POST['amount'];
    $payment_date = $_POST['payment_date'];
    $payment_method_id = $_POST['payment_method_id'];
    $receipt = $_POST['receipt'];
    $details = $_POST['details'];

    // Validate input
    if (empty($customer_id) || empty($amount) || empty($payment_date) || empty($payment_method_id)) {
        $error = "All required fields must be filled.";
    } elseif ($amount <= 0) {
        $error = "Payment amount must be greater than 0.";
    } else {
    try {
        $pdo->beginTransaction();
            
            // Check for duplicate payment (same customer, amount, date, and receipt)
            if (!empty($receipt)) {
                $duplicate_check = $pdo->prepare("
                    SELECT COUNT(*) as count FROM customer_payment 
                    WHERE customer_id = ? AND paid = ? AND payment_date = ? AND receipt = ?
                ");
                $duplicate_check->execute([$customer_id, $amount, $payment_date, $receipt]);
                $duplicate_count = $duplicate_check->fetch()['count'];
                
                if ($duplicate_count > 0) {
                    throw new Exception("A payment with the same receipt number already exists for this customer on this date.");
                }
            }
        
        // Insert payment record
        $stmt = $pdo->prepare("INSERT INTO customer_payment (sale_id, customer_id, paid, payment_date, details, receipt, payment_method_id, created_at) VALUES (0, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$customer_id, $amount, $payment_date, $details, $receipt, $payment_method_id]);
        
        $pdo->commit();
        header("Location: customer_payment.php?success=added");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error adding payment: " . $e->getMessage();
        }
    }
}

// Fetch all customers with their current balances (improved calculation)
$customers_query = "
    SELECT 
        c.id,
        c.name,
        c.mobile,
        c.opening_balance,
        COALESCE(SUM(s.due_amount), 0) as total_dues,
        COALESCE(SUM(cp.paid), 0) as total_payments,
        (COALESCE(SUM(s.due_amount), 0) - COALESCE(SUM(cp.paid), 0) + COALESCE(c.opening_balance, 0)) as current_balance
    FROM customer c
    LEFT JOIN (
        SELECT customer_id, due_amount 
        FROM sale 
        WHERE customer_id IS NOT NULL 
        AND status != 'cancelled'
    ) s ON c.id = s.customer_id
    LEFT JOIN customer_payment cp ON c.id = cp.customer_id
    GROUP BY c.id, c.name, c.mobile, c.opening_balance
    ORDER BY c.name
";
$customers = $pdo->query($customers_query)->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent payments
$recent_payments = $pdo->query("
    SELECT cp.*, c.name as customer_name, pm.method as payment_method_name
    FROM customer_payment cp 
    LEFT JOIN customer c ON cp.customer_id = c.id 
    LEFT JOIN payment_method pm ON cp.payment_method_id = pm.id
    ORDER BY cp.payment_date DESC 
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_payments = 0;
$today_payments = 0;
$month_payments = 0;

foreach ($recent_payments as $payment) {
    $total_payments += $payment['paid'];
    if ($payment['payment_date'] == date('Y-m-d')) {
        $today_payments += $payment['paid'];
    }
    if (date('Y-m', strtotime($payment['payment_date'])) == date('Y-m')) {
        $month_payments += $payment['paid'];
    }
}

// Calculate total customer balances
$total_receivables = 0;
$total_customers_with_balance = 0;
foreach ($customers as $customer) {
    if ($customer['current_balance'] > 0) {
        $total_receivables += $customer['current_balance'];
        $total_customers_with_balance++;
    }
}

$page_title = "Customer Payment";
$activePage = "customer_payment";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">💰 Customer Payment</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="customer_payment_list.php" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-list-ul"></i> Payment List
                    </a>
                    <a href="customer_ledger.php" class="btn btn-outline-info">
                        <i class="bi bi-journal-text"></i> Customer Ledger
                    </a>
                </div>
            </div>

            <?php include 'includes/flash.php'; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['success']) && $_GET['success'] === 'added'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill"></i> Customer payment added successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Today's Payments</h6>
                                    <h4 class="mb-0">PKR <?= number_format($today_payments, 2) ?></h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-cash-coin fs-1"></i>
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
                                    <h6 class="card-title">This Month</h6>
                                    <h4 class="mb-0">PKR <?= number_format($month_payments, 2) ?></h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-calendar-month fs-1"></i>
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
                                    <h6 class="card-title">Total Receivables</h6>
                                    <h4 class="mb-0">PKR <?= number_format($total_receivables, 2) ?></h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-exclamation-triangle fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Customers with Balance</h6>
                                    <h4 class="mb-0"><?= $total_customers_with_balance ?></h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-people fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Add Payment Form -->
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-primary text-white py-3">
                            <h6 class="mb-0">
                                <i class="bi bi-credit-card me-2"></i>
                                Add Customer Payment
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <form method="POST" id="paymentForm" class="needs-validation" novalidate>
                                <!-- Customer Selection Section -->
                                <div class="form-section mb-3">
                                    <h6 class="text-primary mb-2">
                                        <i class="bi bi-person me-2"></i>Customer Information
                                    </h6>
                                    <div class="row">
                                        <div class="col-12 mb-2">
                                            <label class="form-label fw-semibold small">
                                                <i class="bi bi-person-badge me-1"></i>Select Customer 
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select name="customer_id" id="customerSelect" class="form-select" required>
                                                <option value="">Choose a customer...</option>
                                                <?php foreach ($customers as $customer): ?>
                                                    <option value="<?= $customer['id'] ?>" 
                                                            data-balance="<?= $customer['current_balance'] ?>"
                                                            data-name="<?= htmlspecialchars($customer['name']) ?>"
                                                            data-mobile="<?= htmlspecialchars($customer['mobile']) ?>">
                                                        <?= htmlspecialchars($customer['name']) ?> 
                                                        <span class="text-muted">- <?= htmlspecialchars($customer['mobile']) ?></span>
                                                        <span class="badge <?= $customer['current_balance'] > 0 ? 'bg-warning' : ($customer['current_balance'] < 0 ? 'bg-info' : 'bg-success') ?> float-end">
                                                                PKR <?= number_format(abs($customer['current_balance']), 2) ?>
                                                            </span>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="invalid-feedback small">Please select a customer.</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Customer Balance Display -->
                                    <div id="customerBalanceDisplay" style="display: none;" class="mt-2">
                                        <div class="alert border-0 shadow-sm py-2" id="balanceAlert">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-info-circle me-2"></i>
                                                <div class="small">
                                                    <strong class="d-block">Current Balance:</strong> 
                                                    <span id="currentBalanceAmount" class="fw-bold"></span>
                                                    <span id="balanceStatus" class="badge ms-2"></span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Remaining Balance After Payment -->
                                        <div class="alert alert-info border-0 shadow-sm mt-2 py-2" id="remainingBalanceDisplay" style="display: none;">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-calculator me-2"></i>
                                                <div class="small">
                                                    <strong class="d-block">After Payment:</strong> 
                                                    <span id="remainingBalanceAmount" class="fw-bold"></span>
                                                    <span id="remainingBalanceStatus" class="badge ms-2"></span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Payment Summary -->
                                        <div class="card border-0 shadow-sm mt-2" id="paymentSummaryCard" style="display: none;">
                                            <div class="card-body py-2">
                                                <h6 class="card-title mb-2 text-center text-primary small">
                                                    <i class="bi bi-receipt me-2"></i>Payment Summary
                                                </h6>
                                                <div class="row text-center">
                                                    <div class="col-4">
                                                        <div class="p-1 rounded bg-light">
                                                            <small class="text-muted d-block">Current Balance</small>
                                                            <strong id="summaryCurrentBalance" class="text-danger small"></strong>
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="p-1 rounded bg-light">
                                                            <small class="text-muted d-block">Payment Amount</small>
                                                            <strong id="summaryPaymentAmount" class="text-primary small"></strong>
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="p-1 rounded bg-light">
                                                            <small class="text-muted d-block">Remaining</small>
                                                            <strong id="summaryRemainingBalance" class="small"></strong>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Customer History Summary -->
                                        <div class="row mt-2" id="customerHistorySummary" style="display: none;">
                                            <div class="col-12">
                                                <div class="card border-0 bg-gradient-light">
                                                    <div class="card-body py-2">
                                                        <div class="row align-items-center text-center">
                                                            <div class="col-6">
                                                                <div class="d-flex align-items-center justify-content-center">
                                                                    <i class="bi bi-cart text-primary me-2 fs-5"></i>
                                                                    <div class="text-start">
                                                                        <small class="text-muted d-block fw-semibold">Recent Sales</small>
                                                                        <strong id="recentSalesInfo" class="text-primary small"></strong>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="d-flex align-items-center justify-content-center">
                                                                    <i class="bi bi-credit-card text-success me-2 fs-5"></i>
                                                                    <div class="text-start">
                                                                        <small class="text-muted d-block fw-semibold">Recent Payments</small>
                                                                        <strong id="recentPaymentsInfo" class="text-success small"></strong>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Details Section -->
                                <div class="form-section mb-3">
                                    <h6 class="text-primary mb-2">
                                        <i class="bi bi-credit-card me-2"></i>Payment Details
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label fw-semibold small">
                                                <i class="bi bi-currency-dollar me-1"></i>Payment Amount 
                                                <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light small">PKR</span>
                                                <input type="number" name="amount" id="paymentAmount" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
                                            </div>
                                            <div class="form-text text-muted small" id="paymentHelpText"></div>
                                            <div class="invalid-feedback small">Please enter a valid payment amount.</div>
                                            
                                            <!-- Quick Payment Buttons -->
                                            <div class="mt-2" id="quickPaymentButtons" style="display: none;">
                                                <small class="text-muted d-block mb-1 fw-semibold">Quick Payment:</small>
                                                <div class="d-flex gap-1 flex-wrap">
                                                    <button type="button" class="btn btn-outline-primary btn-sm" id="payFullBalance">
                                                        <i class="bi bi-check-all me-1"></i>Full
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="payHalfBalance">
                                                        <i class="bi bi-50-percent me-1"></i>Half
                                                    </button>
                                                    <button type="button" class="btn btn-outline-info btn-sm" id="payQuarterBalance">
                                                        <i class="bi bi-25-percent me-1"></i>Quarter
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label fw-semibold small">
                                                <i class="bi bi-calendar me-1"></i>Payment Date 
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                            <div class="invalid-feedback small">Please select a payment date.</div>
                                        </div>

                                        <div class="col-md-6 mb-2">
                                            <label class="form-label fw-semibold small">
                                                <i class="bi bi-credit-card-2-front me-1"></i>Payment Method 
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select name="payment_method_id" class="form-select" required>
                                                <option value="">Choose payment method...</option>
                                                <option value="1">💵 Cash</option>
                                                <option value="2">🌐 Online Banking</option>
                                                <option value="3">💳 Credit Card</option>
                                                <option value="4">💳 Debit Card</option>
                                                <option value="5">🏦 Bank Transfer</option>
                                                <option value="6">📱 Mobile Payment</option>
                                                <option value="7">📄 Check</option>
                                            </select>
                                            <div class="invalid-feedback small">Please select a payment method.</div>
                                        </div>

                                        <div class="col-md-6 mb-2">
                                            <label class="form-label fw-semibold small">
                                                <i class="bi bi-receipt me-1"></i>Receipt Number
                                            </label>
                                            <input type="text" name="receipt" class="form-control" placeholder="Optional receipt number">
                                        </div>
                                        
                                        <div class="col-12 mb-3">
                                            <label class="form-label fw-semibold small">
                                                <i class="bi bi-chat-text me-1"></i>Payment Notes
                                            </label>
                                            <textarea name="details" class="form-control" rows="2" placeholder="Optional details about the payment"></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Form Actions -->
                                <div class="form-actions text-center pt-2 border-top">
                                    <button type="submit" name="add_payment" class="btn btn-primary me-2" id="submitPaymentBtn">
                                        <i class="bi bi-plus-circle me-2"></i>Process Payment
                                    </button>
                                    <button type="reset" class="btn btn-outline-secondary" id="resetFormBtn">
                                        <i class="bi bi-arrow-clockwise me-2"></i>Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Customer Balances Summary -->
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-info text-white py-3">
                            <h6 class="mb-0">
                                <i class="bi bi-graph-up me-2"></i>Customer Balances
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <?php if (empty($customers)): ?>
                                <div class="text-center py-3">
                                    <i class="bi bi-inbox fs-4 text-muted"></i>
                                    <p class="text-muted mt-2 small">No customers found.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="border-0 small">Customer</th>
                                                <th class="border-0 text-end small">Balance</th>
                                                <th class="border-0 text-center small">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($customers as $customer): ?>
                                                <tr class="align-middle">
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-2">
                                                                <i class="bi bi-person text-primary small"></i>
                                                            </div>
                                                            <div>
                                                                <strong class="d-block small"><?= htmlspecialchars($customer['name']) ?></strong>
                                                                <small class="text-muted"><?= htmlspecialchars($customer['mobile']) ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="fw-bold small <?= $customer['current_balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                                                        PKR <?= number_format(abs($customer['current_balance']), 2) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if ($customer['current_balance'] > 0): ?>
                                                            <span class="badge bg-warning bg-opacity-75 small">Owes</span>
                                                        <?php elseif ($customer['current_balance'] < 0): ?>
                                                            <span class="badge bg-info bg-opacity-75 small">Credit</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success bg-opacity-75 small">Settled</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="customer_ledger.php" class="btn btn-outline-info btn-sm">
                                        <i class="bi bi-list-ul me-2"></i>View Ledger
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
/* Enhanced form styling */
.form-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 0.75rem;
    padding: 1rem;
    border: 1px solid #e9ecef;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.form-section h6 {
    border-bottom: 2px solid #dee2e6;
    padding-bottom: 0.25rem;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

/* Enhanced form controls */
.form-control, .form-select {
    border: 2px solid #e9ecef;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
}

.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    transform: translateY(-1px);
}

/* Enhanced input groups */
.input-group-text {
    border: 2px solid #e9ecef;
    border-right: none;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    font-weight: 600;
    color: #495057;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}

.input-group .form-control {
    border-left: none;
}

/* Enhanced labels */
.form-label {
    color: #495057;
    font-weight: 600;
    margin-bottom: 0.25rem;
    font-size: 0.875rem;
}

/* Enhanced buttons */
.btn {
    border-radius: 0.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.15);
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.8rem;
}

/* Enhanced alerts */
.alert {
    border-radius: 0.75rem;
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(10px);
    padding: 0.75rem;
}

.alert-info {
    background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
    color: #0c5460;
}

/* Enhanced cards */
.card {
    border-radius: 0.75rem;
    overflow: hidden;
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-1px);
    box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.1);
}

.card-header {
    border-bottom: none;
    padding: 0.75rem 1rem;
}

.card-body {
    padding: 1rem;
}

/* Enhanced table */
.table {
    border-radius: 0.5rem;
    overflow: hidden;
}

.table th {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: none;
    font-weight: 600;
    color: #495057;
    padding: 0.75rem 0.5rem;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.25px;
}

.table td {
    padding: 0.75rem 0.5rem;
    border-bottom: 1px solid #f1f3f4;
    vertical-align: middle;
}

.table tbody tr:hover {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    transform: scale(1.005);
    transition: all 0.2s ease;
}

/* Enhanced badges */
.badge {
    border-radius: 0.375rem;
    font-weight: 600;
    padding: 0.375rem 0.5rem;
    font-size: 0.7rem;
}

/* Avatar styling */
.avatar-sm {
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
}

/* Enhanced quick payment buttons */
#quickPaymentButtons .btn {
    border-radius: 0.375rem;
    font-size: 0.75rem;
    padding: 0.375rem 0.5rem;
    transition: all 0.2s ease;
}

#quickPaymentButtons .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.15);
}

/* Form validation styling */
.was-validated .form-control:valid,
.was-validated .form-select:valid {
    border-color: #198754;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='m2.3 6.73.94-.94 2.89 2.89 2.89-2.89.94.94L5.79 9.56z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.was-validated .form-control:invalid,
.was-validated .form-select:invalid {
    border-color: #dc3545;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 4.6 2.4 2.4m0-2.4L5.8 7'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

/* Responsive improvements */
@media (max-width: 768px) {
    .form-section {
        padding: 0.75rem;
    }
    
    .card-body {
        padding: 0.75rem;
    }
}

/* Animation for balance display */
#customerBalanceDisplay {
    animation: slideDown 0.3s ease-out;
    transition: opacity 0.3s ease;
}

/* Ensure balance display is always visible when shown */
#customerBalanceDisplay.show {
    display: block !important;
    opacity: 1 !important;
}

/* Balance alert styling */
#balanceAlert {
    margin-bottom: 0.5rem !important;
}

#currentBalanceAmount {
    font-size: 1rem !important;
    font-weight: 700 !important;
}

#balanceStatus {
    font-size: 0.75rem !important;
    padding: 0.375rem 0.5rem !important;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Enhanced payment summary */
#paymentSummaryCard .card-body {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    padding: 0.75rem;
}

#paymentSummaryCard .bg-light {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%) !important;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
}

/* Enhanced customer history */
#customerHistorySummary .card {
    border: 1px solid #e9ecef;
    transition: all 0.2s ease;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
}

#customerHistorySummary .card:hover {
    transform: translateY(-1px);
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.1);
}

#customerHistorySummary .bg-gradient-light {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%) !important;
}

#customerHistorySummary .card-body {
    padding: 0.75rem !important;
}

#customerHistorySummary .fs-5 {
    font-size: 1.1rem !important;
}

#customerHistorySummary .text-start {
    text-align: left !important;
}

#customerHistorySummary .fw-semibold {
    font-weight: 600 !important;
}

/* Form actions section */
.form-actions {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 0.75rem;
    margin-top: 0.75rem;
}

/* Enhanced select options */
.form-select option {
    padding: 0.375rem;
    border-radius: 0.25rem;
}

/* Focus states for better accessibility */
.form-control:focus,
.form-select:focus,
.btn:focus {
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Loading states */
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

/* Enhanced table responsiveness */
@media (max-width: 576px) {
    .table-responsive {
        border-radius: 0.5rem;
        overflow: hidden;
    }
    
    .table th,
    .table td {
        padding: 0.5rem 0.375rem;
        font-size: 0.75rem;
    }
}

/* Compact spacing utilities */
.mb-2 {
    margin-bottom: 0.5rem !important;
}

.mb-3 {
    margin-bottom: 1rem !important;
}

.mt-2 {
    margin-top: 0.5rem !important;
}

.mt-3 {
    margin-top: 1rem !important;
}

.py-2 {
    padding-top: 0.5rem !important;
    padding-bottom: 0.5rem !important;
}

.py-3 {
    padding-top: 0.75rem !important;
    padding-bottom: 0.75rem !important;
}

.p-3 {
    padding: 1rem !important;
}

/* Small text utilities */
.small {
    font-size: 0.875rem !important;
}

/* Compact form sections */
.form-section {
    margin-bottom: 1rem !important;
}

/* Compact card headers */
.card-header h6 {
    margin-bottom: 0 !important;
    font-size: 0.9rem !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const customerSelect = document.getElementById('customerSelect');
    const paymentAmount = document.getElementById('paymentAmount');
    const customerBalanceDisplay = document.getElementById('customerBalanceDisplay');
    const paymentHelpText = document.getElementById('paymentHelpText');
    const submitPaymentBtn = document.getElementById('submitPaymentBtn');
    const resetFormBtn = document.getElementById('resetFormBtn');

    // Enhanced form validation
    const form = document.getElementById('paymentForm');
    
    // Real-time validation
    paymentAmount.addEventListener('input', function() {
        validatePaymentAmount();
        if (customerSelect.value) {
            updatePaymentHelpText(this.value);
            updateRemainingBalance(this.value);
        }
    });

    // Add event listener for customer selection change
    customerSelect.addEventListener('change', function() {
        console.log('Customer changed to:', this.value);
        
        // Clear any existing validation states
        this.classList.remove('is-invalid', 'is-valid');
        
        if (this.value) {
            // Clear payment amount when changing customer
            paymentAmount.value = '';
            
            // Show balance and fetch history
            showCustomerBalance();
            fetchCustomerHistory(this.value);
            
            // Add valid state
            this.classList.add('is-valid');
        } else {
            hideCustomerBalance();
        }
    });

    // Also trigger on page load if a customer is pre-selected
    if (customerSelect.value) {
        showCustomerBalance();
        fetchCustomerHistory(customerSelect.value);
    }

    // Enhanced form submission
    form.addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        submitPaymentBtn.disabled = true;
        submitPaymentBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Processing...';
        
        // Add success animation
        submitPaymentBtn.classList.add('btn-success');
        
        return true;
    });

    // Enhanced reset functionality
    resetFormBtn.addEventListener('click', function() {
        // Add confirmation
        if (confirm('Are you sure you want to reset the form? All entered data will be lost.')) {
            resetForm();
        }
    });

    // Enhanced validation functions
    function validatePaymentAmount() {
        const amount = parseFloat(paymentAmount.value);
        const isValid = amount > 0;
        
        if (isValid) {
            paymentAmount.classList.remove('is-invalid');
            paymentAmount.classList.add('is-valid');
        } else {
            paymentAmount.classList.remove('is-valid');
            paymentAmount.classList.add('is-invalid');
        }
        
        return isValid;
    }

    function validateForm() {
        let isValid = true;
        
        // Validate customer selection
        if (!customerSelect.value) {
            customerSelect.classList.add('is-invalid');
            isValid = false;
        } else {
            customerSelect.classList.remove('is-invalid');
            customerSelect.classList.add('is-valid');
        }
        
        // Validate payment amount
        if (!validatePaymentAmount()) {
            isValid = false;
        }
        
        // Validate payment method
        const paymentMethod = document.querySelector('select[name="payment_method_id"]');
        if (!paymentMethod.value) {
            paymentMethod.classList.add('is-invalid');
            isValid = false;
        } else {
            paymentMethod.classList.remove('is-invalid');
            paymentMethod.classList.add('is-valid');
        }
        
        // Validate payment date
        const paymentDate = document.querySelector('input[name="payment_date"]');
        if (!paymentDate.value) {
            paymentDate.classList.add('is-invalid');
            isValid = false;
        } else {
            paymentDate.classList.remove('is-invalid');
            paymentDate.classList.add('is-valid');
        }
        
        return isValid;
    }

    function resetForm() {
        // Reset form validation states
        form.classList.remove('was-validated');
        document.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
            el.classList.remove('is-valid', 'is-invalid');
        });
        
        // Reset form fields
        form.reset();
        
        // Hide all dynamic displays
        hideCustomerBalance();
        
        // Reset button states
        submitPaymentBtn.disabled = false;
        submitPaymentBtn.innerHTML = '<i class="bi bi-plus-circle me-2"></i>Process Payment';
        submitPaymentBtn.classList.remove('btn-success');
        
        // Add reset animation
        resetFormBtn.classList.add('btn-success');
        setTimeout(() => {
            resetFormBtn.classList.remove('btn-success');
        }, 1000);
    }

    // Enhanced customer balance display
    function showCustomerBalance() {
        if (!customerSelect.value) {
            hideCustomerBalance();
            return;
        }
        
        const selectedOption = customerSelect.options[customerSelect.selectedIndex];
        const balance = parseFloat(selectedOption.dataset.balance) || 0;
        const customerName = selectedOption.dataset.name || '';
        const customerMobile = selectedOption.dataset.mobile || '';
        
        // Get DOM elements
        const balanceAlert = document.getElementById('balanceAlert');
        const currentBalanceAmount = document.getElementById('currentBalanceAmount');
        const balanceStatus = document.getElementById('balanceStatus');
        
        // Ensure all elements exist
        if (!balanceAlert || !currentBalanceAmount || !balanceStatus) {
            console.error('Balance display elements not found');
            return;
        }
    
        // Always show the balance display first
        customerBalanceDisplay.style.display = 'block';
        customerBalanceDisplay.classList.add('show');
        
        // Set balance amount with proper formatting
        currentBalanceAmount.textContent = `PKR ${Math.abs(balance).toFixed(2)}`;
            
        // Set alert styling and status based on balance
        if (balance > 0) {
            // Customer owes money
            balanceAlert.className = 'alert alert-warning border-0 shadow-sm py-2';
            balanceStatus.textContent = 'Owes Money';
            balanceStatus.className = 'badge bg-warning ms-2';
            currentBalanceAmount.className = 'fw-bold text-danger';
        } else if (balance < 0) {
            // Customer has credit
            balanceAlert.className = 'alert alert-info border-0 shadow-sm py-2';
            balanceStatus.textContent = 'Has Credit';
            balanceStatus.className = 'badge bg-info ms-2';
            currentBalanceAmount.className = 'fw-bold text-success';
        } else {
            // Customer is settled
            balanceAlert.className = 'alert alert-success border-0 shadow-sm py-2';
            currentBalanceAmount.textContent = 'PKR 0.00';
            balanceStatus.textContent = 'Settled';
            balanceStatus.className = 'badge bg-success ms-2';
            currentBalanceAmount.className = 'fw-bold text-success';
        }
        
        // Update payment help text if amount is entered
        const currentAmount = parseFloat(paymentAmount.value) || 0;
        if (currentAmount > 0) {
            updatePaymentHelpText(currentAmount);
            updateRemainingBalance(currentAmount);
        } else {
            paymentHelpText.innerHTML = '';
            document.getElementById('remainingBalanceDisplay').style.display = 'none';
            document.getElementById('paymentSummaryCard').style.display = 'none';
        }
        
        // Show/hide quick payment buttons with animation
        const quickPaymentButtons = document.getElementById('quickPaymentButtons');
        if (quickPaymentButtons) {
            if (balance > 0) {
                quickPaymentButtons.style.display = 'block';
                quickPaymentButtons.style.opacity = '0';
                setTimeout(() => {
                    quickPaymentButtons.style.transition = 'opacity 0.3s ease';
                    quickPaymentButtons.style.opacity = '1';
                }, 100);
            } else {
                quickPaymentButtons.style.display = 'none';
            }
        }
        
        // Add success animation to balance display
        customerBalanceDisplay.style.opacity = '0';
        setTimeout(() => {
            customerBalanceDisplay.style.transition = 'opacity 0.3s ease';
            customerBalanceDisplay.style.opacity = '1';
        }, 50);
        
        console.log('Balance displayed:', balance, 'for customer:', customerName);
    }

    function hideCustomerBalance() {
        customerBalanceDisplay.style.display = 'none';
        customerBalanceDisplay.classList.remove('show');
        paymentHelpText.innerHTML = '';
        document.getElementById('customerHistorySummary').style.display = 'none';
        document.getElementById('remainingBalanceDisplay').style.display = 'none';
        document.getElementById('paymentSummaryCard').style.display = 'none';
        
        // Clear customer history data
        document.getElementById('recentSalesInfo').textContent = '';
        document.getElementById('recentPaymentsInfo').textContent = '';
        
        // Clear balance display data
        document.getElementById('currentBalanceAmount').textContent = '';
        document.getElementById('balanceStatus').textContent = '';
        
        // Hide quick payment buttons
        const quickPaymentButtons = document.getElementById('quickPaymentButtons');
        if (quickPaymentButtons) {
            quickPaymentButtons.style.display = 'none';
        }
        
        console.log('Balance display hidden');
    }

    function fetchCustomerHistory(customerId) {
        const historySummary = document.getElementById('customerHistorySummary');
        if (!historySummary) return;
        
        // Clear previous data first
        document.getElementById('recentSalesInfo').textContent = 'Loading...';
        document.getElementById('recentPaymentsInfo').textContent = 'Loading...';
        
        historySummary.style.display = 'block';
        historySummary.style.opacity = '0.7';
        
        // Make AJAX call to get customer history
        fetch(`get_customer_history.php?customer_id=${customerId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const summary = data.data.summary;
                    
                    // Format sales info with proper amount handling
                    const salesAmount = Math.abs(summary.total_sales);
                    const salesText = summary.sales_count === 1 ? 'sale' : 'sales';
                    document.getElementById('recentSalesInfo').textContent = 
                        `${summary.sales_count} ${salesText} (PKR ${salesAmount.toFixed(2)})`;
                    
                    // Format payments info with proper amount handling
                    const paymentsAmount = Math.abs(summary.total_payments);
                    const paymentsText = summary.payments_count === 1 ? 'payment' : 'payments';
                    document.getElementById('recentPaymentsInfo').textContent = 
                        `${summary.payments_count} ${paymentsText} (PKR ${paymentsAmount.toFixed(2)})`;
                    
                    // Add success animation
                    historySummary.style.opacity = '1';
                    historySummary.style.transition = 'opacity 0.3s ease';
                } else {
                    document.getElementById('recentSalesInfo').textContent = 'No data';
                    document.getElementById('recentPaymentsInfo').textContent = 'No data';
                    historySummary.style.opacity = '1';
                }
            })
            .catch(error => {
                console.error('Error fetching customer history:', error);
                document.getElementById('recentSalesInfo').textContent = 'Error loading data';
                document.getElementById('recentPaymentsInfo').textContent = 'Error loading data';
                historySummary.style.opacity = '1';
            });
    }

    function updatePaymentHelpText(amount) {
        if (!customerSelect.value) {
            paymentHelpText.innerHTML = '';
            return;
        }
        
        const selectedOption = customerSelect.options[customerSelect.selectedIndex];
        const balance = parseFloat(selectedOption.dataset.balance) || 0;
        
        if (amount <= 0) {
            paymentHelpText.innerHTML = '';
            submitPaymentBtn.disabled = true;
            return;
        }
        
        submitPaymentBtn.disabled = false;
        
        if (balance > 0) {
            // Customer owes money
            const remaining = balance - amount;
            if (remaining < 0) {
                paymentHelpText.innerHTML = `<span class="text-warning">⚠️ Payment exceeds balance. Customer will have credit of PKR ${Math.abs(remaining).toFixed(2)}</span>`;
            } else if (remaining === 0) {
                paymentHelpText.innerHTML = `<span class="text-success">✅ Payment will settle the balance completely</span>`;
            } else {
                paymentHelpText.innerHTML = `<span class="text-info">ℹ️ Remaining balance after payment: PKR ${remaining.toFixed(2)}</span>`;
            }
        } else if (balance < 0) {
            // Customer has credit
            paymentHelpText.innerHTML = `<span class="text-info">ℹ️ Customer already has credit. This payment will increase their credit balance</span>`;
        } else {
            // Customer is settled
            paymentHelpText.innerHTML = `<span class="text-info">ℹ️ Recording payment of PKR ${amount.toFixed(2)}</span>`;
        }
    }

    function updateRemainingBalance(amount) {
        if (!customerSelect.value) {
            document.getElementById('remainingBalanceDisplay').style.display = 'none';
            document.getElementById('paymentSummaryCard').style.display = 'none';
            return;
        }

        const selectedOption = customerSelect.options[customerSelect.selectedIndex];
        const balance = parseFloat(selectedOption.dataset.balance) || 0;

        if (balance > 0 && amount > 0) {
            const remaining = balance - amount;
            
            // Update remaining balance display
            document.getElementById('remainingBalanceAmount').textContent = `PKR ${remaining.toFixed(2)}`;
            if (remaining < 0) {
                document.getElementById('remainingBalanceStatus').textContent = 'Has Credit';
                document.getElementById('remainingBalanceStatus').className = 'badge bg-info ms-2 fs-6';
                document.getElementById('remainingBalanceAmount').className = 'fw-bold fs-5 text-success';
            } else if (remaining === 0) {
                document.getElementById('remainingBalanceStatus').textContent = 'Settled';
                document.getElementById('remainingBalanceStatus').className = 'badge bg-success ms-2 fs-6';
                document.getElementById('remainingBalanceAmount').className = 'fw-bold fs-5 text-success';
            } else {
                document.getElementById('remainingBalanceStatus').textContent = 'Owes Money';
                document.getElementById('remainingBalanceStatus').className = 'badge bg-warning ms-2 fs-6';
                document.getElementById('remainingBalanceAmount').className = 'fw-bold fs-5 text-danger';
            }
            document.getElementById('remainingBalanceDisplay').style.display = 'block';
            
            // Update payment summary
            document.getElementById('summaryCurrentBalance').textContent = `PKR ${balance.toFixed(2)}`;
            document.getElementById('summaryPaymentAmount').textContent = `PKR ${amount.toFixed(2)}`;
            document.getElementById('summaryRemainingBalance').textContent = `PKR ${remaining.toFixed(2)}`;
            
            // Set colors for remaining balance
            if (remaining < 0) {
                document.getElementById('summaryRemainingBalance').className = 'text-success fs-6';
            } else if (remaining === 0) {
                document.getElementById('summaryRemainingBalance').className = 'text-success fs-6';
            } else {
                document.getElementById('summaryRemainingBalance').className = 'text-danger fs-6';
            }
            
            document.getElementById('paymentSummaryCard').style.display = 'block';
        } else {
            document.getElementById('remainingBalanceDisplay').style.display = 'none';
            document.getElementById('paymentSummaryCard').style.display = 'none';
        }
    }

    // Enhanced quick payment buttons
    document.getElementById('payFullBalance').addEventListener('click', function() {
        const selectedOption = customerSelect.options[customerSelect.selectedIndex];
        const balance = parseFloat(selectedOption.dataset.balance) || 0;
        if (balance > 0) {
            paymentAmount.value = balance.toFixed(2);
            updatePaymentHelpText(balance);
            updateRemainingBalance(balance);
            paymentAmount.focus();
        }
    });

    document.getElementById('payHalfBalance').addEventListener('click', function() {
        const selectedOption = customerSelect.options[customerSelect.selectedIndex];
        const balance = parseFloat(selectedOption.dataset.balance) || 0;
        if (balance > 0) {
            const halfAmount = balance / 2;
            paymentAmount.value = halfAmount.toFixed(2);
            updatePaymentHelpText(halfAmount);
            updateRemainingBalance(halfAmount);
            paymentAmount.focus();
        }
    });

    document.getElementById('payQuarterBalance').addEventListener('click', function() {
        const selectedOption = customerSelect.options[customerSelect.selectedIndex];
        const balance = parseFloat(selectedOption.dataset.balance) || 0;
        if (balance > 0) {
            const quarterAmount = balance / 4;
            paymentAmount.value = quarterAmount.toFixed(2);
            updatePaymentHelpText(quarterAmount);
            updateRemainingBalance(quarterAmount);
            paymentAmount.focus();
        }
    });

    // Enhanced form validation on blur
    document.querySelectorAll('input, select').forEach(input => {
        input.addEventListener('blur', function() {
            if (this.hasAttribute('required') && !this.value) {
                this.classList.add('is-invalid');
            } else if (this.hasAttribute('required') && this.value) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
    });

    // Add form validation class on first submit attempt
    form.addEventListener('submit', function() {
        form.classList.add('was-validated');
    });

    // Enhanced accessibility
    document.querySelectorAll('.form-control, .form-select').forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const nextInput = this.parentElement.nextElementSibling?.querySelector('input, select, textarea');
                if (nextInput) {
                    nextInput.focus();
                }
            }
        });
    });

    // Auto-format payment amount
    paymentAmount.addEventListener('blur', function() {
        if (this.value) {
            const amount = parseFloat(this.value);
            if (!isNaN(amount)) {
                this.value = amount.toFixed(2);
            }
        }
    });

    // Enhanced mobile experience
    if (window.innerWidth <= 768) {
        document.querySelectorAll('.form-control-lg, .form-select-lg').forEach(input => {
            input.classList.remove('form-control-lg', 'form-select-lg');
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
