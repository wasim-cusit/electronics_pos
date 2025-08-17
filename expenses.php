<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';
require_once 'includes/settings.php';
require_login();

$activePage = 'expenses';

// Handle Add Expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    $date = trim($_POST['date']);
    $category_id = intval($_POST['category']);
    $expense_person = trim($_POST['expense_person']);
    $amount = floatval($_POST['amount']);
    $details = trim($_POST['details']);
    $receipt = trim($_POST['receipt'] ?? '');
    $company_id = 1; // Default company ID

    // Handle file upload
    $attachment_path = '';
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/expenses/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $file_name = 'expense_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_path)) {
                $attachment_path = $upload_path;
            }
        }
    }

    // Validate required fields with specific error messages
    $missing_fields = [];
    if (empty($date)) $missing_fields[] = 'Date';
    if (empty($category_id)) $missing_fields[] = 'Category';
    if (empty($expense_person)) $missing_fields[] = 'Expense Person';
    if (empty($details)) $missing_fields[] = 'Description';
    
    if (!empty($missing_fields)) {
        $error_msg = 'Missing required fields: ' . implode(', ', $missing_fields);
        header("Location: expenses.php?error=missing_fields&message=" . urlencode($error_msg));
        exit;
    }

    // Validate amount
    if ($amount <= 0) {
        header("Location: expenses.php?error=invalid_amount");
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO expenses (company_id, exp_date, cat_id, expense_person, amount, details, receipt, attachment_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$company_id, $date, $category_id, $expense_person, $amount, $details, $receipt, $attachment_path]);
    
    $success_message = "Expense added successfully!";
    if (!empty($attachment_path)) {
        $success_message .= " File uploaded successfully.";
    }
    
    header("Location: expenses.php?success=added&message=" . urlencode($success_message));
    exit;
}

// Handle Edit Expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_expense'])) {
    $id = $_POST['id'];
    $date = trim($_POST['date']);
    $category_id = intval($_POST['category']);
    $expense_person = trim($_POST['expense_person']);
    $amount = floatval($_POST['amount']);
    $details = trim($_POST['details']);
    $receipt = trim($_POST['receipt'] ?? '');

    // Handle file upload
    $attachment_path = $_POST['current_attachment'] ?? '';
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/expenses/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $file_name = 'expense_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_path)) {
                // Delete old file if exists
                if (!empty($_POST['current_attachment']) && file_exists($_POST['current_attachment'])) {
                    unlink($_POST['current_attachment']);
                }
                $attachment_path = $upload_path;
            }
        }
    }

    // Validate required fields with specific error messages
    $missing_fields = [];
    if (empty($date)) $missing_fields[] = 'Date';
    if (empty($category_id)) $missing_fields[] = 'Category';
    if (empty($expense_person)) $missing_fields[] = 'Expense Person';
    if (empty($details)) $missing_fields[] = 'Description';
    
    if (!empty($missing_fields)) {
        $error_msg = 'Missing required fields: ' . implode(', ', $missing_fields);
        header("Location: expenses.php?error=missing_fields&message=" . urlencode($error_msg));
        exit;
    }

    // Validate amount
    if ($amount <= 0) {
        header("Location: expenses.php?error=invalid_amount");
        exit;
    }

    $stmt = $pdo->prepare("UPDATE expenses SET exp_date=?, cat_id=?, expense_person=?, amount=?, details=?, receipt=?, attachment_path=? WHERE id=?");
    $stmt->execute([$date, $category_id, $expense_person, $amount, $details, $receipt, $attachment_path, $id]);
    header("Location: expenses.php?success=updated");
    exit;
}

// Handle Delete Expense
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Verify expense exists before deletion
    $stmt = $pdo->prepare("SELECT id, attachment_path FROM expenses WHERE id = ?");
    $stmt->execute([$id]);
    $expense = $stmt->fetch();
    
    if (!$expense) {
        header("Location: expenses.php?error=not_found");
        exit;
    }
    
    // Delete attachment file if exists
    if (!empty($expense['attachment_path']) && file_exists($expense['attachment_path'])) {
        unlink($expense['attachment_path']);
    }
    
    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: expenses.php?success=deleted");
    exit;
}

// Build search and filter conditions
$where_conditions = [];
$params = [];

if (!empty($_GET['date_from'])) {
    $where_conditions[] = "e.exp_date >= ?";
    $params[] = $_GET['date_from'];
}

if (!empty($_GET['date_to'])) {
    $where_conditions[] = "e.exp_date <= ?";
    $params[] = $_GET['date_to'];
}

if (!empty($_GET['category_filter'])) {
    $where_conditions[] = "e.cat_id = ?";
    $params[] = $_GET['category_filter'];
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Fetch filtered expenses with category info
$query = "SELECT e.*, ec.expense_cat AS category_name FROM expenses e LEFT JOIN expenses_category ec ON e.cat_id = ec.id $where_clause ORDER BY e.exp_date DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$today_total = 0;
$month_total = 0;
$total_expenses = 0;
$category_totals = [];

foreach ($expenses as $expense) {
    $total_expenses += $expense['amount'];

    // Category totals
    if (!isset($category_totals[$expense['category_name']])) {
        $category_totals[$expense['category_name']] = 0;
    }
    $category_totals[$expense['category_name']] += $expense['amount'];

    if ($expense['exp_date'] == date('Y-m-d')) {
        $today_total += $expense['amount'];
    }
    if (date('Y-m', strtotime($expense['exp_date'])) == date('Y-m')) {
        $month_total += $expense['amount'];
    }
}

// If editing, fetch expense
$edit_expense = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ?");
    $stmt->execute([$id]);
    $edit_expense = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch expense categories for dropdown
$expense_categories = $pdo->query("SELECT id, expense_cat FROM expenses_category WHERE status = 1 ORDER BY expense_cat")->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
            <h2 class="mb-4">Shop Expenses</h2>
            <div class="print-header d-none">
                <h3 class="text-center">Expense Report</h3>
                <p class="text-center text-muted">Generated on: <?= date('F j, Y \a\t g:i A') ?></p>
                <hr>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" id="successAlert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?php
                    if ($_GET['success'] === 'added') {
                        echo isset($_GET['message']) ? htmlspecialchars($_GET['message']) : "Expense added successfully!";
                    }
                    if ($_GET['success'] === 'updated') echo "Expense updated successfully!";
                    if ($_GET['success'] === 'deleted') echo "Expense deleted successfully!";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" id="errorAlert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?php
                    if ($_GET['error'] === 'invalid_amount') echo "Amount must be greater than 0!";
                    if ($_GET['error'] === 'missing_fields') {
                        echo isset($_GET['message']) ? htmlspecialchars($_GET['message']) : "Please fill in all required fields!";
                    }
                    if ($_GET['error'] === 'not_found') echo "Expense not found!";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Expense Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">Today's Expenses</h5>
                            <p class="card-text display-6"><?= format_currency($today_total) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-bg-success">
                        <div class="card-body">
                            <h5 class="card-title">This Month</h5>
                            <p class="card-text display-6"><?= format_currency($month_total) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">Total Expenses</h5>
                            <p class="card-text display-6"><?= format_currency($total_expenses) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-bg-info">
                        <div class="card-body">
                            <h5 class="card-title">Total Records</h5>
                            <p class="card-text display-6"><?= count($expenses) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Expense Form -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><?= $edit_expense ? "Edit Expense" : "Add New Expense" ?></span>
                    <?php if (!$edit_expense): ?>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="toggleQuickAdd()">
                            <i class="bi bi-plus-circle"></i> Quick Add
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <?php if ($edit_expense): ?>
                            <input type="hidden" name="id" value="<?= $edit_expense['id'] ?>">
                            <input type="hidden" name="current_attachment" value="<?= $edit_expense['attachment_path'] ?? '' ?>">
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control" required value="<?= $edit_expense ? $edit_expense['exp_date'] : date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select name="category" class="form-control" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($expense_categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= ($edit_expense && $edit_expense['cat_id'] == $cat['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['expense_cat']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Expense Person <span class="text-danger">*</span></label>
                                <input type="text" name="expense_person" class="form-control" required value="<?= $edit_expense ? htmlspecialchars($edit_expense['expense_person']) : '' ?>" placeholder="Who paid/received">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Amount <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="amount" class="form-control" required value="<?= $edit_expense ? $edit_expense['amount'] : '' ?>" placeholder="0.00">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Receipt</label>
                                <input type="text" name="receipt" class="form-control" value="<?= $edit_expense ? htmlspecialchars($edit_expense['receipt']) : '' ?>" placeholder="Receipt number">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea name="details" class="form-control" rows="3" placeholder="Enter expense details..."><?= $edit_expense ? htmlspecialchars($edit_expense['details']) : '' ?></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Attachment (Receipt/Bill)</label>
                                <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif">
                                <?php if ($edit_expense && !empty($edit_expense['attachment_path'])): ?>
                                    <div class="mt-2">
                                        <small class="text-muted">Current: 
                                            <a href="<?= htmlspecialchars($edit_expense['attachment_path']) ?>" target="_blank" class="text-decoration-none">
                                                <?= basename($edit_expense['attachment_path']) ?>
                                            </a>
                                        </small>
                                    </div>
                                <?php endif; ?>
                                <small class="text-muted">Supported formats: PDF, DOC, DOCX, JPG, PNG, GIF (Max: 5MB)</small>
                                <div class="mt-1">
                                    <small class="text-muted" id="file-info"></small>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" name="<?= $edit_expense ? 'edit_expense' : 'add_expense' ?>">
                            <?= $edit_expense ? "Update Expense" : "Add Expense" ?>
                        </button>
                        <?php if ($edit_expense): ?>
                            <a href="expenses.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Category Summary -->
            <div class="card mb-4">
                <div class="card-header">Expense Summary by Category</div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($category_totals as $category => $total): ?>
                            <div class="col-md-3 mb-3">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <h6 class="card-title"><?= htmlspecialchars($category) ?></h6>
                                        <p class="card-text h5 text-primary"><?= format_currency($total) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Search & Filter Expenses</span>
                    <div>
                        <button type="button" class="btn btn-success btn-sm me-2" onclick="exportExpenses()">
                            <i class="bi bi-download"></i> Export CSV
                        </button>
                        <button type="button" class="btn btn-info btn-sm" onclick="printExpenses()">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="<?= $_GET['date_from'] ?? '' ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="<?= $_GET['date_to'] ?? '' ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select name="category_filter" class="form-control">
                                <option value="">All Categories</option>
                                <?php foreach ($expense_categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= (isset($_GET['category_filter']) && $_GET['category_filter'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['expense_cat']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">Search</button>
                                <a href="expenses.php" class="btn btn-secondary">Clear</a>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Filter Summary -->
                    <?php if (!empty($_GET['date_from']) || !empty($_GET['date_to']) || !empty($_GET['category_filter'])): ?>
                        <div class="mt-3 p-3 bg-light rounded">
                            <h6 class="mb-2">Active Filters:</h6>
                            <div class="row">
                                <?php if (!empty($_GET['date_from'])): ?>
                                    <div class="col-md-3">
                                        <small class="text-muted">From: <?= date('M j, Y', strtotime($_GET['date_from'])) ?></small>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($_GET['date_to'])): ?>
                                    <div class="col-md-3">
                                        <small class="text-muted">To: <?= date('M j, Y', strtotime($_GET['date_to'])) ?></small>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($_GET['category_filter'])): ?>
                                    <div class="col-md-3">
                                        <small class="text-muted">Category: 
                                            <?php 
                                            $cat_id = $_GET['category_filter'];
                                            $cat_name = '';
                                            foreach ($expense_categories as $cat) {
                                                if ($cat['id'] == $cat_id) {
                                                    $cat_name = $cat['expense_cat'];
                                                    break;
                                                }
                                            }
                                            echo htmlspecialchars($cat_name);
                                            ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                                <div class="col-md-3">
                                    <small class="text-muted">Results: <?= count($expenses) ?> records</small>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Expense List Table -->
            <div class="card">
                <div class="card-header">Expense Records</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Expense Person</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Receipt</th>
                                <th>Attachment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expenses as $expense): ?>
                                <tr>
                                    <td><?= format_date($expense['exp_date']) ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?= htmlspecialchars($expense['category_name']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($expense['expense_person']) ?></td>
                                    <td><?= htmlspecialchars($expense['details']) ?></td>
                                    <td class="fw-bold"><?= format_currency($expense['amount']) ?></td>
                                    <td><?= htmlspecialchars($expense['receipt']) ?: '<span class="text-muted">No receipt</span>' ?></td>
                                    <td>
                                        <?php if (!empty($expense['attachment_path'])): ?>
                                            <a href="<?= htmlspecialchars($expense['attachment_path']) ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                <i class="bi bi-file-earmark"></i> View
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">No attachment</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="expenses.php?edit=<?= $expense['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <a href="expenses.php?delete=<?= $expense['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirmDelete(<?= $expense['id'] ?>, '<?= htmlspecialchars($expense['details']) ?>')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($expenses)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">No expenses found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
@media print {
    .btn, .form-control, .card-header, .sidebar, .navbar, .alert {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
        margin-bottom: 20px !important;
    }
    .card-body {
        padding: 15px !important;
    }
    .container-fluid {
        padding: 0 !important;
    }
    main {
        margin-top: 0 !important;
        padding: 20px !important;
    }
    table {
        width: 100% !important;
        border-collapse: collapse !important;
        margin-bottom: 20px !important;
    }
    th, td {
        border: 1px solid #000 !important;
        padding: 8px !important;
        text-align: left !important;
    }
    th {
        background-color: #f8f9fa !important;
        font-weight: bold !important;
    }
    .badge {
        background-color: #007bff !important;
        color: white !important;
        padding: 4px 8px !important;
        border-radius: 4px !important;
    }
    .row {
        margin: 0 !important;
    }
    .col-md-3, .col-md-6 {
        padding: 10px !important;
    }
    h2 {
        margin-bottom: 20px !important;
        text-align: center !important;
    }
    .print-header {
        display: block !important;
        margin-bottom: 30px !important;
    }
    .print-header h3 {
        margin-bottom: 10px !important;
        color: #000 !important;
    }
    .print-header p {
        color: #666 !important;
    }
    
    /* Quick Add Mode Styles */
    .quick-add-mode .col-md-6:last-child,
    .quick-add-mode .col-md-2:last-child {
        display: none;
    }
    
    .quick-add-mode .col-md-2:first-child {
        width: 25%;
    }
    
    .quick-add-mode .col-md-3:nth-child(2) {
        width: 25%;
    }
    
    .quick-add-mode .col-md-3:nth-child(3) {
        width: 25%;
    }
    
    .quick-add-mode .col-md-2:nth-child(4) {
        width: 25%;
    }
    
    /* Form validation styles */
    .form-control.is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
    
    .form-control.is-invalid:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
    
    /* Custom alert positioning */
    .custom-alert {
        animation: slideInRight 0.3s ease-out;
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
}
</style>

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

// Auto-dismiss notifications
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss success notifications after 5 seconds
    const successAlert = document.getElementById('successAlert');
    if (successAlert) {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(successAlert);
            bsAlert.close();
        }, 5000);
    }
    
    // Auto-dismiss error notifications after 8 seconds
    const errorAlert = document.getElementById('errorAlert');
    if (errorAlert) {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(errorAlert);
            bsAlert.close();
        }, 8000);
    }
    
    // File upload validation
    const fileInput = document.querySelector('input[name="attachment"]');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            const fileInfo = document.getElementById('file-info');
            
            if (file) {
                const maxSize = 5 * 1024 * 1024; // 5MB
                const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                
                // Show file info
                fileInfo.innerHTML = `Selected: ${file.name} (${fileSizeMB} MB)`;
                fileInfo.className = 'text-info';
                
                if (file.size > maxSize) {
                    showNotification('File size must be less than 5MB', 'warning');
                    this.value = '';
                    fileInfo.innerHTML = '';
                    return;
                }
                
                const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    showNotification('Please select a valid file type (PDF, DOC, DOCX, JPG, PNG, GIF)', 'warning');
                    this.value = '';
                    fileInfo.innerHTML = '';
                    return;
                }
                
                // Show success message
                fileInfo.className = 'text-success';
            } else {
                fileInfo.innerHTML = '';
            }
        });
    }
    
    // Enhanced form validation
    const form = document.querySelector('form[enctype="multipart/form-data"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            let hasErrors = false;
            let errorMessage = '';
            
            // Check required fields
            const requiredFields = [
                { name: 'date', label: 'Date' },
                { name: 'category', label: 'Category' },
                { name: 'expense_person', label: 'Expense Person' },
                { name: 'details', label: 'Description' }
            ];
            
            requiredFields.forEach(field => {
                const input = document.querySelector(`[name="${field.name}"]`);
                if (!input.value.trim()) {
                    hasErrors = true;
                    errorMessage += `• ${field.label} is required\n`;
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            
            // Check amount
            const amountInput = document.querySelector('input[name="amount"]');
            const amount = parseFloat(amountInput.value);
            if (!amount || amount <= 0) {
                hasErrors = true;
                errorMessage += '• Amount must be greater than 0\n';
                amountInput.classList.add('is-invalid');
            } else {
                amountInput.classList.remove('is-invalid');
            }
            
            if (hasErrors) {
                e.preventDefault();
                showCustomAlert('Please fix the following errors:', errorMessage, 'error');
                return false;
            }
        });
    }
});

// Custom alert function for better user experience
function showCustomAlert(title, message, type = 'info') {
    // Remove existing custom alerts
    const existingAlert = document.querySelector('.custom-alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show custom-alert position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px;';
    
    const icon = type === 'error' ? 'bi-exclamation-triangle' : 'bi-info-circle';
    alertDiv.innerHTML = `
        <i class="bi ${icon} me-2"></i>
        <strong>${title}</strong><br>
        <small>${message}</small>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto-dismiss after 8 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            const bsAlert = new bootstrap.Alert(alertDiv);
            bsAlert.close();
        }
    }, 8000);
}

// Enhanced delete confirmation
function confirmDelete(expenseId, expenseDetails) {
    return confirm(`Are you sure you want to delete this expense?\n\nDetails: ${expenseDetails}\n\nThis action cannot be undone.`);
}

// Export expenses to CSV
function exportExpenses() {
    const table = document.querySelector('table');
    const rows = table.querySelectorAll('tbody tr');
    
    let csv = 'Date,Category,Expense Person,Description,Amount,Receipt,Attachment\n';
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length > 0) {
            const rowData = [];
            cells.forEach((cell, index) => {
                if (index < 7) { // Skip Actions column
                    let text = cell.textContent.trim();
                    // Remove HTML tags and clean text
                    text = text.replace(/<[^>]*>/g, '');
                    // Escape quotes and wrap in quotes if contains comma
                    if (text.includes(',') || text.includes('"')) {
                        text = '"' + text.replace(/"/g, '""') + '"';
                    }
                    rowData.push(text);
                }
            });
            csv += rowData.join(',') + '\n';
        }
    });
    
    // Create download link
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'expenses_' + new Date().toISOString().split('T')[0] + '.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

// Print expenses function
function printExpenses() {
    // Hide elements that shouldn't be printed
    const elementsToHide = document.querySelectorAll('.btn, .form-control, .card-header, .sidebar, .navbar, .alert');
    elementsToHide.forEach(el => el.style.display = 'none');
    
    // Print the page
    window.print();
    
    // Restore elements after printing
    setTimeout(() => {
        elementsToHide.forEach(el => el.style.display = '');
    }, 1000);
}

// Quick add toggle function
function toggleQuickAdd() {
    const form = document.querySelector('form[enctype="multipart/form-data"]');
    const quickAddBtn = document.querySelector('button[onclick="toggleQuickAdd()"]');
    
    if (form.classList.contains('quick-add-mode')) {
        // Switch to full form
        form.classList.remove('quick-add-mode');
        quickAddBtn.innerHTML = '<i class="bi bi-plus-circle"></i> Quick Add';
        quickAddBtn.className = 'btn btn-outline-primary btn-sm';
    } else {
        // Switch to quick add mode
        form.classList.add('quick-add-mode');
        quickAddBtn.innerHTML = '<i class="bi bi-list"></i> Full Form';
        quickAddBtn.className = 'btn btn-outline-secondary btn-sm';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
