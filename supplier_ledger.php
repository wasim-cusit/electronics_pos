<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'supplier_ledger';

// Get supplier filter from URL
$supplier_filter = isset($_GET['supplier_id']) ? intval($_GET['supplier_id']) : 0;

// Search and Filter
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query for suppliers
$where_conditions = [];
$params = [];

if (!empty($search)) {
            $where_conditions[] = "(s.supplier_name LIKE ? OR s.supplier_contact LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Fetch suppliers with financial summary
try {
    $query = "
        SELECT 
            s.*,
            COALESCE(SUM(p.total_amount), 0) as total_purchases,
            COALESCE(SUM(sp.payment_amount), 0) as total_payments,
            (COALESCE(SUM(p.total_amount), 0) - COALESCE(SUM(sp.payment_amount), 0) + COALESCE(s.opening_balance, 0)) as balance
        FROM supplier s
        LEFT JOIN purchase p ON s.id = p.supplier_id
        LEFT JOIN supplier_payments sp ON s.id = sp.supplier_id
        $where_clause
        GROUP BY s.id
        ORDER BY s.supplier_name
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // If supplier_payments table doesn't exist, fetch suppliers without financial data
    $query = "
        SELECT 
            s.*,
            COALESCE(SUM(p.total_amount), 0) as total_purchases,
            0 as total_payments,
            (COALESCE(SUM(p.total_amount), 0) + COALESCE(s.opening_balance, 0)) as balance
        FROM supplier s
        LEFT JOIN purchase p ON s.id = p.supplier_id
        $where_clause
        GROUP BY s.id
        ORDER BY s.supplier_name
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// If specific supplier is selected, get detailed transactions
$selected_supplier = null;
$transactions = [];
if ($supplier_filter) {
    $stmt = $pdo->prepare("SELECT * FROM supplier WHERE id = ?");
    $stmt->execute([$supplier_filter]);
    $selected_supplier = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selected_supplier) {
        // Calculate financial summary for selected supplier
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    COALESCE(SUM(p.total_amount), 0) as total_purchases,
                    COALESCE(SUM(sp.payment_amount), 0) as total_payments,
                    (COALESCE(SUM(p.total_amount), 0) - COALESCE(SUM(sp.payment_amount), 0) + COALESCE(?, 0)) as balance
                FROM supplier s
                LEFT JOIN purchase p ON s.id = p.supplier_id
                LEFT JOIN supplier_payments sp ON s.id = sp.supplier_id
                WHERE s.id = ?
            ");
            $stmt->execute([$selected_supplier['opening_balance'] ?? 0, $supplier_filter]);
            $financial_summary = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Merge financial data with supplier data
            $selected_supplier = array_merge($selected_supplier, $financial_summary);
            
            // Ensure all required fields exist with default values
            $selected_supplier['total_purchases'] = $selected_supplier['total_purchases'] ?? 0;
            $selected_supplier['total_payments'] = $selected_supplier['total_payments'] ?? 0;
            $selected_supplier['balance'] = $selected_supplier['balance'] ?? 0;
        } catch (Exception $e) {
            // If query fails (e.g., table doesn't exist), set default values
            $selected_supplier['total_purchases'] = 0;
            $selected_supplier['total_payments'] = 0;
            $selected_supplier['balance'] = $selected_supplier['opening_balance'] ?? 0;
        }
        // Get purchases
        $stmt = $pdo->prepare("
            SELECT 
                'Purchase' as type,
                p.purchase_date as date,
                p.total_amount as amount,
                p.id as reference_id,
                CONCAT('Purchase #', p.id) as description,
                p.created_at
            FROM purchase p 
            WHERE p.supplier_id = ?
            ORDER BY p.purchase_date DESC
        ");
        $stmt->execute([$supplier_filter]);
        $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get payments
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    'Payment' as type,
                    sp.payment_date as date,
                    -sp.payment_amount as amount,
                    sp.id as reference_id,
                    CONCAT('Payment #', sp.id, ' - ', sp.payment_method) as description,
                    sp.created_at
                FROM supplier_payments sp 
                WHERE sp.supplier_id = ?
                ORDER BY sp.payment_date DESC
            ");
            $stmt->execute([$supplier_filter]);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // If supplier_payments table doesn't exist, set empty payments
            $payments = [];
        }
        
        // Add opening balance as first transaction
        $opening_balance = $selected_supplier['opening_balance'] ?? 0;
        if ($opening_balance != 0) {
            $opening_transaction = [
                'type' => 'Opening Balance',
                'date' => 'Opening Balance',
                'amount' => $opening_balance,
                'reference_id' => 0,
                'description' => 'Opening Balance',
                'created_at' => null
            ];
            $transactions = array_merge([$opening_transaction], $purchases, $payments);
        } else {
            $transactions = array_merge($purchases, $payments);
        }
        
        // Sort transactions by date (Opening Balance first, then by date)
        usort($transactions, function($a, $b) {
            if ($a['type'] === 'Opening Balance') return -1;
            if ($b['type'] === 'Opening Balance') return 1;
            if ($a['type'] === 'Opening Balance' || $b['type'] === 'Opening Balance') return 0;
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        // Calculate running balance starting from opening balance
        $balance = $opening_balance; // Start with opening balance
        foreach ($transactions as &$transaction) {
            if ($transaction['type'] !== 'Opening Balance') {
                $balance += $transaction['amount'];
            }
            $transaction['running_balance'] = $balance;
            
            // Ensure all required fields exist
            $transaction['type'] = $transaction['type'] ?? 'Unknown';
            $transaction['date'] = $transaction['date'] ?? 'Unknown';
            $transaction['amount'] = $transaction['amount'] ?? 0;
            $transaction['description'] = $transaction['description'] ?? '';
            $transaction['running_balance'] = $transaction['running_balance'] ?? 0;
        }
    }
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 " style="margin-top: 25px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="bi bi-journal-text text-primary"></i> Supplier Ledger</h2>
                <div class="d-flex">
                    <a href="supplier_payment.php" class="btn btn-primary me-2">
                        <i class="bi bi-plus-circle"></i> Add Payment
                    </a>
                    <a href="supplier_payment_list.php" class="btn btn-info">
                        <i class="bi bi-list-ul"></i> Payment List
                    </a>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="bi bi-search me-2"></i>Search & Filter Suppliers
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search Suppliers</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="Search by supplier name or contact..." 
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" 
                                   value="<?= htmlspecialchars($date_from) ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" 
                                   value="<?= htmlspecialchars($date_to) ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="d-flex gap-2 w-100">
                                <button type="submit" class="btn btn-primary flex-fill">
                                    <i class="bi bi-search me-2"></i>Search
                                </button>
                                <a href="supplier_ledger.php" class="btn btn-secondary flex-fill">
                                    <i class="bi bi-arrow-clockwise me-2"></i>Clear
                                </a>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Search Results Summary -->
                    <?php if (!empty($search) || !empty($date_from) || !empty($date_to)): ?>
                        <div class="mt-3 p-3 bg-info bg-opacity-10 border border-info rounded">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-info-circle text-info me-2"></i>
                                <div>
                                    <strong>Search Results:</strong>
                                    <?php if (!empty($search)): ?>
                                        <span class="badge bg-primary ms-2">Search: "<?= htmlspecialchars($search) ?>"</span>
                                    <?php endif; ?>
                                    <?php if (!empty($date_from) || !empty($date_to)): ?>
                                        <span class="badge bg-warning ms-2">
                                            Date Range: 
                                            <?= !empty($date_from) ? date('M d, Y', strtotime($date_from)) : 'Any' ?> 
                                            to 
                                            <?= !empty($date_to) ? date('M d, Y', strtotime($date_to)) : 'Any' ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="badge bg-secondary ms-2">Found: <?= count($suppliers) ?> suppliers</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($supplier_filter && $selected_supplier): ?>
                <!-- Individual Supplier Ledger -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-truck"></i> 
                            <?= htmlspecialchars($selected_supplier['supplier_name']) ?> - Ledger
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-2">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Opening Balance</h6>
                                                                                 <h4 class="<?= ($selected_supplier['opening_balance'] ?? 0) > 0 ? 'text-danger' : 'text-success' ?>">
                                             Rs.<?= number_format(abs($selected_supplier['opening_balance'] ?? 0), 2) ?>
                                             <?= ($selected_supplier['opening_balance'] ?? 0) > 0 ? '(Owed)' : '(Credit)' ?>
                                         </h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Total Purchases</h6>
                                                                                 <h4 class="text-danger">Rs.<?= number_format($selected_supplier['total_purchases'], 2) ?></h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Total Payments</h6>
                                                                                 <h4 class="text-success">Rs.<?= number_format($selected_supplier['total_payments'], 2) ?></h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Current Balance</h6>
                                                                                 <h4 class="<?= $selected_supplier['balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                                             Rs.<?= number_format(abs($selected_supplier['balance']), 2) ?>
                                             <?= $selected_supplier['balance'] > 0 ? '(Owed)' : '(Credit)' ?>
                                         </h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Actions</h6>
                                        <a href="supplier_payment.php?supplier_id=<?= $selected_supplier['id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="bi bi-plus-circle"></i> Add Payment
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Transactions Table -->
                        <h6>Transaction History</h6>
                        <?php if (empty($transactions)): ?>
                            <p class="text-muted">No transactions found for this supplier.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Description</th>
                                            <th>Amount</th>
                                            <th>Balance</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($transactions as $transaction): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($transaction['type'] === 'Opening Balance'): ?>
                                                        <?= htmlspecialchars($transaction['date']) ?>
                                                    <?php else: ?>
                                                        <?= date('M d, Y', strtotime($transaction['date'])) ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $transaction['type'] == 'Purchase' ? 'bg-danger' : ($transaction['type'] == 'Payment' ? 'bg-success' : 'bg-info') ?>">
                                                        <?= $transaction['type'] ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($transaction['description']) ?></td>
                                                <td>
                                                    <span class="<?= $transaction['amount'] > 0 ? 'text-danger' : 'text-success' ?>">
                                                        <?= $transaction['amount'] > 0 ? '+' : '' ?><?= number_format($transaction['amount'], 2) ?>
                                                    </span>
                                                </td>
                                                                                                 <td>
                                                     <span class="<?= ($transaction['running_balance'] ?? 0) > 0 ? 'text-danger' : 'text-success' ?>">
                                                         Rs.<?= number_format($transaction['running_balance'] ?? 0, 2) ?>
                                                     </span>
                                                 </td>
                                                <td>
                                                    <?php if ($transaction['type'] == 'Purchase'): ?>
                                                        <a href="purchase_details.php?id=<?= $transaction['reference_id'] ?>" class="btn btn-info btn-sm">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    <?php elseif ($transaction['type'] == 'Payment'): ?>
                                                        <a href="supplier_payment_details.php?id=<?= $transaction['reference_id'] ?>" class="btn btn-info btn-sm">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Suppliers Summary Table -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-truck"></i> Suppliers Financial Summary</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($suppliers)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2">No suppliers found</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Supplier</th>
                                            <th>Contact</th>
                                            <th>Total Purchases</th>
                                            <th>Total Payments</th>
                                            <th>Balance</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($suppliers as $supplier): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($supplier['supplier_name']) ?></strong>
                                                </td>
                                                <td>
                                                                                <?php if ($supplier['supplier_contact']): ?>
                                <?= htmlspecialchars($supplier['supplier_contact']) ?>
                            <?php endif; ?>
                                                </td>
                                                                                                 <td>
                                                     <span class="text-danger">Rs.<?= number_format($supplier['total_purchases'], 2) ?></span>
                                                 </td>
                                                 <td>
                                                     <span class="text-success">Rs.<?= number_format($supplier['total_payments'], 2) ?></span>
                                                 </td>
                                                 <td>
                                                     <span class="badge <?= $supplier['balance'] > 0 ? 'bg-danger' : 'bg-success' ?>">
                                                         Rs.<?= number_format(abs($supplier['balance']), 2) ?>
                                                         <?= $supplier['balance'] > 0 ? '(Owed)' : '(Credit)' ?>
                                                     </span>
                                                 </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="supplier_ledger.php?supplier_id=<?= $supplier['id'] ?>" class="btn btn-info btn-sm">
                                                            <i class="bi bi-journal-text"></i> Ledger
                                                        </a>
                                                        <a href="supplier_payment.php?supplier_id=<?= $supplier['id'] ?>" class="btn btn-primary btn-sm">
                                                            <i class="bi bi-plus-circle"></i> Payment
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Summary Cards -->
                            <div class="row mt-4">
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="card-title">Total Suppliers</h5>
                                            <h3 class="text-primary"><?= count($suppliers) ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="card-title">Total Purchases</h5>
                                            <h3 class="text-danger">Rs.<?= number_format(array_sum(array_column($suppliers, 'total_purchases')), 2) ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="card-title">Total Payments</h5>
                                            <h3 class="text-success">Rs.<?= number_format(array_sum(array_column($suppliers, 'total_payments')), 2) ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="card-title">Net Balance</h5>
                                            <h3 class="<?= array_sum(array_column($suppliers, 'balance')) > 0 ? 'text-danger' : 'text-success' ?>">
                                                Rs.<?= number_format(abs(array_sum(array_column($suppliers, 'balance'))), 2) ?>
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
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
    .col-md-2, .col-md-3, .col-md-4 {
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
