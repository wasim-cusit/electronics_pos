<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Purchase ID not provided']);
    exit;
}

$purchase_id = intval($_GET['id']);

try {
    // Get purchase details
    $stmt = $pdo->prepare("SELECT p.*, s.supplier_name FROM purchase p LEFT JOIN supplier s ON p.supplier_id = s.id WHERE p.id = ?");
    $stmt->execute([$purchase_id]);
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$purchase) {
        echo json_encode(['success' => false, 'error' => 'Purchase not found']);
        exit;
    }
    
    // Get purchase items
    $stmt = $pdo->prepare("SELECT pi.*, p.product_name, c.category FROM purchase_items pi 
                           LEFT JOIN products p ON pi.product_id = p.id 
                           LEFT JOIN categories c ON p.category_id = c.id 
                           WHERE pi.purchase_id = ?");
    $stmt->execute([$purchase_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data
    $purchase_data = [
        'id' => $purchase['id'],
        'purchase_no' => $purchase['purchase_no'],
        'supplier_name' => $purchase['supplier_name'],
        'purchase_date' => date('d M Y', strtotime($purchase['purchase_date'])),
        'purchase_time' => date('h:i A', strtotime($purchase['purchase_date'])),
        'total_amount' => $purchase['total_amount'],
        'paid_amount' => $purchase['paid_amount'],
        'due_amount' => $purchase['due_amount'],
        'items' => $items
    ];
    
    echo json_encode(['success' => true, 'purchase' => $purchase_data]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
