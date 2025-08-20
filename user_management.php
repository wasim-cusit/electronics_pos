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

$error = '';
$success = '';
$action = $_GET['action'] ?? 'list';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        // Add new user
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $role_id = intval($_POST['role_id'] ?? 0);
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($name) || empty($username) || empty($email) || empty($role_id) || empty($password)) {
            $error = 'Please fill in all required fields.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else {
            try {
                // Check if username already exists
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM system_users WHERE username = ?');
                $stmt->execute([$username]);
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Username already exists. Please choose a different username.';
                } else {
                    // Check if email already exists
                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM system_users WHERE email = ?');
                    $stmt->execute([$email]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = 'Email already exists. Please use a different email.';
                    } else {
                        // Hash password and insert user
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare('INSERT INTO system_users (name, username, email, contact, address, role_id, password, status, signupdate) VALUES (?, ?, ?, ?, ?, ?, ?, 1, CURDATE())');
                        $stmt->execute([$name, $username, $email, $contact, $address, $role_id, $hashed_password]);
                        
                        $success = 'User added successfully!';
                        $action = 'list'; // Switch back to list view
                    }
                }
            } catch (Exception $e) {
                $error = 'Failed to add user: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['edit_user'])) {
        // Update existing user
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $role_id = intval($_POST['role_id'] ?? 0);
        $status = intval($_POST['status'] ?? 1);
        
        if (empty($name) || empty($username) || empty($email) || empty($role_id)) {
            $error = 'Please fill in all required fields.';
        } else {
            try {
                $stmt = $pdo->prepare('UPDATE system_users SET name = ?, username = ?, email = ?, contact = ?, address = ?, role_id = ?, status = ? WHERE id = ?');
                $stmt->execute([$name, $username, $email, $contact, $address, $role_id, $status, $id]);
                
                $success = 'User updated successfully!';
                $action = 'list'; // Switch back to list view
            } catch (Exception $e) {
                $error = 'Update failed: ' . $e->getMessage();
            }
        }
    }
}

// Fetch roles for dropdowns
try {
    $roles = $pdo->query('SELECT * FROM roles ORDER BY role_name')->fetchAll();
    if (empty($roles)) {
        $error = 'No roles found in the database. Please check the roles table.';
    }
} catch (Exception $e) {
    $error = 'Error fetching roles: ' . $e->getMessage();
    $roles = [];
}

// Fetch all users for list view
if ($action === 'list') {
    try {
        $stmt = $pdo->query('SELECT system_users.id, system_users.username, system_users.name, system_users.email, system_users.status, system_users.contact, system_users.signupdate, roles.role_name AS role FROM system_users JOIN roles ON system_users.role_id = roles.id ORDER BY system_users.id');
        $users = $stmt->fetchAll();
    } catch (Exception $e) {
        $error = 'Error fetching users: ' . $e->getMessage();
        $users = [];
    }
}

// Fetch user data for edit view
if ($action === 'edit' && isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    try {
        $stmt = $pdo->prepare('SELECT * FROM system_users WHERE id = ?');
        $stmt->execute([$edit_id]);
        $edit_user = $stmt->fetch();
        if (!$edit_user) {
            $error = 'User not found.';
            $action = 'list';
        }
    } catch (Exception $e) {
        $error = 'Error fetching user: ' . $e->getMessage();
        $action = 'list';
    }
}

include 'includes/header.php';
?>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="bi bi-people-fill text-primary me-2"></i>
            User Management
        </h2>
        <div class="btn-group" role="group">
            <a href="?action=list" class="btn btn-outline-primary <?= $action === 'list' ? 'active' : '' ?>">
                <i class="bi bi-list-ul me-2"></i>All Users
            </a>
            <a href="?action=add" class="btn btn-outline-primary <?= $action === 'add' ? 'active' : '' ?>">
                <i class="bi bi-person-plus me-2"></i>Add User
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($action === 'list'): ?>
        <!-- List Users View -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <h5 class="card-title mb-0">System Users</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Contact</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Signup Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= $user['id'] ?></td>
                                        <td><?= htmlspecialchars($user['name']) ?></td>
                                        <td><?= htmlspecialchars($user['username']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><?= htmlspecialchars($user['contact'] ?? '') ?></td>
                                        <td>
                                            <span class="badge bg-primary"><?= htmlspecialchars($user['role']) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($user['status'] == 1): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($user['signupdate']) ?></td>
                                        <td>
                                            <a href="?action=edit&id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <a href="delete_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this user?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No users found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php elseif ($action === 'add'): ?>
        <!-- Add User View -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <h5 class="card-title mb-0">Add New User</h5>
            </div>
            <div class="card-body">
                <form method="post" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback">Please provide a full name.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" required>
                            <div class="invalid-feedback">Please provide a username.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="invalid-feedback">Please provide a valid email address.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="contact" class="form-label">Contact Number</label>
                            <input type="text" class="form-control" id="contact" name="contact">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" id="role_id" name="role_id" required>
                                <option value="">Select Role</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['role_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a role.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="invalid-feedback">Please provide a password (minimum 6 characters).</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <div class="invalid-feedback">Please confirm your password.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" name="add_user" class="btn btn-primary">
                            <i class="bi bi-person-plus me-2"></i>Add User
                        </button>
                        <a href="?action=list" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Back to Users
                        </a>
                    </div>
                </form>
            </div>
        </div>

    <?php elseif ($action === 'edit' && isset($edit_user)): ?>
        <!-- Edit User View -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <h5 class="card-title mb-0">Edit User: <?= htmlspecialchars($edit_user['name']) ?></h5>
            </div>
            <div class="card-body">
                <form method="post" class="needs-validation" novalidate>
                    <input type="hidden" name="id" value="<?= $edit_user['id'] ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($edit_user['name']) ?>" required>
                            <div class="invalid-feedback">Please provide a full name.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($edit_user['username']) ?>" required>
                            <div class="invalid-feedback">Please provide a username.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($edit_user['email']) ?>" required>
                            <div class="invalid-feedback">Please provide a valid email address.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="contact" class="form-label">Contact Number</label>
                            <input type="text" class="form-control" id="contact" name="contact" value="<?= htmlspecialchars($edit_user['contact'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" id="role_id" name="role_id" required>
                                <option value="">Select Role</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['id'] ?>" <?= ($edit_user['role_id'] == $role['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($role['role_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a role.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="1" <?= $edit_user['status'] == 1 ? 'selected' : '' ?>>Active</option>
                                <option value="0" <?= $edit_user['status'] == 0 ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($edit_user['address'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" name="edit_user" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Update User
                        </button>
                        <a href="?action=list" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Back to Users
                        </a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>

<?php include 'includes/footer.php'; ?>
