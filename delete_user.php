<?php
require_once 'includes/auth.php';
require_login();

// Check if user has admin role
if (!has_role('Admin')) {
    header('Location: dashboard.php');
    exit;
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Don't allow admin to delete themselves
    if ($id == $_SESSION['user_id']) {
        header('Location: users.php?error=self_delete');
        exit;
    }
    
    require_once 'includes/config.php';
    
    try {
        $stmt = $pdo->prepare('DELETE FROM system_users WHERE id = ?');
        $stmt->execute([$id]);
        
        header('Location: users.php?success=deleted');
    } catch (Exception $e) {
        header('Location: users.php?error=delete_failed');
    }
} else {
    header('Location: users.php');
}
exit;