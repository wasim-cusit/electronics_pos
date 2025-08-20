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

// CSRF Protection
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request. Please try again.']);
    exit;
}

try {
    // Get and sanitize form data
    $name = sanitize_input(trim($_POST['name'] ?? ''));
    $mobile = sanitize_input(trim($_POST['mobile'] ?? ''));
    $cnic = sanitize_input(trim($_POST['cnic'] ?? ''));
    $address = sanitize_input(trim($_POST['address'] ?? ''));
    $email = sanitize_input(trim($_POST['email'] ?? ''));
    $opening_balance = floatval($_POST['opening_balance'] ?? 0.00);
    
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
    
    // Validate email if provided
    if (!empty($email) && !validate_email($email)) {
        echo json_encode(['success' => false, 'error' => 'Invalid email format']);
        exit;
    }
    
    // Validate mobile number
    if (!validate_phone($mobile)) {
        echo json_encode(['success' => false, 'error' => 'Invalid mobile number format']);
        exit;
    }
    
    // Validate CNIC if provided
    if (!empty($cnic) && !validate_cnic($cnic)) {
        echo json_encode(['success' => false, 'error' => 'Invalid CNIC format']);
        exit;
    }
    
    // Validate name length
    if (!validate_length($name, 2, 100)) {
        echo json_encode(['success' => false, 'error' => 'Customer name must be between 2 and 100 characters']);
        exit;
    }
    
    // Check if customer already exists (by name, mobile, or CNIC)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customer WHERE name = ? OR mobile = ? OR (cnic IS NOT NULL AND cnic = ?)");
    $stmt->execute([$name, $mobile, $cnic]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'error' => 'Customer with this name, mobile number, or CNIC already exists']);
        exit;
    }
    
    // Insert new customer
    $stmt = $pdo->prepare("INSERT INTO customer (name, mobile, cnic, address, email, opening_balance, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
    $stmt->execute([$name, $mobile, $cnic, $address, $email, $opening_balance]);
    
    $customer_id = $pdo->lastInsertId();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'customer' => [
            'id' => $customer_id,
            'name' => $name,
            'mobile' => $mobile,
            'cnic' => $cnic,
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
