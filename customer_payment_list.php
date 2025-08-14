<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

// Handle Delete Payment
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM customer_payment WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: customer_payment_list.php?success=deleted");
        exit;
    } catch (Exception $e) {
        $error = "Error deleting payment: " . $e->getMessage();
    }
}

// Filter parameters
$search = $_GET['search'] ?? '';
$customer_filter = $_GET['customer_id'] ?? '';

$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(c.name LIKE ? OR cp.receipt LIKE ? OR cp.details LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($customer_filter)) {
    $where_conditions[] = "cp.customer_id = ?";
    $params[] = $customer_filter;
}



if (!empty($date_from)) {
    $where_conditions[] = "cp.payment_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "cp.payment_date <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Fetch payments with filters
$query = "
    SELECT cp.*, c.name as customer_name, c.mobile as customer_phone, pm.method as payment_method_name
    FROM customer_payment cp 
    LEFT JOIN customer c ON cp.customer_id = c.id 
    LEFT JOIN payment_method pm ON cp.payment_method_id = pm.id
    $where_clause
    ORDER BY cp.payment_date DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch customers for filter dropdown
$customers = $pdo->query("SELECT id, name FROM customer ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_amount = 0;
$filtered_count = count($payments);

foreach ($payments as $payment) {
    $total_amount += $payment['paid'];
}

$page_title = "Customer Payment List";
$activePage = "customer_payment_list";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">📋 Customer Payment List</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="customer_payment.php" class="btn btn-primary me-2">
                        <i class="bi bi-plus-circle"></i> Add Payment
                    </a>
                    <a href="customer_ledger.php" class="btn btn-outline-info">
                        <i class="bi bi-journal-text"></i> Customer Ledger
                    </a>
                </div>
            </div>

            <?php include 'includes/flash.php'; ?>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Payments</h6>
                                    <h4 class="mb-0">PKR <?= number_format($total_amount, 2) ?></h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-wallet2 fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Filtered Results</h6>
                                    <h4 class="mb-0"><?= $filtered_count ?> payments</h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-funnel fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Average Payment</h6>
                                    <h4 class="mb-0">PKR <?= $filtered_count > 0 ? number_format($total_amount / $filtered_count, 2) : '0.00' ?></h4>
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
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Customer, Receipt, Details...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Customer</label>
                            <select name="customer_id" class="form-control">
                                <option value="">All Customers</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?= $customer['id'] ?>" <?= $customer_filter == $customer['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($customer['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    <?php if (!empty($search) || !empty($customer_filter) || !empty($date_from) || !empty($date_to)): ?>
                        <div class="mt-3">
                            <a href="customer_payment_list.php" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-x-circle"></i> Clear Filters
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payments Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">💳 Payment Records</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($payments)): ?>
                        <p class="text-muted text-center">No payments found matching your criteria.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Details</th>
                                        <th>Receipt</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td><?= date('d/m/Y', strtotime($payment['payment_date'])) ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($payment['customer_name']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($payment['customer_phone']) ?></small>
                                            </td>
                                            <td class="fw-bold text-success">PKR <?= number_format($payment['paid'], 2) ?></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($payment['payment_method_name'] ?: '-') ?></span></td>
                                            <td><?= htmlspecialchars($payment['receipt'] ?: '-') ?></td>
                                            <td><?= htmlspecialchars($payment['details'] ?: '-') ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="customer_payment_details.php?id=<?= $payment['id'] ?>" class="btn btn-outline-info" title="View Details">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="customer_payment.php?edit=<?= $payment['id'] ?>" class="btn btn-outline-warning" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="?delete=<?= $payment['id'] ?>" class="btn btn-outline-danger" title="Delete" 
                                                       onclick="return confirm('Are you sure you want to delete this payment?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
