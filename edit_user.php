<?php
require_once 'includes/auth.php';
require_login();

// Check if user has admin role
if (!has_role('Admin')) {
    header('Location: dashboard.php');
    exit;
}

require_once 'includes/config.php';
$activePage = 'users';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: users.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact']);
    $address = trim($_POST['address']);
    $role_id = intval($_POST['role_id']);
    $status = intval($_POST['status']);
    
    // Validation
    if (empty($name) || empty($username) || empty($email) || empty($role_id)) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            $stmt = $pdo->prepare('UPDATE system_users SET name = ?, username = ?, email = ?, contact = ?, address = ?, role_id = ?, status = ? WHERE id = ?');
            $stmt->execute([$name, $username, $email, $contact, $address, $role_id, $status, $id]);
            
            header('Location: users.php?success=updated');
            exit;
        } catch (Exception $e) {
            $error = 'Update failed: ' . $e->getMessage();
        }
    }
}

// Fetch user data
$stmt = $pdo->prepare('SELECT * FROM system_users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: users.php');
    exit;
}

// Fetch roles for dropdown
try {
    $roles = $pdo->query('SELECT * FROM roles ORDER BY role_name')->fetchAll();
    if (empty($roles)) {
        $error = 'No roles found in the database. Please check the roles table.';
    }
} catch (Exception $e) {
    $error = 'Error fetching roles: ' . $e->getMessage();
    $roles = [];
}

// Debug information (remove this in production)
if (isset($_GET['debug'])) {
    echo "<div class='alert alert-info'>";
    echo "<strong>Debug Info:</strong><br>";
    echo "User ID: " . $user['id'] . "<br>";
    echo "User Role ID: " . $user['role_id'] . "<br>";
    echo "Available Roles:<br>";
    foreach ($roles as $role) {
        echo "- ID: " . $role['id'] . ", Name: " . $role['role_name'] . "<br>";
    }
    echo "</div>";
}

include 'includes/header.php';
?>

<style>
.form-control.is-valid {
    border-color: #198754;
    padding-right: calc(1.5em + 0.75rem);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='m2.3 6.73.94-.94 3.47-3.47L7.1 1.86 6.13.9 4.7 2.33 2.3 4.73l.94.94z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.form-control.is-invalid {
    border-color: #dc3545;
    padding-right: calc(1.5em + 0.75rem);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 4.6 2.4 2.4m0-2.4L5.8 7'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.alert {
    margin-bottom: 1rem;
}
</style>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="bi bi-pencil-square text-primary me-2"></i>
            Edit User
        </h2>
        <a href="users.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Users
        </a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0">
            <h5 class="card-title mb-0">Edit User: <?= htmlspecialchars($user['name']) ?></h5>
        </div>
        <div class="card-body">
            <form method="post">
                <!-- Debug info for testing -->
                <?php if (isset($_GET['debug'])): ?>
                    <div class="alert alert-info mb-3">
                        <strong>Form Debug:</strong><br>
                        User Role ID: <?= $user['role_id'] ?><br>
                        Available Roles: <?= count($roles) ?><br>
                        <?php foreach ($roles as $role): ?>
                            Role ID: <?= $role['id'] ?>, Name: <?= $role['role_name'] ?><br>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Contact</label>
                        <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($user['contact']) ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Role</label>
                        <?php if (!empty($roles)): ?>
                            <select name="role_id" class="form-control" required>
                                <option value="">Select Role</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['id'] ?>" <?= $role['id'] == $user['role_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($role['role_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                No roles available. Please add roles to the database first.
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control" required>
                            <option value="1" <?= $user['status'] == 1 ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= $user['status'] == 0 ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($user['address']) ?></textarea>
                </div>
                </div>
                
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="updateBtn">
                        <i class="bi bi-check-circle me-2"></i>Update User
                    </button>
                    <a href="users.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const updateBtn = document.getElementById('updateBtn');
    
    form.addEventListener('submit', function(e) {
        const roleSelect = document.querySelector('select[name="role_id"]');
        const selectedRole = roleSelect.value;
        
        if (!selectedRole || selectedRole === '') {
            e.preventDefault();
            alert('Please select a role for the user.');
            roleSelect.focus();
            return false;
        }
        
        // Show loading state
        updateBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Updating...';
        updateBtn.disabled = true;
    });
    
    // Add visual feedback for role selection
    const roleSelect = document.querySelector('select[name="role_id"]');
    roleSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        } else {
            this.classList.remove('is-valid');
            this.classList.add('is-invalid');
        }
    });
});
</script>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>