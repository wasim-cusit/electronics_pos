<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';
require_once 'includes/settings.php';

// Function to return JSON error response
function returnJsonError($statusCode, $message) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnJsonError(405, 'Method not allowed');
}

// Check if user has admin privileges
if (!function_exists('has_role') || !has_role('Admin')) {
    returnJsonError(403, 'Access denied');
}

// Get the backup file name
$filename = $_POST['filename'] ?? '';
if (empty($filename)) {
    returnJsonError(400, 'No backup file specified');
}

// Security check - only allow backup files (both tailor_backup_ and test_backup_ patterns)
if (!preg_match('/^(tailor_backup_|test_backup_).*\.(sql|zip|json)$/', $filename)) {
    returnJsonError(400, 'Invalid backup file');
}

// Get backup directory
$backup_dir = get_setting('backup_location', 'backups/');
$backup_dir = rtrim($backup_dir, '/') . '/';
$file_path = $backup_dir . $filename;

// Check if file exists
if (!file_exists($file_path)) {
    returnJsonError(404, 'Backup file not found');
}

// Check if file is within backup directory (prevent directory traversal)
$real_file_path = realpath($file_path);
$real_backup_dir = realpath($backup_dir);
if (strpos($real_file_path, $real_backup_dir) !== 0) {
    returnJsonError(400, 'Invalid file path');
}

try {
    // Delete the file
    if (unlink($file_path)) {
        // Return success response
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Backup file deleted successfully']);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['success' => false, 'message' => 'Failed to delete backup file']);
    }
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Error deleting backup file: ' . $e->getMessage()]);
}
?>
