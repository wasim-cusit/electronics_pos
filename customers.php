<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'customers';

// Handle Add/Edit Customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_customer']) || isset($_POST['edit_customer']))) {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
        header("Location: customers.php?error=" . urlencode($error));
        exit;
    }
    
    // Sanitize and validate inputs
    $name = sanitize_input($_POST['name']);
    $mobile = sanitize_input($_POST['mobile']);
    $cnic = sanitize_input($_POST['cnic'] ?? '');
    $address = sanitize_input($_POST['address']);
    $email = sanitize_input($_POST['email']);
    $opening_balance = floatval($_POST['opening_balance'] ?? 0.00);
    $status = intval($_POST['status'] ?? 1);
    
    // Validate required fields
    if (empty($name)) {
        $error = "Customer name is required.";
        header("Location: customers.php?error=" . urlencode($error));
        exit;
    }
    
    // Validate email if provided
    if (!empty($email) && !validate_email($email)) {
        $error = "Invalid email format.";
        header("Location: customers.php?error=" . urlencode($error));
        exit;
    }
    
    // Validate mobile if provided
    if (!empty($mobile) && !validate_phone($mobile)) {
        $error = "Invalid mobile number format.";
        header("Location: customers.php?error=" . urlencode($error));
        exit;
    }
    
    // Validate CNIC if provided
    if (!empty($cnic) && !validate_cnic($cnic)) {
        $error = "Invalid CNIC format.";
        header("Location: customers.php?error=" . urlencode($error));
        exit;
    }

    try {
        if (isset($_POST['edit_customer']) && !empty($_POST['id'])) {
            // Update existing customer
            $id = intval($_POST['id']);
            $stmt = $pdo->prepare("UPDATE customer SET name=?, mobile=?, cnic=?, address=?, email=?, opening_balance=?, status=? WHERE id=?");
            $stmt->execute([$name, $mobile, $cnic, $address, $email, $opening_balance, $status, $id]);
            header("Location: customers.php?success=updated");
        } else {
            // Add new customer
            $stmt = $pdo->prepare("INSERT INTO customer (name, mobile, cnic, address, email, opening_balance, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $mobile, $cnic, $address, $email, $opening_balance, $status]);
            header("Location: customers.php?success=added");
        }
        exit;
    } catch (Exception $e) {
        $error = "Error processing customer: " . $e->getMessage();
        header("Location: customers.php?error=" . urlencode($error));
        exit;
    }
}

// Handle Delete Customer
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM customer WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: customers.php?success=deleted");
    exit;
}

// Handle search functionality with validation
$search = sanitize_input($_GET['search'] ?? '');
$status_filter = sanitize_input($_GET['status_filter'] ?? '');

// Validate status filter
if ($status_filter !== '' && !in_array($status_filter, ['0', '1'])) {
    $status_filter = '';
}

// Build the customers query with search filters
$customers_query = "SELECT * FROM customer WHERE 1=1";

$params = [];

if (!empty($search)) {
    // Limit search length to prevent abuse
    if (strlen($search) > 100) {
        $search = substr($search, 0, 100);
    }
    
    $customers_query .= " AND (name LIKE ? OR mobile LIKE ? OR cnic LIKE ? OR email LIKE ? OR address LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
}

if ($status_filter !== '') {
    $customers_query .= " AND status = ?";
    $params[] = $status_filter;
}

$customers_query .= " ORDER BY name";

// Execute the query with parameters
try {
    $stmt = $pdo->prepare($customers_query);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Customer search error: " . $e->getMessage());
    $customers = [];
    $error = "Error performing search. Please try again.";
}


include 'includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 " style="margin-top: 25px;">
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
                                <!-- CSRF Protection -->
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                
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
                                    <label class="form-label">CNIC</label>
                                    <input type="text" name="cnic" id="customerCnic" class="form-control" placeholder="00000-0000000-0" pattern="[0-9]{5}-[0-9]{7}-[0-9]">
                                    <small class="text-muted">Format: 00000-0000000-0</small>
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

            <!-- Search and Filter Section -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="bi bi-search me-2"></i>Search & Filter Customers
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search Customers</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="Search by name, mobile, CNIC, email, or address..." 
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="status_filter" class="form-label">Filter by Status</label>
                            <select class="form-select" id="status_filter" name="status_filter">
                                <option value="">All Statuses</option>
                                <option value="1" <?= ($status_filter === '1') ? 'selected' : '' ?>>Active</option>
                                <option value="0" <?= ($status_filter === '0') ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search me-2"></i>Search
                                </button>
                                <a href="customers.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-clockwise me-2"></i>Clear
                                </a>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Search Results Summary -->
                    <?php if (!empty($search) || $status_filter !== ''): ?>
                        <div class="mt-3 p-3 bg-info bg-opacity-10 border border-info rounded">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-info-circle text-info me-2"></i>
                                <div>
                                    <strong>Search Results:</strong>
                                    <?php if (!empty($search)): ?>
                                        <span class="badge bg-primary ms-2">Search: "<?= htmlspecialchars($search) ?>"</span>
                                    <?php endif; ?>
                                    <?php if ($status_filter !== ''): ?>
                                        <span class="badge bg-success ms-2">Status Filter: <?= ($status_filter === '1') ? 'Active' : 'Inactive' ?></span>
                                    <?php endif; ?>
                                    <span class="badge bg-secondary ms-2">Found: <?= count($customers) ?> customers</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
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
                                <th>CNIC</th>
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
                                    <td><?= htmlspecialchars($customer['cnic'] ?? '') ?></td>
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
                                            <a href="javascript:void(0)" onclick="editCustomer(<?= $customer['id'] ?>, '<?= htmlspecialchars($customer['name']) ?>', '<?= htmlspecialchars($customer['mobile']) ?>', '<?= htmlspecialchars($customer['cnic'] ?? '') ?>', '<?= htmlspecialchars($customer['email']) ?>', '<?= htmlspecialchars($customer['address']) ?>', <?= $customer['opening_balance'] ?>, <?= $customer['status'] ?>)" class="btn btn-outline-primary" title="Edit Customer">
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
                                <tr><td colspan="8" class="text-center">No customers found.</td></tr>
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

function editCustomer(id, name, mobile, cnic, email, address, balance, status) {
    // Fill form with customer data
    document.getElementById('customerId').value = id;
    document.getElementById('customerName').value = name;
    document.getElementById('customerMobile').value = mobile;
    document.getElementById('customerCnic').value = cnic;
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
    
    .btn-toolbar {
        margin-top: 1rem;
        width: 100%;
    }
    
    .btn-toolbar .btn {
        width: 100%;
    }
}
</style>

<?php include 'includes/footer.php'; ?>