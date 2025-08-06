<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';
require_login();
if (!has_role('Admin')) {
    header('Location: ' . $base_url . 'dashboard.php');
    exit;
}
$activePage = 'users';
include 'includes/header.php';

// Fetch all users and their roles
$stmt = $pdo->query('SELECT users.id, users.username, roles.name AS role FROM users JOIN roles ON users.role_id = roles.id ORDER BY users.id');
$users = $stmt->fetchAll();
?>
<div class="container-fluid">
  <div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
      <h2 class="mb-4">User Management</h2>
      <a href="<?= $base_url ?>register.php" class="btn btn-success mb-3">Add New User</a>
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>
            <tr>
              <td><?= htmlspecialchars($user['id']) ?></td>
              <td><?= htmlspecialchars($user['username']) ?></td>
              <td><?= htmlspecialchars($user['role']) ?></td>
              <td>
                <a href="<?= $base_url ?>edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                <a href="<?= $base_url ?>delete_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </main>
  </div>
</div>
<?php include 'includes/footer.php'; ?>