<?php
echo "<h1>🔧 Database Fix Script</h1>";

try {
    require_once 'includes/config.php';
    echo "✅ Database connection successful<br>";
    echo "Database: " . $db . "<br><br>";
    
    // Step 1: Create roles table
    echo "<h2>1. Creating Roles Table</h2>";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `roles` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `role_name` varchar(100) NOT NULL,
            `permissions` text DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        echo "✅ Roles table created/verified<br>";
        
        // Insert default roles
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM roles");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $pdo->exec("INSERT INTO `roles` (`id`, `role_name`, `permissions`) VALUES
                (1, 'Admin', 'all'),
                (2, 'Manager', 'sales,purchases,customers,suppliers,reports'),
                (3, 'Tailor', 'sales,customers'),
                (4, 'Cashier', 'sales,customers')");
            echo "✅ Default roles inserted<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error with roles: " . $e->getMessage() . "<br>";
    }
    
    // Step 2: Create system_users table
    echo "<h2>2. Creating System Users Table</h2>";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `system_users` (
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
        echo "✅ System users table created/verified<br>";
    } catch (Exception $e) {
        echo "❌ Error with system_users: " . $e->getMessage() . "<br>";
    }
    
    // Step 3: Create categories table
    echo "<h2>3. Creating Categories Table</h2>";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `categories` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `category_name` varchar(100) NOT NULL,
            `description` text DEFAULT NULL,
            `status` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        echo "✅ Categories table created/verified<br>";
        
        // Insert default categories
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $pdo->exec("INSERT INTO `categories` (`category_name`, `description`) VALUES
                ('Shirts', 'Men and women shirts'),
                ('Pants', 'Trousers and pants'),
                ('Dresses', 'Women dresses'),
                ('Suits', 'Formal suits'),
                ('Accessories', 'Belts, ties, etc')");
            echo "✅ Default categories inserted<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error with categories: " . $e->getMessage() . "<br>";
    }
    
    // Step 4: Create suppliers table (this was missing!)
    echo "<h2>4. Creating Suppliers Table</h2>";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `suppliers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `supplier_name` varchar(100) NOT NULL,
            `contact_person` varchar(100) DEFAULT NULL,
            `email` varchar(100) DEFAULT NULL,
            `phone` varchar(20) DEFAULT NULL,
            `address` text DEFAULT NULL,
            `status` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        echo "✅ Suppliers table created/verified<br>";
        
        // Insert default supplier
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM suppliers");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $pdo->exec("INSERT INTO `suppliers` (`supplier_name`, `contact_person`, `email`, `phone`) VALUES
                ('General Textiles', 'John Doe', 'john@textiles.com', '+1234567890')");
            echo "✅ Default supplier inserted<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error with suppliers: " . $e->getMessage() . "<br>";
    }
    
    // Step 5: Create products table
    echo "<h2>5. Creating Products Table</h2>";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `products` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `product_name` varchar(255) NOT NULL,
            `brand` varchar(100) DEFAULT NULL,
            `product_unit` varchar(50) NOT NULL,
            `product_code` varchar(100) NOT NULL,
            `category_id` int(11) NOT NULL,
            `alert_quantity` int(11) NOT NULL DEFAULT 0,
            `description` text DEFAULT NULL,
            `product_image` varchar(255) DEFAULT NULL,
            `status` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `product_code` (`product_code`),
            KEY `category_id` (`category_id`),
            CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        echo "✅ Products table created/verified<br>";
    } catch (Exception $e) {
        echo "❌ Error with products: " . $e->getMessage() . "<br>";
    }
    
    // Step 6: Create stock_items table
    echo "<h2>6. Creating Stock Items Table</h2>";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `stock_items` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `product_id` int(11) NOT NULL,
            `purchase_item_id` int(11) DEFAULT NULL,
            `product_code` varchar(100) NOT NULL,
            `quantity` int(11) NOT NULL,
            `purchase_price` decimal(15,2) NOT NULL,
            `sale_price` decimal(15,2) NOT NULL,
            `stock_date` date NOT NULL,
            `status` enum('available','reserved','sold') NOT NULL DEFAULT 'available',
            PRIMARY KEY (`id`),
            KEY `product_id` (`product_id`),
            KEY `product_code` (`product_code`),
            CONSTRAINT `fk_stock_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        echo "✅ Stock items table created/verified<br>";
    } catch (Exception $e) {
        echo "❌ Error with stock_items: " . $e->getMessage() . "<br>";
    }
    
    // Step 7: Create customer table
    echo "<h2>7. Creating Customer Table</h2>";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `customer` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `customer_name` varchar(100) NOT NULL,
            `email` varchar(100) DEFAULT NULL,
            `phone` varchar(20) DEFAULT NULL,
            `address` text DEFAULT NULL,
            `status` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        echo "✅ Customer table created/verified<br>";
    } catch (Exception $e) {
        echo "❌ Error with customer: " . $e->getMessage() . "<br>";
    }
    
    // Step 8: Create payment_method table
    echo "<h2>8. Creating Payment Method Table</h2>";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `payment_method` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `method_name` varchar(100) NOT NULL,
            `description` text DEFAULT NULL,
            `status` tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        echo "✅ Payment method table created/verified<br>";
        
        // Insert default payment methods
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM payment_method");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $pdo->exec("INSERT INTO `payment_method` (`method_name`, `description`) VALUES
                ('Cash', 'Cash payment'),
                ('Card', 'Credit/Debit card'),
                ('Bank Transfer', 'Bank transfer'),
                ('Mobile Money', 'Mobile payment')");
            echo "✅ Default payment methods inserted<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error with payment_method: " . $e->getMessage() . "<br>";
    }
    
    // Step 9: Create admin user
    echo "<h2>9. Creating Admin User</h2>";
    try {
        $stmt = $pdo->prepare("SELECT id FROM system_users WHERE username = ?");
        $stmt->execute(['admin']);
        $admin_exists = $stmt->fetch();
        
        if (!$admin_exists) {
            $password = 'admin123';
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO system_users (role_id, status, name, username, email, password, contact, address, signupdate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())");
            $stmt->execute([1, 1, 'Administrator', 'admin', 'admin@tailorshop.com', $hash, '+1234567890', '123 Main Street, City, Country']);
            
            echo "✅ Admin user created<br>";
            echo "  - Username: <strong>admin</strong><br>";
            echo "  - Password: <strong>admin123</strong><br>";
        } else {
            echo "✅ Admin user already exists<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error creating admin user: " . $e->getMessage() . "<br>";
    }
    
    echo "<hr>";
    echo "<h2>🎯 Database Setup Complete!</h2>";
    echo "<p>✅ All essential tables have been created</p>";
    echo "<p>✅ Admin user is ready</p>";
    echo "<p><strong>Now you can:</strong></p>";
    echo "<ol>";
    echo "<li>Try logging in with username: <code>admin</code> and password: <code>admin123</code></li>";
    echo "<li>Delete this file for security</li>";
    echo "<li>Start using your tailor system!</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "❌ Database setup failed: " . $e->getMessage() . "<br>";
}
?>
