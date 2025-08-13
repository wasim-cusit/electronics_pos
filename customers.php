<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'customers';

// Handle Add Customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    $name = $_POST['name'];
    $mobile = $_POST['mobile'];
    $address = $_POST['address'];
    $email = $_POST['email'];
    $opening_balance = $_POST['opening_balance'] ?? 0.00;
    $status = $_POST['status'] ?? 1;

    $stmt = $pdo->prepare("INSERT INTO customer (name, mobile, address, email, opening_balance, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $mobile, $address, $email, $opening_balance, $status]);
    header("Location: customers.php?success=added");
    exit;
}

// Handle Edit Customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_customer'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $mobile = $_POST['mobile'];
    $address = $_POST['address'];
    $email = $_POST['email'];
    $opening_balance = $_POST['opening_balance'] ?? 0.00;
    $status = $_POST['status'] ?? 1;

    $stmt = $pdo->prepare("UPDATE customer SET name=?, mobile=?, address=?, email=?, opening_balance=?, status=? WHERE id=?");
    $stmt->execute([$name, $mobile, $address, $email, $opening_balance, $status, $id]);
    header("Location: customers.php?success=updated");
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

// If editing, fetch customer
$edit_customer = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM customer WHERE id = ?");
    $stmt->execute([$id]);
    $edit_customer = $stmt->fetch(PDO::FETCH_ASSOC);
}

include 'includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
            <h2 class="mb-4">Customers</h2>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php
                    if ($_GET['success'] === 'added') echo "Customer added successfully!";
                    if ($_GET['success'] === 'updated') echo "Customer updated successfully!";
                    if ($_GET['success'] === 'deleted') echo "Customer deleted successfully!";
                    ?>
                </div>
            <?php endif; ?>

            <!-- Add/Edit Customer Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <?= $edit_customer ? "Edit Customer" : "Add Customer" ?>
                </div>
                <div class="card-body">
                    <form method="post">
                        <?php if ($edit_customer): ?>
                            <input type="hidden" name="id" value="<?= $edit_customer['id'] ?>">
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($edit_customer['name'] ?? '') ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Mobile</label>
                                <input type="text" name="mobile" class="form-control" value="<?= htmlspecialchars($edit_customer['mobile'] ?? '') ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($edit_customer['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($edit_customer['address'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Opening Balance</label>
                                <input type="number" name="opening_balance" class="form-control" step="0.01" value="<?= htmlspecialchars($edit_customer['opening_balance'] ?? '0.00') ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <option value="1" <?= ($edit_customer['status'] ?? 1) == 1 ? 'selected' : '' ?>>Active</option>
                                    <option value="0" <?= ($edit_customer['status'] ?? 1) == 0 ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" name="<?= $edit_customer ? 'edit_customer' : 'add_customer' ?>">
                            <?= $edit_customer ? "Update Customer" : "Add Customer" ?>
                        </button>
                        <?php if ($edit_customer): ?>
                            <a href="customers.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Customer List Table -->
            <div class="card">
                <div class="card-header">Customer List</div>
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
                                    <td><?= $customer['status'] ? 'Active' : 'Inactive' ?></td>
                                    <td><?= htmlspecialchars($customer['created_at']) ?></td>
                                    <td>
                                        <a href="customers.php?edit=<?= $customer['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <a href="customers.php?delete=<?= $customer['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this customer?')">Delete</a>
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
<?php include 'includes/footer.php'; ?>