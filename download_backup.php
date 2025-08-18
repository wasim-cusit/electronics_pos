<?php
require_once 'includes/auth.php';
require_login();
require_once 'includes/config.php';
require_once 'includes/settings.php';

// Get the backup file name
$filename = $_GET['file'] ?? '';
if (empty($filename)) {
    die('No backup file specified');
}

// Security check - only allow backup files
if (!preg_match('/^tailor_backup_.*\.(sql|zip|json)$/', $filename)) {
    die('Invalid backup file');
}

// Get backup directory
$backup_dir = get_setting('backup_location', 'backups/');
$backup_dir = rtrim($backup_dir, '/') . '/';
$file_path = $backup_dir . $filename;

// Check if file exists
if (!file_exists($file_path)) {
    die('Backup file not found');
}

// Get file info
$file_size = filesize($file_path);
$file_type = mime_content_type($file_path);

// Set headers for download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $file_size);
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Output file content
readfile($file_path);
exit;
?>
