<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $product_id = intval($_GET['product_id']);
    
    if ($product_id <= 0) {
        throw new Exception('Invalid product ID');
    }
    
    // Get product information
    $stmt = $pdo->prepare("
        SELECT 
            p.product_name,
            p.product_code,
            c.category
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?
    ");
    $stmt->execute([$product_id]);
    $product_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product_info) {
        throw new Exception('Product not found');
    }
    
         // Check if created_at column exists in stock_items table
     $stmt = $pdo->prepare("SHOW COLUMNS FROM stock_items LIKE 'created_at'");
     $stmt->execute();
     $has_created_at = $stmt->fetch();
     
     // Use created_at if it exists, otherwise use stock_date
     $created_at_field = $has_created_at ? 'created_at' : 'stock_date';
     
     // Debug: Log which field we're using
     error_log("Stock history: Using {$created_at_field} field for created_at");
     
     // Debug: Check what's actually in stock_items for this product
     $debug_stmt = $pdo->prepare("SELECT id, quantity, purchase_price, sale_price, status, stock_date FROM stock_items WHERE product_id = ?");
     $debug_stmt->execute([$product_id]);
     $debug_data = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
     error_log("Raw stock_items data for product {$product_id}: " . json_encode($debug_data));
     
     // Check for NULL or 0 quantities
     $null_check_stmt = $pdo->prepare("SELECT COUNT(*) as total_records, 
         SUM(CASE WHEN quantity IS NULL THEN 1 ELSE 0 END) as null_quantity,
         SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) as zero_quantity,
         SUM(CASE WHEN quantity > 0 THEN 1 ELSE 0 END) as positive_quantity,
         SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END) as total_quantity
         FROM stock_items WHERE product_id = ?");
     $null_check_stmt->execute([$product_id]);
     $null_check_data = $null_check_stmt->fetch(PDO::FETCH_ASSOC);
     error_log("Quantity analysis for product {$product_id}: " . json_encode($null_check_data));
     
     // Also check if there are any records with actual quantities
     $actual_quantity_stmt = $pdo->prepare("SELECT id, quantity, purchase_price, sale_price, status FROM stock_items WHERE product_id = ? AND quantity > 0");
     $actual_quantity_stmt->execute([$product_id]);
     $actual_quantities = $actual_quantity_stmt->fetchAll(PDO::FETCH_ASSOC);
     error_log("Records with actual quantities for product {$product_id}: " . json_encode($actual_quantities));
     
     // Get stock history from stock_items table - handle different scenarios
     $stmt = $pdo->prepare("
         SELECT 
             'added' as movement_type,
             CAST(COALESCE(quantity, 0) AS DECIMAL(15,3)) as quantity,
             CAST(COALESCE(purchase_price, 0) AS DECIMAL(15,2)) as price,
             stock_date as movement_date,
             status,
             'Stock added via stock management' as notes,
             {$created_at_field} as created_at
         FROM stock_items 
         WHERE product_id = ? AND status != 'deleted'
         
         UNION ALL
         
         SELECT 
             'sold' as movement_type,
             CAST(COALESCE(quantity, 0) AS DECIMAL(15,3)) as quantity,
             CAST(COALESCE(sale_price, 0) AS DECIMAL(15,2)) as price,
             stock_date as movement_date,
             'sold' as status,
             'Item sold' as notes,
             {$created_at_field} as created_at
         FROM stock_items 
         WHERE product_id = ? AND status = 'sold'
         
         ORDER BY movement_date DESC, created_at DESC
     ");
     $stmt->execute([$product_id, $product_id]);
     $stock_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
     
     // Debug: Log the stock history data
     error_log("Stock history for product {$product_id}: " . json_encode($stock_history));
    
         // If stock_movements table exists, get additional movement data
     try {
         // Check if stock_movements table exists
         $stmt = $pdo->prepare("SHOW TABLES LIKE 'stock_movements'");
         $stmt->execute();
         $movements_table_exists = $stmt->fetch();
         
         if ($movements_table_exists) {
             $stmt = $pdo->prepare("
                 SELECT 
                     movement_type,
                     quantity,
                     price,
                     movement_date,
                     'available' as status,
                     notes,
                     created_at
                 FROM stock_movements 
                 WHERE product_id = ?
                 ORDER BY movement_date DESC, created_at DESC
             ");
             $stmt->execute([$product_id]);
             $movement_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
             
             // Merge and sort all history
             $all_history = array_merge($stock_history, $movement_history);
             usort($all_history, function($a, $b) {
                 $dateA = strtotime($a['movement_date']);
                 $dateB = strtotime($b['movement_date']);
                 if ($dateA === $dateB) {
                     return strtotime($b['created_at']) - strtotime($a['created_at']);
                 }
                 return $dateB - $dateA;
             });
             
             $history = $all_history;
         } else {
             $history = $stock_history;
         }
     } catch (Exception $e) {
         // If stock_movements table doesn't exist, use only stock_items data
         $history = $stock_history;
     }
    
    // Calculate summary statistics
    $total_added = 0;
    $total_sold = 0;
    $total_reserved = 0;
    $total_returned = 0;
    
    foreach ($history as $item) {
        switch ($item['movement_type']) {
            case 'added':
                $total_added += $item['quantity'];
                break;
            case 'sold':
                $total_sold += $item['quantity'];
                break;
            case 'reserved':
                $total_reserved += $item['quantity'];
                break;
            case 'returned':
                $total_returned += $item['quantity'];
                break;
        }
    }
    
    $current_stock = $total_added - $total_sold + $total_returned - $total_reserved;
    
    // Add summary to product info
    $product_info['summary'] = [
        'total_added' => $total_added,
        'total_sold' => $total_sold,
        'total_reserved' => $total_reserved,
        'total_returned' => $total_returned,
        'current_stock' => $current_stock
    ];
    
    echo json_encode([
        'success' => true,
        'product_info' => $product_info,
        'history' => $history,
        'summary' => [
            'total_movements' => count($history),
            'total_added' => $total_added,
            'total_sold' => $total_sold,
            'current_stock' => $current_stock
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
