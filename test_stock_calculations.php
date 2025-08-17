<?php
require_once 'includes/config.php';

echo "<h1>Stock Calculations Test</h1>";

try {
    // Test 1: Check if stock_items table exists and has data
    echo "<h2>Test 1: Database Structure</h2>";
    
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'stock_items'");
    $stmt->execute();
    $table_exists = $stmt->fetch();
    
    if ($table_exists) {
        echo "✅ stock_items table exists<br>";
        
        // Check table structure
        $stmt = $pdo->prepare("DESCRIBE stock_items");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Table columns: " . implode(', ', $columns) . "<br>";
        
        // Check if we have data
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM stock_items");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        echo "Total stock items: {$count}<br>";
        
        if ($count > 0) {
            // Show sample data
            $stmt = $pdo->prepare("SELECT * FROM stock_items LIMIT 5");
            $stmt->execute();
            $sample_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Sample Stock Data:</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Product ID</th><th>Quantity</th><th>Purchase Price</th><th>Sale Price</th><th>Status</th></tr>";
            
            foreach ($sample_data as $row) {
                echo "<tr>";
                echo "<td>{$row['id']}</td>";
                echo "<td>{$row['product_id']}</td>";
                echo "<td>{$row['quantity']}</td>";
                echo "<td>{$row['purchase_price']}</td>";
                echo "<td>{$row['sale_price']}</td>";
                echo "<td>{$row['status']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "❌ stock_items table does not exist<br>";
    }
    
    // Test 2: Test the average calculations
    echo "<h2>Test 2: Average Calculations</h2>";
    
    if ($table_exists && $count > 0) {
        // Test simple average
        $stmt = $pdo->prepare("
            SELECT 
                product_id,
                COUNT(*) as item_count,
                AVG(purchase_price) as simple_avg_purchase,
                AVG(sale_price) as simple_avg_sale
            FROM stock_items 
            WHERE status = 'available'
            GROUP BY product_id 
            LIMIT 3
        ");
        $stmt->execute();
        $simple_avgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Simple Averages:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Product ID</th><th>Items</th><th>Avg Purchase</th><th>Avg Sale</th></tr>";
        
        foreach ($simple_avgs as $row) {
            echo "<tr>";
            echo "<td>{$row['product_id']}</td>";
            echo "<td>{$row['item_count']}</td>";
            echo "<td>" . number_format($row['simple_avg_purchase'], 2) . "</td>";
            echo "<td>" . number_format($row['simple_avg_sale'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Test weighted average
        $stmt = $pdo->prepare("
            SELECT 
                product_id,
                SUM(quantity) as total_quantity,
                SUM(quantity * purchase_price) as total_purchase_value,
                SUM(quantity * sale_price) as total_sale_value,
                CASE 
                    WHEN SUM(quantity) > 0 
                    THEN SUM(quantity * purchase_price) / SUM(quantity)
                    ELSE 0 
                END as weighted_avg_purchase,
                CASE 
                    WHEN SUM(quantity) > 0 
                    THEN SUM(quantity * sale_price) / SUM(quantity)
                    ELSE 0 
                END as weighted_avg_sale
            FROM stock_items 
            WHERE status = 'available'
            GROUP BY product_id 
            LIMIT 3
        ");
        $stmt->execute();
        $weighted_avgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Weighted Averages:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Product ID</th><th>Total Qty</th><th>Total Purchase Value</th><th>Weighted Avg Purchase</th><th>Weighted Avg Sale</th></tr>";
        
        foreach ($weighted_avgs as $row) {
            echo "<tr>";
            echo "<td>{$row['product_id']}</td>";
            echo "<td>{$row['total_quantity']}</td>";
            echo "<td>" . number_format($row['total_purchase_value'], 2) . "</td>";
            echo "<td>" . number_format($row['weighted_avg_purchase'], 2) . "</td>";
            echo "<td>" . number_format($row['weighted_avg_sale'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 3: Test the full stock query
    echo "<h2>Test 3: Full Stock Query</h2>";
    
    if ($table_exists) {
        $stock_query = "
            SELECT 
                p.id,
                p.product_name,
                COALESCE(SUM(si.quantity), 0) as total_stock,
                COALESCE(SUM(CASE WHEN si.status = 'available' THEN si.quantity ELSE 0 END), 0) as available_stock,
                COALESCE(AVG(CASE WHEN si.status = 'available' THEN si.purchase_price END), 0) as avg_purchase_price,
                CASE 
                    WHEN SUM(CASE WHEN si.status = 'available' THEN si.quantity ELSE 0 END) > 0 
                    THEN COALESCE(SUM(CASE WHEN si.status = 'available' THEN si.quantity * si.purchase_price ELSE 0 END) / SUM(CASE WHEN si.status = 'available' THEN si.quantity ELSE 0 END), 0)
                    ELSE 0 
                END as weighted_avg_purchase_price
            FROM products p
            LEFT JOIN stock_items si ON p.id = si.product_id
            GROUP BY p.id, p.product_name
            LIMIT 5
        ";
        
        try {
            $stmt = $pdo->prepare($stock_query);
            $stmt->execute();
            $stock_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Stock Summary:</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Product</th><th>Total Stock</th><th>Available</th><th>Simple Avg Purchase</th><th>Weighted Avg Purchase</th></tr>";
            
            foreach ($stock_data as $row) {
                echo "<tr>";
                echo "<td>{$row['product_name']}</td>";
                echo "<td>{$row['total_stock']}</td>";
                echo "<td>{$row['available_stock']}</td>";
                echo "<td>" . number_format($row['avg_purchase_price'], 2) . "</td>";
                echo "<td>" . number_format($row['weighted_avg_purchase_price'], 2) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
        } catch (Exception $e) {
            echo "❌ Error executing stock query: " . $e->getMessage() . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ General error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><strong>Test completed!</strong></p>";
echo "<p>If you see any errors, please run the fix_database.php script first to ensure proper database structure.</p>";
?>
