<?php
require_once 'includes/auth.php';
require_once 'includes/flash.php';
require_once 'includes/config.php';
require_login();
if (!has_role('Admin')) {
    set_flash('danger', 'Access denied.');
    header('Location: ' . $base_url . 'users.php');
    exit;
}
$roles = $pdo->query('SELECT * FROM roles')->fetchAll();
$id = intval($_GET['id'] ?? 0);
if (!$id) {
    set_flash('danger', 'Invalid user ID.');
    header('Location: ' . $base_url . 'users.php');
    exit;
}
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) {
    set_flash('danger', 'User not found.');
    header('Location: ' . $base_url . 'users.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_id = intval($_POST['role'] ?? $user['role_id']);
    if (!$username) {
        set_flash('danger', 'Username is required.');
    } else {
        if ($password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET username=?, password=?, role_id=? WHERE id=?');
            $ok = $stmt->execute([$username, $hash, $role_id, $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE users SET username=?, role_id=? WHERE id=?');
            $ok = $stmt->execute([$username, $role_id, $id]);
        }
        if ($ok) {
            set_flash('success', 'User updated successfully.');
            header('Location: ' . $base_url . 'users.php');
            exit;
        } else {
            set_flash('danger', 'Update failed.');
        }
    }
}
$activePage = 'users';
include 'includes/header.php';
?>
<div class="container-fluid">
  <div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
      <h2 class="mb-4">Edit User</h2>
      <?= get_flash() ?>
      <form method="post" class="card p-4 shadow-sm" style="max-width:400px;">
        <div class="mb-3">
          <label for="username" class="form-label">Username</label>
          <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">New Password (leave blank to keep current)</label>
          <input type="password" class="form-control" id="password" name="password">
        </div>
        <div class="mb-3">
          <label for="role" class="form-label">Role</label>
          <select class="form-select" id="role" name="role">
            <?php foreach ($roles as $role): ?>
              <option value="<?= $role['id'] ?>" <?= $user['role_id']==$role['id']?'selected':'' ?>><?= htmlspecialchars($role['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary">Update User</button>
        <a href="<?= $base_url ?>users.php" class="btn btn-secondary ms-2">Cancel</a>
      </form>
    </main>
  </div>
</div>
<?php include 'includes/footer.php'; ?>