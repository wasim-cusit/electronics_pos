<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';
require_once 'includes/settings.php';
require_login();

$activePage = 'expenses';

// Handle Add Expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    $date = $_POST['date'];
    $category = $_POST['category'] === 'Other' && !empty($_POST['other_category']) ? trim($_POST['other_category']) : $_POST['category'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $payment_method = $_POST['payment_method'];
    $created_by = $_SESSION['user_id'];

    // Handle file upload
    $attachment_path = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/expenses/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_path)) {
            $attachment_path = $upload_path;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO expenses (date, category, amount, description, payment_method, attachment_path, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$date, $category, $amount, $description, $payment_method, $attachment_path, $created_by]);
    header("Location: expenses.php?success=added");
    exit;
}

// Handle Edit Expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_expense'])) {
    $id = $_POST['id'];
    $date = $_POST['date'];
    $category = $_POST['category'] === 'Other' && !empty($_POST['other_category']) ? trim($_POST['other_category']) : $_POST['category'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $payment_method = $_POST['payment_method'];

    // Handle file upload for edit
    $attachment_path = $_POST['current_attachment'];
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/expenses/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_path)) {
            // Delete old attachment if exists
            if ($attachment_path && file_exists($attachment_path)) {
                unlink($attachment_path);
            }
            $attachment_path = $upload_path;
        }
    }

    $stmt = $pdo->prepare("UPDATE expenses SET date=?, category=?, amount=?, description=?, payment_method=?, attachment_path=? WHERE id=?");
    $stmt->execute([$date, $category, $amount, $description, $payment_method, $attachment_path, $id]);
    header("Location: expenses.php?success=updated");
    exit;
}

// Handle Delete Expense
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    // Get attachment path to delete file
    $stmt = $pdo->prepare("SELECT attachment_path FROM expenses WHERE id = ?");
    $stmt->execute([$id]);
    $expense = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($expense && $expense['attachment_path'] && file_exists($expense['attachment_path'])) {
        unlink($expense['attachment_path']);
    }

    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: expenses.php?success=deleted");
    exit;
}

// Fetch all expenses with user info
$expenses = $pdo->query("SELECT e.*, u.username AS created_by_name FROM expenses e LEFT JOIN users u ON e.created_by = u.id ORDER BY e.date DESC")->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$today_total = 0;
$month_total = 0;
$total_expenses = 0;
$category_totals = [];

foreach ($expenses as $expense) {
    $total_expenses += $expense['amount'];

    // Category totals
    if (!isset($category_totals[$expense['category']])) {
        $category_totals[$expense['category']] = 0;
    }
    $category_totals[$expense['category']] += $expense['amount'];

    if ($expense['date'] == date('Y-m-d')) {
        $today_total += $expense['amount'];
    }
    if (date('Y-m', strtotime($expense['date'])) == date('Y-m')) {
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

include 'includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
            <h2 class="mb-4">Shop Expenses</h2>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php
                    if ($_GET['success'] === 'added') echo "Expense added successfully!";
                    if ($_GET['success'] === 'updated') echo "Expense updated successfully!";
                    if ($_GET['success'] === 'deleted') echo "Expense deleted successfully!";
                    ?>
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
                <div class="card-header">
                    <?= $edit_expense ? "Edit Expense" : "Add New Expense" ?>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <?php if ($edit_expense): ?>
                            <input type="hidden" name="id" value="<?= $edit_expense['id'] ?>">
                            <input type="hidden" name="current_attachment" value="<?= $edit_expense['attachment_path'] ?>">
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-2  mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="date" class="form-control" required value="<?= $edit_expense ? $edit_expense['date'] : date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Category</label>
                                <div class="d-flex">
                                    <select name="category" class="form-control" id="categorySelect" required style="width: 60%;">
                                        <option value="">Select Category</option>
                                        <option value="Electricity" <?= ($edit_expense && $edit_expense['category'] == 'Electricity') ? 'selected' : '' ?>>Electricity Bill</option>
                                        <option value="Water" <?= ($edit_expense && $edit_expense['category'] == 'Water') ? 'selected' : '' ?>>Water Bill</option>
                                        <option value="Gas" <?= ($edit_expense && $edit_expense['category'] == 'Gas') ? 'selected' : '' ?>>Gas Bill</option>
                                        <option value="Rent" <?= ($edit_expense && $edit_expense['category'] == 'Rent') ? 'selected' : '' ?>>Shop Rent</option>
                                        <option value="Tea" <?= ($edit_expense && $edit_expense['category'] == 'Tea') ? 'selected' : '' ?>>Tea & Refreshments</option>
                                        <option value="Cleaning" <?= ($edit_expense && $edit_expense['category'] == 'Cleaning') ? 'selected' : '' ?>>Cleaning Supplies</option>
                                        <option value="Maintenance" <?= ($edit_expense && $edit_expense['category'] == 'Maintenance') ? 'selected' : '' ?>>Equipment Maintenance</option>
                                        <option value="Transport" <?= ($edit_expense && $edit_expense['category'] == 'Transport') ? 'selected' : '' ?>>Transportation</option>
                                        <option value="Internet" <?= ($edit_expense && $edit_expense['category'] == 'Internet') ? 'selected' : '' ?>>Internet/Phone</option>
                                        <option value="Salary" <?= ($edit_expense && $edit_expense['category'] == 'Salary') ? 'selected' : '' ?>>Employee Salary</option>
                                        <option value="Other" <?= (
                                                                    $edit_expense && !in_array(
                                                                        $edit_expense['category'],
                                                                        [
                                                                            'Electricity',
                                                                            'Water',
                                                                            'Gas',
                                                                            'Rent',
                                                                            'Tea',
                                                                            'Cleaning',
                                                                            'Maintenance',
                                                                            'Transport',
                                                                            'Internet',
                                                                            'Salary',
                                                                            'Other'
                                                                        ]
                                                                    )) ? 'selected' : ''
                                                                ?>>Other Expenses</option>
                                    </select>
                                    <input type="text" name="other_category" id="otherCategoryInput" class="form-control ms-2" placeholder="Enter other category" style="display:none; width: 60%;" value="<?= ($edit_expense && !in_array($edit_expense['category'], ['Electricity', 'Water', 'Gas', 'Rent', 'Tea', 'Cleaning', 'Maintenance', 'Transport', 'Internet', 'Salary', 'Other'])) ? htmlspecialchars($edit_expense['category']) : '' ?>">
                                </div>
                                <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        var categorySelect = document.getElementById('categorySelect');
                                        var otherInput = document.getElementById('otherCategoryInput');

                                        function toggleOtherInput() {
                                            if (categorySelect.value === 'Other') {
                                                otherInput.style.display = 'inline-block';
                                                otherInput.required = true;
                                            } else {
                                                otherInput.style.display = 'none';
                                                otherInput.required = false;
                                            }
                                        }
                                        categorySelect.addEventListener('change', toggleOtherInput);
                                        toggleOtherInput();
                                    });
                                </script>
                            </div>
                            <div class=" mb-3" style="width: 13%;">
                                <label class="form-label">Amount</label>
                                <input type="number" step="0.01" name="amount" class="form-control" required value="<?= $edit_expense ? $edit_expense['amount'] : '' ?>" placeholder="0.00">
                            </div>
                            <div class=" mb-3" style="width: 13%;">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" class="form-control" required>
                                    <option value="">Select Method</option>
                                    <option value="Cash" <?= ($edit_expense && $edit_expense['payment_method'] == 'Cash') ? 'selected' : '' ?>>Cash</option>
                                    <option value="Bank Transfer" <?= ($edit_expense && $edit_expense['payment_method'] == 'Bank Transfer') ? 'selected' : '' ?>>Bank Transfer</option>
                                    <option value="Card" <?= ($edit_expense && $edit_expense['payment_method'] == 'Card') ? 'selected' : '' ?>>Card Payment</option>
                                    <option value="Mobile Money" <?= ($edit_expense && $edit_expense['payment_method'] == 'Mobile Money') ? 'selected' : '' ?>>Mobile Money</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Enter expense details..."><?= $edit_expense ? htmlspecialchars($edit_expense['description']) : '' ?></textarea>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Attachment (Receipt/Bill)</label>
                                <input type="file" name="attachment" class="form-control" accept="image/*,.pdf,.doc,.docx">
                                <?php if ($edit_expense && $edit_expense['attachment_path']): ?>
                                    <small class="text-muted">Current: <?= basename($edit_expense['attachment_path']) ?></small>
                                <?php endif; ?>
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

            <!-- Expense List Table -->
            <div class="card">
                <div class="card-header">Expense Records</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Created By</th>
                                <th>Attachment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expenses as $expense): ?>
                                <tr>
                                    <td><?= format_date($expense['date']) ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?= htmlspecialchars($expense['category']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($expense['description']) ?></td>
                                    <td class="fw-bold"><?= format_currency($expense['amount']) ?></td>
                                    <td><?= htmlspecialchars($expense['payment_method']) ?></td>
                                    <td><?= htmlspecialchars($expense['created_by_name']) ?></td>
                                    <td>
                                        <?php if ($expense['attachment_path']): ?>
                                            <a href="<?= $expense['attachment_path'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-file-earmark"></i> View
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">No attachment</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="expenses.php?edit=<?= $expense['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <a href="expenses.php?delete=<?= $expense['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this expense?')">Delete</a>
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

<?php include 'includes/footer.php'; ?>