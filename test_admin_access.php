<?php
echo "<h1>🔍 Admin Access Test Script</h1>";

// Test 1: Database Connection
echo "<h2>1. Database Connection Test</h2>";
try {
    require_once 'includes/config.php';
    echo "✅ Database connection successful<br>";
    echo "Database: " . $db . "<br>";
    echo "Host: " . $host . "<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Check if tables exist
echo "<h2>2. Table Existence Test</h2>";
$required_tables = ['system_users', 'roles', 'customer', 'products', 'sale', 'sale_items'];
foreach ($required_tables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        echo ($exists ? "✅" : "❌") . " Table '$table' " . ($exists ? "exists" : "missing") . "<br>";
    } catch (Exception $e) {
        echo "❌ Error checking table '$table': " . $e->getMessage() . "<br>";
    }
}

// Test 3: Check roles table
echo "<h2>3. Roles Table Test</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM roles ORDER BY id");
    $roles = $stmt->fetchAll();
    echo "✅ Roles table has " . count($roles) . " roles:<br>";
    foreach ($roles as $role) {
        echo "  - ID: {$role['id']}, Name: {$role['role_name']}<br>";
    }
} catch (Exception $e) {
    echo "❌ Error reading roles: " . $e->getMessage() . "<br>";
}

// Test 4: Check system_users table
echo "<h2>4. System Users Table Test</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM system_users ORDER BY id");
    $users = $stmt->fetchAll();
    echo "✅ System users table has " . count($users) . " users:<br>";
    foreach ($users as $user) {
        echo "  - ID: {$user['id']}, Username: {$user['username']}, Role ID: {$user['role_id']}, Status: {$user['status']}<br>";
    }
} catch (Exception $e) {
    echo "❌ Error reading system_users: " . $e->getMessage() . "<br>";
}

// Test 5: Check admin user specifically
echo "<h2>5. Admin User Test</h2>";
try {
    $stmt = $pdo->prepare("SELECT u.*, r.role_name FROM system_users u JOIN roles r ON u.role_id = r.id WHERE u.username = ?");
    $stmt->execute(['admin']);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "✅ Admin user found:<br>";
        echo "  - Username: {$admin['username']}<br>";
        echo "  - Role ID: {$admin['role_id']}<br>";
        echo "  - Role Name: {$admin['role_name']}<br>";
        echo "  - Status: {$admin['status']}<br>";
        
        // Test password
        $test_password = 'admin123';
        if (password_verify($test_password, $admin['password'])) {
            echo "✅ Password 'admin123' is correct<br>";
        } else {
            echo "❌ Password 'admin123' is incorrect<br>";
            echo "  - Current hash: " . substr($admin['password'], 0, 20) . "...<br>";
        }
    } else {
        echo "❌ Admin user not found<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking admin user: " . $e->getMessage() . "<br>";
}

// Test 6: Test login function
echo "<h2>6. Login Function Test</h2>";
try {
    require_once 'includes/auth.php';
    
    // Test with correct credentials
    $result = login('admin', 'admin123');
    echo "Login test result: " . ($result ? "✅ Success" : "❌ Failed") . "<br>";
    
    if ($result) {
        echo "Session data after login:<br>";
        echo "<pre>";
        print_r($_SESSION);
        echo "</pre>";
        
        // Test role check
        echo "Has Admin role: " . (has_role('Admin') ? "✅ YES" : "❌ NO") . "<br>";
        
        // Clean up session
        logout();
        echo "✅ Session cleaned up<br>";
    }
} catch (Exception $e) {
    echo "❌ Error testing login: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h2>📋 Summary</h2>";
echo "<p>If you see any ❌ errors above, those need to be fixed first.</p>";
echo "<p>To fix admin access:</p>";
echo "<ol>";
echo "<li>Run <code>reset_admin_password.php</code> to reset admin password</li>";
echo "<li>Try logging in with username: <strong>admin</strong> and password: <strong>admin123</strong></li>";
echo "<li>Check the error logs for any role check issues</li>";
echo "</ol>";
?>
