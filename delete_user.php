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
$id = intval($_GET['id'] ?? 0);
if (!$id) {
    set_flash('danger', 'Invalid user ID.');
    header('Location: ' . $base_url . 'users.php');
    exit;
}
if ($id == $_SESSION['user_id']) {
    set_flash('danger', 'You cannot delete your own account.');
    header('Location: ' . $base_url . 'users.php');
    exit;
}
$stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
if ($stmt->execute([$id])) {
    set_flash('success', 'User deleted successfully.');
} else {
    set_flash('danger', 'Delete failed.');
}
header('Location: ' . $base_url . 'users.php');
exit;