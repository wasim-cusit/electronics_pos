<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'customers';

// Handle Add/Edit Customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_customer']) || isset($_POST['edit_customer']))) {
    $name = $_POST['name'];
    $mobile = $_POST['mobile'];
    $address = $_POST['address'];
    $email = $_POST['email'];
    $opening_balance = $_POST['opening_balance'] ?? 0.00;
    $status = $_POST['status'] ?? 1;

    if (isset($_POST['edit_customer']) && !empty($_POST['id'])) {
        // Update existing customer
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE customer SET name=?, mobile=?, address=?, email=?, opening_balance=?, status=? WHERE id=?");
        $stmt->execute([$name, $mobile, $address, $email, $opening_balance, $status, $id]);
        header("Location: customers.php?success=updated");
    } else {
        // Add new customer
        $stmt = $pdo->prepare("INSERT INTO customer (name, mobile, address, email, opening_balance, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $mobile, $address, $email, $opening_balance, $status]);
        header("Location: customers.php?success=added");
    }
    exit;
}

// Handle Delete Customer
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM customer WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: customers.php?success=deleted");
    exit;
}

// Fetch all customers
$customers = $pdo->query("SELECT * FROM customer ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);



include 'includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">👥 Customers</h2>
                <div class="btn-toolbar">
                    <button type="button" class="btn btn-primary" onclick="openCustomerModal()">
                        <i class="bi bi-plus-circle"></i> Add Customer
                    </button>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php
                    if ($_GET['success'] === 'added') echo "Customer added successfully!";
                    if ($_GET['success'] === 'updated') echo "Customer updated successfully!";
                    if ($_GET['success'] === 'deleted') echo "Customer deleted successfully!";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Customer Modal -->
            <div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="customerModalLabel">
                                <i class="bi bi-person-plus"></i>
                                <span id="modalTitle">➕ Add New Customer</span>
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="post" id="customerForm">
                            <div class="modal-body">
                                <input type="hidden" name="id" id="customerId" value="">
                                <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="customerName" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Mobile</label>
                                    <input type="text" name="mobile" id="customerMobile" class="form-control">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" id="customerEmail" class="form-control">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" id="customerStatus" class="form-control">
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Opening Balance</label>
                                    <input type="number" name="opening_balance" id="customerBalance" class="form-control" step="0.01" value="0.00">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea name="address" id="customerAddress" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </button>
                                <button type="submit" class="btn btn-success" name="add_customer" id="submitBtn">
                                    <i class="bi bi-check-circle"></i>
                                    <span id="submitBtnText">Add Customer</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Customer List Table -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-people"></i> Customer List
                        <span class="badge bg-primary ms-2"><?= count($customers) ?> customers</span>
                    </h5>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Mobile</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><?= htmlspecialchars($customer['name']) ?></td>
                                    <td><?= htmlspecialchars($customer['mobile']) ?></td>
                                    <td><?= htmlspecialchars($customer['email']) ?></td>
                                    <td><?= htmlspecialchars($customer['address']) ?></td>
                                    <td>
                                        <?php if ($customer['status']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($customer['created_at']) ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="javascript:void(0)" onclick="editCustomer(<?= $customer['id'] ?>, '<?= htmlspecialchars($customer['name']) ?>', '<?= htmlspecialchars($customer['mobile']) ?>', '<?= htmlspecialchars($customer['email']) ?>', '<?= htmlspecialchars($customer['address']) ?>', <?= $customer['opening_balance'] ?>, <?= $customer['status'] ?>)" class="btn btn-outline-primary" title="Edit Customer">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="customers.php?delete=<?= $customer['id'] ?>" class="btn btn-outline-danger" title="Delete Customer" 
                                               onclick="return confirm('Are you sure you want to delete this customer?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($customers)): ?>
                                <tr><td colspan="7" class="text-center">No customers found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Customer Modal Functions
function openCustomerModal() {
    // Reset form
    document.getElementById('customerForm').reset();
    document.getElementById('customerId').value = '';
    document.getElementById('modalTitle').textContent = '➕ Add New Customer';
    document.getElementById('submitBtn').name = 'add_customer';
    document.getElementById('submitBtnText').textContent = 'Add Customer';
    
    // Show modal
    var modal = new bootstrap.Modal(document.getElementById('customerModal'));
    modal.show();
}

function editCustomer(id, name, mobile, email, address, balance, status) {
    // Fill form with customer data
    document.getElementById('customerId').value = id;
    document.getElementById('customerName').value = name;
    document.getElementById('customerMobile').value = mobile;
    document.getElementById('customerEmail').value = email;
    document.getElementById('customerAddress').value = address;
    document.getElementById('customerBalance').value = balance;
    document.getElementById('customerStatus').value = status;
    
    // Update modal title and button
    document.getElementById('modalTitle').textContent = '✏️ Edit Customer';
    document.getElementById('submitBtn').name = 'edit_customer';
    document.getElementById('submitBtnText').textContent = 'Update Customer';
    
    // Show modal
    var modal = new bootstrap.Modal(document.getElementById('customerModal'));
    modal.show();
}

// Handle form submission
document.getElementById('customerForm').addEventListener('submit', function(e) {
    // Form will submit normally with the updated name attribute
});

// Reset form when modal is hidden
document.getElementById('customerModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('customerForm').reset();
    document.getElementById('customerId').value = '';
    document.getElementById('modalTitle').textContent = '➕ Add New Customer';
    document.getElementById('submitBtn').name = 'add_customer';
    document.getElementById('submitBtnText').textContent = 'Add Customer';
});
</script>

<?php include 'includes/footer.php'; ?>