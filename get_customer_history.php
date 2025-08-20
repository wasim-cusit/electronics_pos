<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

header('Content-Type: application/json');

if (!isset($_GET['customer_id']) || !is_numeric($_GET['customer_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid customer ID']);
    exit;
}

$customer_id = (int)$_GET['customer_id'];

try {
    // Get customer's recent sales
    $sales_query = "
        SELECT sale_no, total_amount, due_amount, sale_date, status
        FROM sale 
        WHERE customer_id = ? AND status != 'cancelled'
        ORDER BY sale_date DESC 
        LIMIT 5
    ";
    $sales_stmt = $pdo->prepare($sales_query);
    $sales_stmt->execute([$customer_id]);
    $recent_sales = $sales_stmt->fetchAll();
    
    // Get customer's recent payments
    $payments_query = "
        SELECT paid, payment_date, details, receipt
        FROM customer_payment 
        WHERE customer_id = ?
        ORDER BY payment_date DESC 
        LIMIT 5
    ";
    $payments_stmt = $pdo->prepare($payments_query);
    $payments_stmt->execute([$customer_id]);
    $recent_payments = $payments_stmt->fetchAll();
    
    // Calculate summary
    $total_sales = array_sum(array_column($recent_sales, 'due_amount'));
    $total_payments = array_sum(array_column($recent_payments, 'paid'));
    
    $response = [
        'success' => true,
        'data' => [
            'recent_sales' => $recent_sales,
            'recent_payments' => $recent_payments,
            'summary' => [
                'total_sales' => $total_sales,
                'total_payments' => $total_payments,
                'sales_count' => count($recent_sales),
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
