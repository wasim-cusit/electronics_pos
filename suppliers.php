<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'suppliers';





// Handle Delete Supplier
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM supplier WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: suppliers.php?success=deleted");
    exit;
}

// Handle search functionality
$search = $_GET['search'] ?? '';
$balance_filter = $_GET['balance_filter'] ?? '';

// Build the suppliers query with search filters
$suppliers_query = "SELECT * FROM supplier WHERE 1=1";

$params = [];

if (!empty($search)) {
    $suppliers_query .= " AND (supplier_name LIKE ? OR supplier_contact LIKE ? OR supplier_email LIKE ? OR supplier_address LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if ($balance_filter !== '') {
    switch ($balance_filter) {
        case 'owe':
            $suppliers_query .= " AND opening_balance > 0";
            break;
        case 'owed':
            $suppliers_query .= " AND opening_balance < 0";
            break;
        case 'zero':
            $suppliers_query .= " AND (opening_balance = 0 OR opening_balance IS NULL)";
            break;
    }
}

$suppliers_query .= " ORDER BY supplier_name";

// Execute the query with parameters
$stmt = $pdo->prepare($suppliers_query);
$stmt->execute($params);
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);



include 'includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 " style="margin-top: 25px;">

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php
                    if ($_GET['success'] === 'added') echo "Supplier added successfully!";
                    if ($_GET['success'] === 'deleted') echo "Supplier deleted successfully!";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Add Supplier Button -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">👥 Suppliers</h2>
                <button type="button" class="btn btn-primary" onclick="openAddSupplierModal()">
                    <i class="bi bi-plus-circle"></i> Add Supplier
                </button>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Suppliers</h6>
                                    <h4 class="mb-0"><?= count($suppliers) ?></h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-people fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-danger">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">You Owe</h6>
                                    <h4 class="mb-0">Rs.<?= number_format(array_sum(array_map(function($s) { return max(0, $s['opening_balance'] ?? 0); }, $suppliers)), 2) ?></h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-arrow-up-circle fs-1"></i>
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
                                    <h6 class="card-title">You're Owed</h6>
                                    <h4 class="mb-0">Rs.<?= number_format(abs(array_sum(array_map(function($s) { return min(0, $s['opening_balance'] ?? 0); }, $suppliers))), 2) ?></h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-arrow-down-circle fs-1"></i>
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
                                    <h6 class="card-title">Net Balance</h6>
                                    <h4 class="mb-0">Rs.<?= number_format(array_sum(array_map(function($s) { return $s['opening_balance'] ?? 0; }, $suppliers)), 2) ?></h4>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-calculator fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
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
                                       placeholder="Search by name, contact, email, or address..." 
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="balance_filter" class="form-label">Filter by Balance</label>
                            <select class="form-select" id="balance_filter" name="balance_filter">
                                <option value="">All Balances</option>
                                <option value="owe" <?= ($balance_filter === 'owe') ? 'selected' : '' ?>>You Owe (Positive Balance)</option>
                                <option value="owed" <?= ($balance_filter === 'owed') ? 'selected' : '' ?>>You're Owed (Negative Balance)</option>
                                <option value="zero" <?= ($balance_filter === 'zero') ? 'selected' : '' ?>>No Balance (Zero)</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search me-2"></i>Search
                                </button>
                                <a href="suppliers.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-clockwise me-2"></i>Clear
                                </a>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Search Results Summary -->
                    <?php if (!empty($search) || $balance_filter !== ''): ?>
                        <div class="mt-3 p-3 bg-info bg-opacity-10 border border-info rounded">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-info-circle text-info me-2"></i>
                                <div>
                                    <strong>Search Results:</strong>
                                    <?php if (!empty($search)): ?>
                                        <span class="badge bg-primary ms-2">Search: "<?= htmlspecialchars($search) ?>"</span>
                                    <?php endif; ?>
                                    <?php if ($balance_filter !== ''): ?>
                                        <span class="badge bg-success ms-2">
                                            Balance Filter: 
                                            <?php 
                                            switch($balance_filter) {
                                                case 'owe': echo 'You Owe'; break;
                                                case 'owed': echo 'You\'re Owed'; break;
                                                case 'zero': echo 'No Balance'; break;
                                            }
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="badge bg-secondary ms-2">Found: <?= count($suppliers) ?> suppliers</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Supplier List Table -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul"></i> Supplier List
                        <span class="badge bg-primary ms-2"><?= count($suppliers) ?> suppliers</span>
                    </h5>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Opening Balance (Outstanding)</th>
                                <th>Address</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($suppliers as $supplier): ?>
                                <tr>
                                    <td><?= htmlspecialchars($supplier['supplier_name']) ?></td>
                                    <td><?= htmlspecialchars($supplier['supplier_contact']) ?></td>
                                    <td><?= htmlspecialchars($supplier['supplier_email']) ?></td>
                                    <td>
                                        <?php 
                                        $balance = $supplier['opening_balance'] ?? 0;
                                        if ($balance > 0): ?>
                                            <span class="badge bg-danger">
                                                Rs.<?= number_format($balance, 2) ?> (You Owe)
                                            </span>
                                        <?php elseif ($balance < 0): ?>
                                            <span class="badge bg-success">
                                                Rs.<?= number_format(abs($balance), 2) ?> (You're Owed)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                Rs.0.00 (No Balance)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($supplier['supplier_address']) ?></td>
                                    <td><?= htmlspecialchars($supplier['created_at']) ?></td>
                                    <td>
                                        <a href="supplier_ledger.php?supplier_id=<?= $supplier['id'] ?>" class="btn btn-sm btn-info">Ledger</a>
                                        <a href="suppliers.php?delete=<?= $supplier['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this supplier?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($suppliers)): ?>
                                <tr><td colspan="7" class="text-center">No suppliers found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1" aria-labelledby="addSupplierModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSupplierModalLabel">
                    <i class="bi bi-plus-circle"></i> Add New Supplier
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addSupplierForm">
                    <!-- CSRF Protection -->
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="supplierName" class="form-label">Supplier Name *</label>
                            <input type="text" class="form-control" id="supplierName" name="supplier_name" required placeholder="Enter supplier name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="supplierContact" class="form-label">Contact Number</label>
                            <input type="text" class="form-control" id="supplierContact" name="supplier_contact" placeholder="Enter contact number">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="supplierEmail" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="supplierEmail" name="supplier_email" placeholder="Enter email address">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="supplierOpeningBalance" class="form-label">Opening Balance (Rs.)</label>
                            <input type="number" class="form-control" id="supplierOpeningBalance" name="opening_balance" step="0.01" placeholder="0.00">
                            <small class="text-muted">
                                <strong>Positive value:</strong> You owe money to supplier (Red badge)<br>
                                <strong>Negative value:</strong> Supplier owes you money (Green badge)<br>
                                <strong>Zero:</strong> No outstanding balance
                            </small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="supplierAddress" class="form-label">Address</label>
                        <textarea class="form-control" id="supplierAddress" name="supplier_address" rows="3" placeholder="Enter supplier address"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" onclick="saveSupplier()">
                    <i class="bi bi-check-circle"></i> Add Supplier
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Notification function to replace alerts
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
    
    // Allow manual close
    notification.querySelector('.btn-close').addEventListener('click', () => {
        notification.remove();
    });
}

// Supplier modal functionality
function openAddSupplierModal() {
    // Clear the form
    document.getElementById('addSupplierForm').reset();
    // Show the modal
    new bootstrap.Modal(document.getElementById('addSupplierModal')).show();
}

function saveSupplier() {
    const form = document.getElementById('addSupplierForm');
    const formData = new FormData(form);
    
    // Validate required fields
    if (!formData.get('supplier_name').trim()) {
        showNotification('Supplier name is required!', 'warning');
        return;
    }
    
    // Send data to PHP endpoint
    fetch('add_supplier_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('addSupplierModal')).hide();
            
            // Show success message
            showNotification('Supplier added successfully!', 'success');
            
            // Reload the page to show the new supplier
            location.reload();
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('An error occurred while adding supplier', 'error');
    });
}
</script>

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

/* Enhanced modal styling */
.modal-content {
    border-radius: 12px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.modal-header {
    border-radius: 12px 12px 0 0;
    border-bottom: none;
}

.modal-footer {
    border-top: none;
    border-radius: 0 0 12px 12px;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .col-md-4 {
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