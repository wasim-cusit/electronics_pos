<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';

echo "<h2>Session Debug Information</h2>";

echo "<h3>Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (is_logged_in()) {
    echo "<h3>User Information:</h3>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    echo "Username: " . $_SESSION['username'] . "<br>";
    echo "Role ID: " . $_SESSION['role_id'] . "<br>";
    
    echo "<h3>Role Check:</h3>";
    echo "Is Admin: " . (has_role('Admin') ? 'YES' : 'NO') . "<br>";
    
    // Check database directly
    try {
        $stmt = $pdo->prepare('SELECT r.role_name, u.username, u.role_id FROM system_users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user_role = $stmt->fetch();
        
        echo "<h3>Database Role Information:</h3>";
        echo "Username: " . $user_role['username'] . "<br>";
        echo "Role ID: " . $user_role['role_id'] . "<br>";
        echo "Role Name: " . $user_role['role_name'] . "<br>";
        
    } catch (Exception $e) {
        echo "Database Error: " . $e->getMessage();
    }
    
} else {
    echo "<p>Not logged in</p>";
    echo "<p><a href='login.php'>Go to Login</a></p>";
}
?>
