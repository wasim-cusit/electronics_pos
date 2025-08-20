<?php
// Include security configuration
require_once __DIR__ . '/security.php';

// Include error handling
require_once __DIR__ . '/error_handler.php';

// Auto-detect base URL
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script_name = $_SERVER['SCRIPT_NAME'];
    $path_info = pathinfo($script_name);
    $base_path = $path_info['dirname'];
    
    // If we're in the root directory, return just the protocol and host
    if ($base_path === '/') {
        return $protocol . '://' . $host . '/';
    }
    
    // Otherwise, return the full path
    return $protocol . '://' . $host . $base_path . '/';
}

// Base URL configuration - you can override this manually if needed
$base_url = getBaseUrl();

// Alternative: Manual override (uncomment and modify if needed)
// $base_url = '/electronic/';
// $base_url = 'http://localhost/electronic/';
// $base_url = 'https://yoursite.com/electronic/';

// Database configuration
$host = 'localhost';
$db   = 'electronics_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    exit('Database connection failed: ' . $e->getMessage());
}

// CSRF Protection
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Input sanitization functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function sanitize_array($array) {
    $sanitized = [];
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $sanitized[$key] = sanitize_array($value);
        } else {
            $sanitized[$key] = sanitize_input($value);
        }
    }
    return $sanitized;
}

// Validation functions
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_phone($phone) {
    // Remove all non-digit characters
    $clean_phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if it's a valid Pakistani mobile number (11 digits starting with 03)
    if (strlen($clean_phone) === 11 && substr($clean_phone, 0, 2) === '03') {
        return true;
    }
    
    // Check if it's a valid Pakistani mobile number (12 digits starting with 92)
    if (strlen($clean_phone) === 12 && substr($clean_phone, 0, 2) === '92') {
        return true;
    }
    
    // Check if it's a valid Pakistani mobile number (13 digits starting with +92)
    if (strlen($clean_phone) === 13 && substr($clean_phone, 0, 3) === '923') {
        return true;
    }
    
    return false;
}

function validate_cnic($cnic) {
    // Remove all non-digit characters
    $clean_cnic = preg_replace('/[^0-9]/', '', $cnic);
    
    // Check if it's exactly 13 digits
    if (strlen($clean_cnic) === 13) {
        return true;
    }
    
    // Check if it's in format 00000-0000000-0
    if (preg_match('/^\d{5}-\d{7}-\d$/', $cnic)) {
        return true;
    }
    
    return false;
}

function validate_required($value) {
    return !empty(trim($value));
}

function validate_length($value, $min_length = 1, $max_length = 255) {
    $length = strlen(trim($value));
    return $length >= $min_length && $length <= $max_length;
}

function validate_date($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Helper function to get full URL
function getFullUrl($path = '') {
    global $base_url;
    return rtrim($base_url, '/') . '/' . ltrim($path, '/');
}

// Helper function to get asset URL
function getAssetUrl($path = '') {
    global $base_url;
    return rtrim($base_url, '/') . '/assets/' . ltrim($path, '/');
}

// Error logging function
function log_error($message, $context = []) {
    $log_entry = date('Y-m-d H:i:s') . ' - ' . $message;
    if (!empty($context)) {
        $log_entry .= ' - Context: ' . json_encode($context);
    }
    error_log($log_entry);
}