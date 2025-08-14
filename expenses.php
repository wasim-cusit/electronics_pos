<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';
require_once 'includes/settings.php';
require_login();

$activePage = 'expenses';

// Handle Add Expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    $date = $_POST['date'];
    $category_id = $_POST['category'];
    $expense_person = $_POST['expense_person'];
    $amount = $_POST['amount'];
    $details = $_POST['description'];
    $receipt = $_POST['receipt'] ?? '';

    $stmt = $pdo->prepare("INSERT INTO expenses (exp_date, cat_id, expense_person, amount, details, receipt) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$date, $category_id, $expense_person, $amount, $details, $receipt]);
    header("Location: expenses.php?success=added");
    exit;
}

// Handle Edit Expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_expense'])) {
    $id = $_POST['id'];
    $date = $_POST['date'];
    $category_id = $_POST['category'];
    $expense_person = $_POST['expense_person'];
    $amount = $_POST['amount'];
    $details = $_POST['description'];
    $receipt = $_POST['receipt'] ?? '';

    $stmt = $pdo->prepare("UPDATE expenses SET exp_date=?, cat_id=?, expense_person=?, amount=?, details=?, receipt=? WHERE id=?");
    $stmt->execute([$date, $category_id, $expense_person, $amount, $details, $receipt, $id]);
    header("Location: expenses.php?success=updated");
    exit;
}

// Handle Delete Expense
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: expenses.php?success=deleted");
    exit;
}

// Fetch all expenses with category info
$expenses = $pdo->query("SELECT e.*, ec.expense_cat AS category_name FROM expenses e LEFT JOIN expenses_category ec ON e.cat_id = ec.id ORDER BY e.exp_date DESC")->fetchAll(PDO::FETCH_ASSOC);

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
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="date" class="form-control" required value="<?= $edit_expense ? $edit_expense['exp_date'] : date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Category</label>
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
                                <label class="form-label">Expense Person</label>
                                <input type="text" name="expense_person" class="form-control" required value="<?= $edit_expense ? htmlspecialchars($edit_expense['expense_person']) : '' ?>" placeholder="Who paid/received">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Amount</label>
                                <input type="number" step="0.01" name="amount" class="form-control" required value="<?= $edit_expense ? $edit_expense['amount'] : '' ?>" placeholder="0.00">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Receipt</label>
                                <input type="text" name="receipt" class="form-control" value="<?= $edit_expense ? htmlspecialchars($edit_expense['receipt']) : '' ?>" placeholder="Receipt number">
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
                                 <th>Expense Person</th>
                                 <th>Description</th>
                                 <th>Amount</th>
                                 <th>Receipt</th>
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
                                         <a href="expenses.php?edit=<?= $expense['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                         <a href="expenses.php?delete=<?= $expense['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this expense?')">Delete</a>
                                     </td>
                                 </tr>
                             <?php endforeach; ?>
                             <?php if (empty($expenses)): ?>
                                 <tr>
                                     <td colspan="7" class="text-center">No expenses found.</td>
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