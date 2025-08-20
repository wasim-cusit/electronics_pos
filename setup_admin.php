<?php
echo "<h1>🔧 Admin Setup Script</h1>";

try {
    require_once 'includes/config.php';
    echo "✅ Database connection successful<br><br>";
    
    // Step 1: Check if roles table exists, if not create it
    echo "<h2>1. Setting up Roles Table</h2>";
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'roles'");
        if ($stmt->rowCount() == 0) {
            // Create roles table
            $pdo->exec("CREATE TABLE `roles` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `role_name` varchar(100) NOT NULL,
                `permissions` text DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
            echo "✅ Created roles table<br>";
        } else {
            echo "✅ Roles table already exists<br>";
        }
        
        // Insert default roles
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM roles");
        $stmt->execute();
        $role_count = $stmt->fetchColumn();
        
        if ($role_count == 0) {
            $pdo->exec("INSERT INTO `roles` (`id`, `role_name`, `permissions`) VALUES
                (1, 'Admin', 'all'),
                (2, 'Manager', 'sales,purchases,customers,suppliers,reports'),
                -- (3, 'Tailor', 'sales,customers'),
                (4, 'Cashier', 'sales,customers')");
            echo "✅ Inserted default roles<br>";
        } else {
            echo "✅ Roles already exist ($role_count found)<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error with roles: " . $e->getMessage() . "<br>";
    }
    
    // Step 2: Check if system_users table exists, if not create it
    echo "<h2>2. Setting up System Users Table</h2>";
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'system_users'");
        if ($stmt->rowCount() == 0) {
            // Create system_users table
            $pdo->exec("CREATE TABLE `system_users` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `role_id` int(11) NOT NULL,
                `status` int(11) NOT NULL DEFAULT 1,
                `name` varchar(100) NOT NULL,
                `username` varchar(50) NOT NULL,
                `email` varchar(50) NOT NULL,
                `password` varchar(100) NOT NULL,
                `contact` varchar(50) NOT NULL,
                `image` varchar(255) DEFAULT NULL,
                `address` varchar(255) NOT NULL,
                `signupdate` date NOT NULL,
                `last_login` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
            echo "✅ Created system_users table<br>";
        } else {
            echo "✅ System users table already exists<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error with system_users: " . $e->getMessage() . "<br>";
    }
    
    // Step 3: Create admin user
    echo "<h2>3. Creating Admin User</h2>";
    try {
        // Check if admin user exists
        $stmt = $pdo->prepare("SELECT id FROM system_users WHERE username = ?");
        $stmt->execute(['admin']);
        $admin_exists = $stmt->fetch();
        
        if (!$admin_exists) {
            // Create admin user
            $password = 'admin123';
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO system_users (role_id, status, name, username, email, password, contact, address, signupdate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())");
            $stmt->execute([1, 1, 'Administrator', 'admin', 'admin@tailorshop.com', $hash, '+1234567890', '123 Main Street, City, Country']);
            
            echo "✅ Created admin user<br>";
            echo "  - Username: <strong>admin</strong><br>";
            echo "  - Password: <strong>admin123</strong><br>";
        } else {
            // Update admin password
            $password = 'admin123';
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE system_users SET password = ?, role_id = 1, status = 1 WHERE username = ?");
            $stmt->execute([$hash, 'admin']);
            
            echo "✅ Updated admin user password<br>";
            echo "  - Username: <strong>admin</strong><br>";
            echo "  - Password: <strong>admin123</strong><br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error creating admin user: " . $e->getMessage() . "<br>";
    }
    
    // Step 4: Verify setup
    echo "<h2>4. Verification</h2>";
    try {
        $stmt = $pdo->prepare("SELECT u.*, r.role_name FROM system_users u JOIN roles r ON u.role_id = r.id WHERE u.username = ?");
        $stmt->execute(['admin']);
        $admin = $stmt->fetch();
        
        if ($admin) {
            echo "✅ Admin user verified:<br>";
            echo "  - Username: {$admin['username']}<br>";
            echo "  - Role: {$admin['role_name']}<br>";
            echo "  - Status: " . ($admin['status'] == 1 ? 'Active' : 'Inactive') . "<br>";
            
            // Test password
            if (password_verify('admin123', $admin['password'])) {
                echo "  - Password: ✅ Correct<br>";
            } else {
                echo "  - Password: ❌ Incorrect<br>";
            }
        } else {
            echo "❌ Admin user not found after setup<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error verifying setup: " . $e->getMessage() . "<br>";
    }
    
    echo "<hr>";
    echo "<h2>🎯 Next Steps</h2>";
    echo "<p>1. <strong>Try logging in</strong> with username: <code>admin</code> and password: <code>admin123</code></p>";
    echo "<p>2. <strong>Delete this file</strong> for security after successful login</p>";
    echo "<p>3. <strong>Run the test script</strong> to verify everything is working: <code>test_admin_access.php</code></p>";
    
} catch (Exception $e) {
    echo "❌ Setup failed: " . $e->getMessage() . "<br>";
    echo "<p>Please check your database connection in <code>includes/config.php</code></p>";
}
?>
