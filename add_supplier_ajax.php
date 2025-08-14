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

// Get form data
$supplier_name = trim($_POST['supplier_name'] ?? '');
$supplier_contact = trim($_POST['supplier_contact'] ?? '');
$supplier_email = trim($_POST['supplier_email'] ?? '');
$supplier_address = trim($_POST['supplier_address'] ?? '');
$opening_balance = floatval($_POST['opening_balance'] ?? 0);

// Validate required fields
if (empty($supplier_name)) {
    echo json_encode(['success' => false, 'message' => 'Supplier name is required']);
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
