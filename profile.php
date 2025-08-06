<?php
require_once 'includes/auth.php';
require_once 'includes/flash.php';
require_login();
$activePage = '';
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newpass = $_POST['new_password'] ?? '';
    $confirmpass = $_POST['confirm_password'] ?? '';
    if (!$newpass || !$confirmpass) {
        set_flash('danger', 'Both password fields are required.');
    } elseif ($newpass !== $confirmpass) {
        set_flash('danger', 'Passwords do not match.');
    } else {
        global $pdo;
        $hash = password_hash($newpass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password=? WHERE id=?');
        if ($stmt->execute([$hash, $_SESSION['user_id']])) {
            set_flash('success', 'Password updated successfully.');
        } else {
            set_flash('danger', 'Failed to update password.');
        }
    }
}
include 'includes/header.php';
?>
<div class="container-fluid">
  <div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <main class="col-md-10 ms-sm-auto px-4 py-5" style="margin-top: 25px;">
      <h2 class="mb-4">Profile / Settings</h2>
      <?= get_flash() ?>
      <div class="card p-4 shadow-sm" style="max-width:400px;">
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars(current_user()) ?>" disabled>
        </div>
        <form method="post">
          <div class="mb-3">
            <label for="new_password" class="form-label">New Password</label>
            <input type="password" class="form-control" id="new_password" name="new_password" required>
          </div>
          <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
          </div>
          <button type="submit" class="btn btn-primary">Change Password</button>
        </form>
      </div>
    </main>
  </div>
</div>
<?php include 'includes/footer.php'; ?>