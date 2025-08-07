<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if ($name === '') {
        echo json_encode(['success' => false, 'error' => 'Name is required.']);
        exit;
    }
    $stmt = $pdo->prepare("INSERT INTO customers (name, contact, address, email) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $contact, $address, $email]);
    $id = $pdo->lastInsertId();
    echo json_encode(['success' => true, 'customer' => [
        'id' => $id,
        'name' => $name
    ]]);
    exit;
}
echo json_encode(['success' => false, 'error' => 'Invalid request.']);
