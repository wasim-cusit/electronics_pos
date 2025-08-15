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

// Fetch all suppliers
$suppliers = $pdo->query("SELECT * FROM supplier ORDER BY supplier_name")->fetchAll(PDO::FETCH_ASSOC);



include 'includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">

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
        alert('Supplier name is required!');
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
            alert('Supplier added successfully!');
            
            // Reload the page to show the new supplier
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding supplier');
    });
}
</script>

<?php include 'includes/footer.php'; ?>