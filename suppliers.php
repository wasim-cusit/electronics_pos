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
                <h2 class="mb-0">Suppliers</h2>
                <button type="button" class="btn btn-primary" onclick="openAddSupplierModal()">
                    <i class="bi bi-plus-circle"></i> Add Supplier
                </button>
            </div>

            <!-- Supplier List Table -->
            <div class="card">
                <div class="card-header">Supplier List</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Opening Balance</th>
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
                                        <span class="badge <?= ($supplier['opening_balance'] ?? 0) > 0 ? 'bg-danger' : 'bg-success' ?>">
                                            Rs.<?= number_format(abs($supplier['opening_balance'] ?? 0), 2) ?>
                                            <?= ($supplier['opening_balance'] ?? 0) > 0 ? '(Owed)' : '(Credit)' ?>
                                        </span>
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
                            <input type="number" class="form-control" id="supplierOpeningBalance" name="opening_balance" step="0.01" min="0" placeholder="0.00">
                            <small class="text-muted">Enter the opening balance for this supplier</small>
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