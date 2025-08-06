<?php
require_once 'includes/config.php';
// Expense categories
$categories = [
    'Rent', 'Electricity Bill', 'Internet Bill', 'Tailoring/Labor', 'Staff Salary',
    'Packaging', 'Transport', 'Maintenance', 'Miscellaneous'
];

$success = false;
$errors = [];
$data = [
    'date' => '',
    'category' => '',
    'amount' => '',
    'description' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate
    $data['date'] = $_POST['date'] ?? '';
    $data['category'] = $_POST['category'] ?? '';
    $data['amount'] = $_POST['amount'] ?? '';
    $data['description'] = $_POST['description'] ?? '';
    $file = $_FILES['attachment'] ?? null;

    if (!$data['date']) $errors[] = 'Date is required.';
    if (!$data['category']) $errors[] = 'Category is required.';
    if (!$data['amount'] || !is_numeric($data['amount'])) $errors[] = 'Valid amount is required.';
    // File is optional

    if (empty($errors)) {
        // For now, just show the data (no DB, no file upload)
        $success = true;
    }
}
?>

<?php if ($success): ?>
    <div class="alert alert-success">Expense recorded! (Demo mode)</div>
    <div class="card mb-3">
        <div class="card-body">
            <strong>Date:</strong> <?= htmlspecialchars($data['date']) ?><br>
            <strong>Category:</strong> <?= htmlspecialchars($data['category']) ?><br>
            <strong>Amount:</strong> <?= htmlspecialchars($data['amount']) ?><br>
            <strong>Description:</strong> <?= nl2br(htmlspecialchars($data['description'])) ?><br>
            <?php if (!empty($_FILES['attachment']['name'])): ?>
                <strong>Attachment:</strong> <?= htmlspecialchars($_FILES['attachment']['name']) ?><br>
            <?php endif; ?>
        </div>
    </div>
    <a href="<?= $base_url ?>expense_entry.php" class="btn btn-primary">Add Another</a>
<?php else: ?>
    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="card p-4 shadow-sm">
        <div class="mb-3">
            <label for="date" class="form-label">Date</label>
            <input type="date" class="form-control" id="date" name="date" value="<?= htmlspecialchars($data['date']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="category" class="form-label">Category</label>
            <select class="form-select" id="category" name="category" required>
                <option value="">Select category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= $data['category'] === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="amount" class="form-label">Amount (PKR)</label>
            <input type="number" step="0.01" class="form-control" id="amount" name="amount" value="<?= htmlspecialchars($data['amount']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="2"><?= htmlspecialchars($data['description']) ?></textarea>
        </div>
        <div class="mb-3">
            <label for="attachment" class="form-label">Attachment (optional)</label>
            <input class="form-control" type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.pdf">
        </div>
        <button type="submit" class="btn btn-success">Add Expense</button>
    </form>
<?php endif; ?>