<?php
echo "<h1>🔍 Database Status Check</h1>";

try {
    require_once 'includes/config.php';
    echo "✅ Database connection successful<br>";
    echo "Database: " . $db . "<br><br>";
    
    // Check what tables exist
    echo "<h2>📋 Existing Tables:</h2>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "❌ <strong>No tables found!</strong> The database is empty.<br>";
        echo "<p>You need to import the complete database structure first.</p>";
    } else {
        echo "✅ Found " . count($tables) . " tables:<br>";
        foreach ($tables as $table) {
            echo "  - <code>$table</code><br>";
        }
    }
    
    // Check if we have the essential tables
    echo "<h2>🔑 Essential Tables Check:</h2>";
    $essential_tables = [
        'system_users' => 'User authentication',
        'roles' => 'User roles and permissions',
        'customer' => 'Customer information',
        'products' => 'Product catalog',
        'suppliers' => 'Supplier information',
        'purchase' => 'Purchase records',
        'purchase_items' => 'Purchase line items',
        'sale' => 'Sales records',
        'sale_items' => 'Sales line items',
        'stock_items' => 'Inventory stock',
        'categories' => 'Product categories',
        'payment_method' => 'Payment methods',
        'expenses_category' => 'Expense categories',
        'expenses' => 'Expense records',
        'cash_transactions' => 'Cash flow tracking'
    ];
    
    foreach ($essential_tables as $table => $description) {
        $exists = in_array($table, $tables);
        echo ($exists ? "✅" : "❌") . " <code>$table</code> - $description<br>";
    }
    
    echo "<hr>";
    
    if (empty($tables)) {
        echo "<h2>🚀 Solution:</h2>";
        echo "<p><strong>You need to import the complete database structure!</strong></p>";
        echo "<ol>";
        echo "<li>Go to phpMyAdmin</li>";
        echo "<li>Select your <code>tailor_db</code> database</li>";
        echo "<li>Click <strong>Import</strong> tab</li>";
        echo "<li>Choose the file: <code>tailor_database_corrected_original.sql</code></li>";
        echo "<li>Click <strong>Go</strong> to import</li>";
        echo "</ol>";
        echo "<p><strong>OR</strong> run this command in MySQL:</p>";
        echo "<code>mysql -u root -p tailor_db < tailor_database_corrected_original.sql</code>";
    } elseif (count($tables) < 20) {
        echo "<h2>⚠️ Partial Database:</h2>";
        echo "<p>Some tables are missing. You should import the complete database structure.</p>";
    } else {
        echo "<h2>✅ Database Looks Good!</h2>";
        echo "<p>All essential tables are present. You can now:</p>";
        echo "<ol>";
        echo "<li>Run <code>setup_admin.php</code> to create admin user</li>";
        echo "<li>Try logging in with admin credentials</li>";
        echo "</ol>";
    }
    
} catch (Exception $e) {
    echo "❌ Database check failed: " . $e->getMessage() . "<br>";
    echo "<p>Please check your database connection in <code>includes/config.php</code></p>";
}
?>
