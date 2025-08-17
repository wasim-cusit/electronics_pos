<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $opening_balance = (float)($_POST['opening_balance'] ?? 0.00);
    
    // Validate required fields
    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Customer name is required']);
        exit;
    }
    
    if (empty($mobile)) {
        echo json_encode(['success' => false, 'error' => 'Mobile number is required']);
        exit;
    }
    
    if (empty($address)) {
        echo json_encode(['success' => false, 'error' => 'Address is required']);
        exit;
    }
    
    // Check if customer already exists (by name or mobile)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customer WHERE name = ? OR mobile = ?");
    $stmt->execute([$name, $mobile]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'error' => 'Customer with this name or mobile number already exists']);
        exit;
    }
    
    // Insert new customer
    $stmt = $pdo->prepare("INSERT INTO customer (name, mobile, address, email, opening_balance, status, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
    $stmt->execute([$name, $mobile, $address, $email, $opening_balance]);
    
    $customer_id = $pdo->lastInsertId();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'customer' => [
            'id' => $customer_id,
            'name' => $name,
            'mobile' => $mobile,
            'address' => $address,
            'email' => $email,
            'opening_balance' => $opening_balance
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error adding customer: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to add customer. Please try again.']);
}
?>
