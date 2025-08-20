<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'supplier_payment_list';

// Handle Delete Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_payment'])) {
    $payment_id = $_POST['payment_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM supplier_payments WHERE id = ?");
        $stmt->execute([$payment_id]);
        header("Location: supplier_payment_list.php?success=deleted");
        exit;
    } catch (Exception $e) {
        $error = "Error deleting payment: " . $e->getMessage();
    }
}

// Search and Filter
$search = $_GET['search'] ?? '';
$supplier_filter = $_GET['supplier_filter'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(sp.reference_no LIKE ? OR sp.notes LIKE ? OR s.supplier_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($supplier_filter)) {
    $where_conditions[] = "sp.supplier_id = ?";
    $params[] = $supplier_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "sp.payment_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "sp.payment_date <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Fetch payments with supplier details
$query = "SELECT sp.*, s.supplier_name, s.supplier_contact 
          FROM supplier_payments sp 
          LEFT JOIN supplier s ON sp.supplier_id = s.id 
          $where_clause 
          ORDER BY sp.payment_date DESC, sp.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch suppliers for filter dropdown
$suppliers = $pdo->query("SELECT * FROM supplier ORDER BY supplier_name")->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4" style="margin-top: 25px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="bi bi-list-ul text-primary"></i> Supplier Payment List</h2>
                <div class="d-flex">
                    <a href="supplier_payment.php" class="btn btn-primary me-2">
                        <i class="bi bi-plus-circle"></i> Add Payment
                    </a>
                    <a href="supplier_ledger.php" class="btn btn-info">
                        <i class="bi bi-journal-text"></i> Supplier Ledger
                    </a>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php
                    if ($_GET['success'] === 'deleted') echo "Payment deleted successfully!";
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Search and Filter Section -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="bi bi-search me-2"></i>Search & Filter Supplier Payments
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search Payments</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="Search by reference, notes, supplier..." 
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="supplier_filter" class="form-label">Filter by Supplier</label>
                            <select class="form-select" id="supplier_filter" name="supplier_filter">
                                <option value="">All Suppliers</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?= $supplier['id'] ?>" <?= $supplier_filter == $supplier['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($supplier['supplier_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date_from" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" 
                                   value="<?= htmlspecialchars($date_from) ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="date_to" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" 
                                   value="<?= htmlspecialchars($date_to) ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="d-flex gap-2 w-100">
                                <button type="submit" class="btn btn-primary flex-fill">
                                    <i class="bi bi-search me-2"></i>Search
                                </button>
                                <a href="supplier_payment_list.php" class="btn btn-secondary flex-fill">
                                    <i class="bi bi-arrow-clockwise me-2"></i>Clear
                                </a>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Search Results Summary -->
                    <?php if (!empty($search) || !empty($supplier_filter) || !empty($date_from) || !empty($date_to)): ?>
                        <div class="mt-3 p-3 bg-info bg-opacity-10 border border-info rounded">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-info-circle text-info me-2"></i>
                                <div>
                                    <strong>Search Results:</strong>
                                    <?php if (!empty($search)): ?>
                                        <span class="badge bg-primary ms-2">Search: "<?= htmlspecialchars($search) ?>"</span>
                                    <?php endif; ?>
                                    <?php if (!empty($supplier_filter)): ?>
                                        <?php 
                                        $selected_supplier = array_filter($suppliers, function($s) use ($supplier_filter) { 
                                            return $s['id'] == $supplier_filter; 
                                        });
                                        $supplier_name = !empty($selected_supplier) ? reset($selected_supplier)['supplier_name'] : 'Unknown';
                                        ?>
                                        <span class="badge bg-success ms-2">Supplier: <?= htmlspecialchars($supplier_name) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($date_from) || !empty($date_to)): ?>
                                        <span class="badge bg-warning ms-2">
                                            Date Range: 
                                            <?= !empty($date_from) ? date('M d, Y', strtotime($date_from)) : 'Any' ?> 
                                            to 
                                            <?= !empty($date_to) ? date('M d, Y', strtotime($date_to)) : 'Any' ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="badge bg-secondary ms-2">Found: <?= count($payments) ?> payments</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payments Table -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-credit-card"></i> Payment Records</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($payments)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2">No payments found</p>
                            <a href="supplier_payment.php" class="btn btn-primary">Add First Payment</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Date</th>
                                        <th>Supplier</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Reference</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td><?= date('M d, Y', strtotime($payment['payment_date'])) ?></td>
                                                                                         <td>
                                                 <strong><?= htmlspecialchars($payment['supplier_name']) ?></strong>
                                                 <?php if ($payment['supplier_contact']): ?>
                                                     <br><small class="text-muted"><?= htmlspecialchars($payment['supplier_contact']) ?></small>
                                                 <?php endif; ?>
                                             </td>
                                                                                         <td>
                                                 <span class="badge bg-success">Rs.<?= number_format($payment['payment_amount'], 2) ?></span>
                                             </td>
                                            <td><?= htmlspecialchars($payment['payment_method']) ?></td>
                                            <td><?= htmlspecialchars($payment['reference_no'] ?: '-') ?></td>
                                            <td><?= htmlspecialchars($payment['notes'] ?: '-') ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="supplier_payment_details.php?id=<?= $payment['id'] ?>" class="btn btn-info btn-sm">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this payment?')">
                                                        <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                                                        <button type="submit" name="delete_payment" class="btn btn-danger btn-sm">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Summary -->
                        <div class="row mt-4">
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Total Payments</h5>
                                        <h3 class="text-primary"><?= count($payments) ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Total Amount</h5>
                                                                                 <h3 class="text-success">Rs.<?= number_format(array_sum(array_column($payments, 'payment_amount')), 2) ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">This Month</h5>
                                                                                 <h3 class="text-info">Rs.<?= number_format(array_sum(array_map(function($p) { 
                                             return date('Y-m') === date('Y-m', strtotime($p['payment_date'])) ? $p['payment_amount'] : 0; 
                                         }, $payments)), 2) ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
/* Search and filter section styling */
.card-header.bg-light {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
    border-bottom: 1px solid #dee2e6;
}

.search-results-summary {
    background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
    border: 1px solid #bee5eb;
    border-radius: 8px;
}

/* Enhanced form controls */
.form-control:focus, .form-select:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.input-group-text {
    background-color: #f8f9fa;
    border-color: #ced4da;
    color: #6c757d;
}

/* Enhanced table styling */
.table-hover tbody tr:hover {
    background-color: #f8f9fa;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.card {
    border-radius: 8px;
    border: 1px solid #e9ecef;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card-header {
    border-radius: 8px 8px 0 0 !important;
    border-bottom: 1px solid #e9ecef;
}

/* Enhanced button styles */
.btn {
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

/* Badge styling */
.badge {
    font-weight: 500;
    letter-spacing: 0.3px;
}

/* Summary cards enhancement */
.card.bg-light {
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.card.bg-light:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}

/* Responsive improvements */
@media (max-width: 768px) {
    .col-md-2, .col-md-3 {
        margin-bottom: 1rem;
    }
    
    .d-flex.gap-2 {
        flex-direction: column;
        width: 100%;
    }
    
    .d-flex.gap-2 .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
