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
    $contact = trim($_POST['contact'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    // Validate required fields
    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Customer name is required']);
        exit;
    }
    
    // Check if customer already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customer WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'error' => 'Customer with this name already exists']);
        exit;
    }
    
    // Insert new customer
    $stmt = $pdo->prepare("INSERT INTO customer (name, mobile, address, email, status, created_at) VALUES (?, ?, ?, ?, 1, NOW())");
    $stmt->execute([$name, $contact, $address, $email]);
    
    $customer_id = $pdo->lastInsertId();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'customer' => [
            'id' => $customer_id,
            'name' => $name,
            'contact' => $contact,
            'address' => $address,
            'email' => $email
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error adding customer: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to add customer. Please try again.']);
}
?>
