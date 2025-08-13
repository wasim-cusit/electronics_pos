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
    $quantity = intval($_POST['quantity']);
    $purchase_price = floatval($_POST['purchase_price']);
    $sale_price = floatval($_POST['sale_price']);
    $stock_date = $_POST['stock_date'];
    
    // Validate inputs
    if ($product_id <= 0 || $quantity <= 0 || $purchase_price < 0 || $sale_price < 0) {
        throw new Exception('Invalid input parameters');
    }
    
    // Get product information
    $stmt = $pdo->prepare("SELECT product_code FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('Product not found');
    }
    
    // Insert stock item
    $stmt = $pdo->prepare("
        INSERT INTO stock_items (product_id, product_code, quantity, purchase_price, sale_price, stock_date, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'available')
    ");
    
    $stmt->execute([$product_id, $product['product_code'], $quantity, $purchase_price, $sale_price, $stock_date]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Stock added successfully',
        'stock_id' => $pdo->lastInsertId()
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
