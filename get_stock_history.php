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
    
    // Get stock history data from stock_items table
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            product_id, 
            quantity, 
            purchase_price,
            sale_price,
            stock_date,
            status,
            product_code
        FROM stock_items 
        WHERE product_id = ? 
        ORDER BY stock_date DESC
    ");
    
    $stmt->execute([$product_id]);
    $stock_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate summary statistics
    $total_added = 0;
    $total_sold = 0;
    $total_reserved = 0;
    $current_stock = 0;
    
    foreach ($stock_history as $item) {
        if ($item['status'] === 'available') {
            $total_added += $item['quantity'];
            $current_stock += $item['quantity'];
        } elseif ($item['status'] === 'sold') {
            $total_sold += $item['quantity'];
        } elseif ($item['status'] === 'reserved') {
            $total_reserved += $item['quantity'];
        }
    }
    
    // Add summary to product info
    $product_info['summary'] = [
        'total_added' => $total_added,
        'total_sold' => $total_sold,
        'total_reserved' => $total_reserved,
        'current_stock' => $current_stock
    ];
    
    // Process the data for display
    $processed_history = [];
    foreach ($stock_history as $item) {
        $processed_history[] = [
            'id' => $item['id'],
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'purchase_price' => $item['purchase_price'],
            'sale_price' => $item['sale_price'],
            'stock_date' => $item['stock_date'],
            'status' => $item['status'],
            'product_code' => $item['product_code']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'history' => $processed_history,
        'product_info' => $product_info
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
