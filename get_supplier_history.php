<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

header('Content-Type: application/json');

if (!isset($_GET['supplier_id']) || !is_numeric($_GET['supplier_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid supplier ID']);
    exit;
}

$supplier_id = (int)$_GET['supplier_id'];

try {
    // Get supplier's recent purchases
    $purchases_query = "
        SELECT purchase_no, total_amount, due_amount, purchase_date, status
        FROM purchase 
        WHERE supplier_id = ? AND status != 'cancelled'
        ORDER BY purchase_date DESC 
        LIMIT 5
    ";
    $purchases_stmt = $pdo->prepare($purchases_query);
    $purchases_stmt->execute([$supplier_id]);
    $recent_purchases = $purchases_stmt->fetchAll();
    
    // Get supplier's recent payments
    $payments_query = "
        SELECT payment_amount as paid, payment_date, notes as details, reference_no as receipt
        FROM supplier_payments 
        WHERE supplier_id = ?
        ORDER BY payment_date DESC 
        LIMIT 5
    ";
    $payments_stmt = $pdo->prepare($payments_query);
    $payments_stmt->execute([$supplier_id]);
    $recent_payments = $payments_stmt->fetchAll();
    
    // Calculate summary
    $total_purchases = array_sum(array_column($recent_purchases, 'total_amount'));
    $total_payments = array_sum(array_column($recent_payments, 'paid'));
    
    $response = [
        'success' => true,
        'data' => [
            'recent_purchases' => $recent_purchases,
            'recent_payments' => $recent_payments,
            'summary' => [
                'total_purchases' => abs($total_purchases), // Use absolute value for display
                'total_payments' => abs($total_payments),   // Use absolute value for display
                'purchases_count' => count($recent_purchases),
                'payments_count' => count($recent_payments)
            ]
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
