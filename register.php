<?php
require_once 'includes/auth.php';
require_once 'includes/flash.php';
require_once 'includes/config.php';
require_login();
if (!has_role('Admin')) {
    set_flash('danger', 'Access denied.');
    header('Location: ' . $base_url . 'dashboard.php');
    exit;
}
$activePage = '';
$roles = ['Admin' => 1, 'Manager' => 2, 'Cashier' => 3];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_id = intval($_POST['role'] ?? 2);
    if (!$username || !$password) {
        set_flash('danger', 'Username and password are required.');
    } elseif (register_user($username, $password, $role_id)) {
        set_flash('success', 'User registered successfully!');
        header('Location: ' . $base_url . 'register.php');
        exit;
    } else {
        set_flash('danger', 'Registration failed. Username may already exist.');
    }
}
include 'includes/header.php';
?>
<div class="container-fluid">
  <div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
      <h2 class="mb-4">Register New User</h2>
      <?= get_flash() ?>
      <form method="post" class="card p-4 shadow-sm" style="max-width:400px;">
        <div class="mb-3">
          <label for="username" class="form-label">Username</label>
          <input type="text" class="form-control" id="username" name="username" required>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="mb-3">
          <label for="role" class="form-label">Role</label>
          <select class="form-select" id="role" name="role">
            <?php foreach ($roles as $name => $id): ?>
              <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary">Register</button>
      </form>
    </main>
  </div>
</div>
<?php include 'includes/footer.php'; ?>