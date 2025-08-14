<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

// Get payment ID
$payment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$payment_id) {
    header("Location: customer_payment_list.php?error=invalid_id");
    exit;
}

// Fetch payment details
try {
    $stmt = $pdo->prepare("
        SELECT cp.*, c.name as customer_name, c.mobile as customer_phone, c.address as customer_address, 
               c.email as customer_email, pm.method as payment_method_name
        FROM customer_payment cp 
        LEFT JOIN customer c ON cp.customer_id = c.id 
        LEFT JOIN payment_method pm ON cp.payment_method_id = pm.id
        WHERE cp.id = ?
    ");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        header("Location: customer_payment_list.php?error=payment_not_found");
        exit;
    }
} catch (Exception $e) {
    header("Location: customer_payment_list.php?error=database_error");
    exit;
}

// Fetch customer's payment history
$stmt = $pdo->prepare("
    SELECT cp.*, pm.method as payment_method_name
    FROM customer_payment cp 
    LEFT JOIN payment_method pm ON cp.payment_method_id = pm.id
    WHERE cp.customer_id = ? AND cp.id != ? 
    ORDER BY cp.payment_date DESC 
    LIMIT 10
");
$stmt->execute([$payment['customer_id'], $payment_id]);
$payment_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Payment Details - " . $payment['customer_name'];
$activePage = "customer_payment_details";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">💳 Payment Details</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="customer_payment_list.php" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                    <a href="customer_payment.php?edit=<?= $payment['id'] ?>" class="btn btn-warning me-2">
                        <i class="bi bi-pencil"></i> Edit Payment
                    </a>
                    <a href="customer_ledger.php?customer_id=<?= $payment['customer_id'] ?>" class="btn btn-outline-info">
                        <i class="bi bi-journal-text"></i> Customer Ledger
                    </a>
                </div>
            </div>

            <?php include 'includes/flash.php'; ?>

            <div class="row">
                <!-- Payment Details -->
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">💰 Payment Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="fw-bold">Payment ID:</td>
                                            <td>#<?= $payment['id'] ?></td>
                                        </tr>
                                        <tr>
                                                                                         <td class="fw-bold">Amount:</td>
                                             <td class="h4 text-success">PKR <?= number_format($payment['paid'], 2) ?></td>
                                        </tr>
                                                                                 <tr>
                                             <td class="fw-bold">Payment Date:</td>
                                             <td><?= date('d/m/Y', strtotime($payment['payment_date'])) ?></td>
                                         </tr>
                                         <tr>
                                             <td class="fw-bold">Payment Method:</td>
                                             <td><span class="badge bg-secondary"><?= htmlspecialchars($payment['payment_method_name'] ?: 'N/A') ?></span></td>
                                         </tr>
                                     </table>
                                 </div>
                                 <div class="col-md-6">
                                    <table class="table table-borderless">
                                                                                 <tr>
                                             <td class="fw-bold">Receipt:</td>
                                             <td><?= htmlspecialchars($payment['receipt'] ?: 'N/A') ?></td>
                                         </tr>
                                        
                                        <tr>
                                            <td class="fw-bold">Created At:</td>
                                            <td><?= date('d/m/Y H:i', strtotime($payment['created_at'])) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Status:</td>
                                            <td><span class="badge bg-success">Completed</span></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <?php if (!empty($payment['details'])): ?>
                                <div class="mt-3">
                                    <h6>Details:</h6>
                                    <p class="text-muted"><?= htmlspecialchars($payment['details']) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Customer Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">👤 Customer Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="fw-bold">Name:</td>
                                            <td><?= htmlspecialchars($payment['customer_name']) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Phone:</td>
                                            <td><?= htmlspecialchars($payment['customer_phone']) ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="fw-bold">Email:</td>
                                            <td><?= htmlspecialchars($payment['customer_email'] ?: 'N/A') ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Address:</td>
                                            <td><?= htmlspecialchars($payment['customer_address'] ?: 'N/A') ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-md-4">
                    <!-- Quick Actions -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">⚡ Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="customer_payment.php?customer_id=<?= $payment['customer_id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle"></i> Add Another Payment
                                </a>
                                <a href="customers.php?edit=<?= $payment['customer_id'] ?>" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-person-gear"></i> Edit Customer
                                </a>
                                <a href="customer_history.php?customer_id=<?= $payment['customer_id'] ?>" class="btn btn-outline-info btn-sm">
                                    <i class="bi bi-clock-history"></i> Customer History
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Summary -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">📊 Payment Summary</h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center">
                                <div class="h3 text-success mb-2">PKR <?= number_format($payment['paid'], 2) ?></div>
                                <p class="text-muted mb-0">Payment Amount</p>
                            </div>
                            <hr>
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="h6 text-primary"><?= count($payment_history) ?></div>
                                    <small class="text-muted">Previous Payments</small>
                                </div>
                                <div class="col-6">
                                                                    <div class="h6 text-info"><?= $payment['payment_method_name'] ?: 'N/A' ?></div>
                                <small class="text-muted">Method</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment History -->
            <?php if (!empty($payment_history)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">📋 Recent Payment History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Receipt</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payment_history as $hist_payment): ?>
                                        <tr>
                                            <td><?= date('d/m/Y', strtotime($hist_payment['payment_date'])) ?></td>
                                            <td class="fw-bold text-success">PKR <?= number_format($hist_payment['paid'], 2) ?></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($hist_payment['payment_method_name'] ?: '-') ?></span></td>
                                            <td><?= htmlspecialchars($hist_payment['receipt'] ?: '-') ?></td>
                                            <td>
                                                <a href="customer_payment_details.php?id=<?= $hist_payment['id'] ?>" class="btn btn-outline-info btn-sm">
                                                    <i class="bi bi-eye"></i>
                                                </a>
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
