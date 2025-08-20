<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request. Please try again.']);
    exit;
}

// Get and sanitize form data
$supplier_name = sanitize_input(trim($_POST['supplier_name'] ?? ''));
$supplier_contact = sanitize_input(trim($_POST['supplier_contact'] ?? ''));
$supplier_email = sanitize_input(trim($_POST['supplier_email'] ?? ''));
$supplier_address = sanitize_input(trim($_POST['supplier_address'] ?? ''));
$opening_balance = floatval($_POST['opening_balance'] ?? 0);

// Validate required fields
if (empty($supplier_name)) {
    echo json_encode(['success' => false, 'message' => 'Supplier name is required']);
    exit;
}

// Validate email if provided
if (!empty($supplier_email) && !validate_email($supplier_email)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Validate phone if provided
if (!empty($supplier_contact) && !validate_phone($supplier_contact)) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number format']);
    exit;
}

// Validate name length
if (!validate_length($supplier_name, 2, 100)) {
    echo json_encode(['success' => false, 'message' => 'Supplier name must be between 2 and 100 characters']);
    exit;
}

try {
    // Check if supplier already exists
    $stmt = $pdo->prepare("SELECT id FROM supplier WHERE supplier_name = ?");
    $stmt->execute([$supplier_name]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Supplier with this name already exists']);
        exit;
    }
    
    // Insert new supplier
    $stmt = $pdo->prepare("
        INSERT INTO supplier (supplier_name, supplier_contact, supplier_email, supplier_address, opening_balance, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $supplier_name,
        $supplier_contact,
        $supplier_email,
        $supplier_address,
        $opening_balance
    ]);
    
    $supplier_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Supplier added successfully',
        'supplier_id' => $supplier_id,
        'supplier_name' => $supplier_name
    ]);
    
} catch (Exception $e) {
    error_log("Error adding supplier: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred while adding supplier'
    ]);
}
?>
