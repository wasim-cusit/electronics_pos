<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $product_id = intval($_POST['product_id']);
    $quantity = floatval($_POST['quantity']);
    $purchase_price = floatval($_POST['purchase_price']);
    $sale_price = floatval($_POST['sale_price']);
    $stock_date = $_POST['stock_date'];
    
    // Validate inputs
    if ($product_id <= 0 || $quantity <= 0 || $purchase_price < 0 || $sale_price < 0) {
        throw new Exception('Invalid input parameters');
    }
    
    // Validate sale price is not less than purchase price
    if ($sale_price < $purchase_price) {
        throw new Exception('Sale price cannot be less than purchase price');
    }
    
    // Get product information
    $stmt = $pdo->prepare("SELECT product_code, product_name FROM products WHERE id = ? AND status = 1");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('Product not found or inactive');
    }
    
    // Generate unique product code for this stock item
    $stock_code = $product['product_code'] . '-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    // Insert stock item
    $stmt = $pdo->prepare("
        INSERT INTO stock_items (product_id, product_code, quantity, purchase_price, sale_price, stock_date, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'available')
    ");
    
    $stmt->execute([$product_id, $stock_code, $quantity, $purchase_price, $sale_price, $stock_date]);
    
    $stock_id = $pdo->lastInsertId();
    
    // Note: stock_movements table doesn't exist in the current database
    // Stock addition logging is not available
    
    echo json_encode([
        'success' => true, 
        'message' => "Stock added successfully: {$quantity} units of {$product['product_name']}",
        'stock_id' => $stock_id,
        'stock_code' => $stock_code
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
