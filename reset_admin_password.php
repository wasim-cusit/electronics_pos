<?php
require_once 'includes/config.php';

// This script resets the admin password to 'admin123'
// Run this once and then delete the file for security

try {
    $new_password = 'admin123';
    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare('UPDATE system_users SET password = ? WHERE username = ?');
    $result = $stmt->execute([$hash, 'admin']);
    
    if ($result) {
        echo "✅ Admin password reset successfully!<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
        echo "<br>⚠️ Please delete this file now for security!";
    } else {
        echo "❌ Failed to reset password";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
