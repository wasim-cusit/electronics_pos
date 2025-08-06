<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';
require_login();

$activePage = 'expenses';

// Handle Add Expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    $date = $_POST['date'];
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];
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

    $stmt = $pdo->prepare("INSERT INTO expenses (date, category, amount, description, attachment_path, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$date, $category, $amount, $description, $attachment_path, $created_by]);
    header("Location: expenses.php?success=added");
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

foreach ($expenses as $expense) {
    $total_expenses += $expense['amount'];
    if ($expense['date'] == date('Y-m-d')) {
        $today_total += $expense['amount'];
    }
    if (date('Y-m', strtotime($expense['date'])) == date('Y-m')) {
        $month_total += $expense['amount'];
    }
}

include 'includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
            <h2 class="mb-4">Expenses</h2>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php
                    if ($_GET['success'] === 'added') echo "Expense added successfully!";
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
                            <p class="card-text display-6">PKR <?= number_format($today_total, 2) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-bg-success">
                        <div class="card-body">
                            <h5 class="card-title">This Month</h5>
                            <p class="card-text display-6">PKR <?= number_format($month_total, 2) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">Total Expenses</h5>
                            <p class="card-text display-6">PKR <?= number_format($total_expenses, 2) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Expense Form -->
            <div class="card mb-4">
                <div class="card-header">Add Expense</div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="date" class="form-control" required value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-3 mb-3">