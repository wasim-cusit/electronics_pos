<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

$activePage = 'suppliers';

// Handle Add Supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_supplier'])) {
    $name = $_POST['name'];
    $contact = $_POST['contact'];
    $address = $_POST['address'];
    $email = $_POST['email'];

    $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact, address, email) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $contact, $address, $email]);
    header("Location: suppliers.php?success=added");
    exit;
}

// Handle Edit Supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_supplier'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $contact = $_POST['contact'];
    $address = $_POST['address'];
    $email = $_POST['email'];

    $stmt = $pdo->prepare("UPDATE suppliers SET name=?, contact=?, address=?, email=? WHERE id=?");
    $stmt->execute([$name, $contact, $address, $email, $id]);
    header("Location: suppliers.php?success=updated");
    exit;
}

// Handle Delete Supplier
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: suppliers.php?success=deleted");
    exit;
}

// Fetch all suppliers
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// If editing, fetch supplier
$edit_supplier = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$id]);
    $edit_supplier = $stmt->fetch(PDO::FETCH_ASSOC);
}

include 'includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
            <h2 class="mb-4">Suppliers</h2>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php
                    if ($_GET['success'] === 'added') echo "Supplier added successfully!";
                    if ($_GET['success'] === 'updated') echo "Supplier updated successfully!";
                    if ($_GET['success'] === 'deleted') echo "Supplier deleted successfully!";
                    ?>
                </div>
            <?php endif; ?>

            <!-- Add/Edit Supplier Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <?= $edit_supplier ? "Edit Supplier" : "Add Supplier" ?>
                </div>
                <div class="card-body">
                    <form method="post">
                        <?php if ($edit_supplier): ?>
                            <input type="hidden" name="id" value="<?= $edit_supplier['id'] ?>">
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($edit_supplier['name'] ?? '') ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Contact</label>
                                <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($edit_supplier['contact'] ?? '') ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($edit_supplier['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($edit_supplier['address'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" name="<?= $edit_supplier ? 'edit_supplier' : 'add_supplier' ?>">
                            <?= $edit_supplier ? "Update Supplier" : "Add Supplier" ?>
                        </button>
                        <?php if ($edit_supplier): ?>
                            <a href="suppliers.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
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
                                <th>Address</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($suppliers as $supplier): ?>
                                <tr>
                                    <td><?= htmlspecialchars($supplier['name']) ?></td>
                                    <td><?= htmlspecialchars($supplier['contact']) ?></td>
                                    <td><?= htmlspecialchars($supplier['email']) ?></td>
                                    <td><?= htmlspecialchars($supplier['address']) ?></td>
                                    <td><?= htmlspecialchars($supplier['created_at']) ?></td>
                                    <td>
                                        <a href="suppliers.php?edit=<?= $supplier['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <a href="supplier_history.php?id=<?= $supplier['id'] ?>" class="btn btn-sm btn-info">History</a>
                                        <a href="suppliers.php?delete=<?= $supplier['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this supplier?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($suppliers)): ?>
                                <tr><td colspan="6" class="text-center">No suppliers found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
<?php include 'includes/footer.php'; ?>