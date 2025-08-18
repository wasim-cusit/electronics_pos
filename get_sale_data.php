<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Sale ID not provided']);
    exit;
}

$sale_id = intval($_GET['id']);

try {
    // Get sale details
    $stmt = $pdo->prepare("SELECT s.*, COALESCE(c.name, s.walk_in_cust_name) AS customer_name FROM sale s LEFT JOIN customer c ON s.customer_id = c.id WHERE s.id = ?");
    $stmt->execute([$sale_id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sale) {
        echo json_encode(['success' => false, 'error' => 'Sale not found']);
        exit;
    }
    
    // Get sale items
    $stmt = $pdo->prepare("SELECT si.*, p.product_name, c.category FROM sale_items si 
                           LEFT JOIN products p ON si.product_id = p.id 
                           LEFT JOIN categories c ON p.category_id = c.id 
                           WHERE si.sale_id = ?");
    $stmt->execute([$sale_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data
    $sale_data = [
        'id' => $sale['id'],
        'sale_no' => $sale['sale_no'],
        'customer_name' => $sale['customer_name'],
        'sale_date' => date('d M Y', strtotime($sale['sale_date'])),
        'sale_time' => date('h:i A', strtotime($sale['sale_date'])),
        'subtotal' => $sale['subtotal'],
        'discount' => $sale['discount'],
        'after_discount' => $sale['after_discount'],
        'total_amount' => $sale['total_amount'],
        'paid_amount' => $sale['paid_amount'],
        'due_amount' => $sale['due_amount'],
        'items' => $items
    ];
    
    echo json_encode(['success' => true, 'sale' => $sale_data]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
