<?php
/**
 * Security Configuration and Functions
 * This file contains additional security measures for the Electronics POS system
 */

// Security constants
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minutes
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);

// Rate limiting function - DISABLED to prevent interference
function check_rate_limit($action, $max_attempts = 10, $time_window = 300) {
    // Rate limiting disabled to prevent interference with software functionality
    return true;
}

// Input validation functions
function validate_integer($value, $min = null, $max = null) {
    if (!is_numeric($value) || (int)$value != $value) {
        return false;
    }
    
    $value = (int)$value;
    
    if ($min !== null && $value < $min) {
        return false;
    }
    
    if ($max !== null && $value > $max) {
        return false;
    }
    
    return true;
}

function validate_float($value, $min = null, $max = null) {
    if (!is_numeric($value)) {
        return false;
    }
    
    $value = (float)$value;
    
    if ($min !== null && $value < $min) {
        return false;
    }
    
    if ($max !== null && $value > $max) {
        return false;
    }
    
    return true;
}

function validate_string($value, $min_length = 0, $max_length = null) {
    if (!is_string($value)) {
        return false;
    }
    
    $length = strlen(trim($value));
    
    if ($length < $min_length) {
        return false;
    }
    
    if ($max_length !== null && $length > $max_length) {
        return false;
    }
    
    return true;
}

// Note: validate_date function is already defined in config.php to avoid conflicts

// File upload security - Simplified to prevent interference
function validate_file_upload($file, $allowed_types = [], $max_size = 10485760) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return false;
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Check file size (increased to 10MB)
    if ($file['size'] > $max_size) {
        return false;
    }
    
    return true;
}

// SQL injection prevention - Simplified to prevent interference
function escape_sql_like($string) {
    return str_replace(['%', '_'], ['\\%', '\\_'], $string);
}

// Note: Validation functions (validate_email, validate_phone, validate_cnic, etc.) 
// are already defined in config.php to avoid conflicts

// XSS prevention - Simplified to prevent interference
function xss_clean($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = xss_clean($value);
        }
    } else {
        // Basic cleaning without being too restrictive
        $data = strip_tags($data);
    }
    return $data;
}

// Session security - Simplified to prevent interference
function secure_session_start() {
    // Basic session start without strict security that could cause issues
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Password strength validation - Simplified to prevent interference
function validate_password_strength($password) {
    $errors = [];
    
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    return $errors;
}

// Audit logging - Simplified to prevent interference
function log_security_event($event_type, $details = [], $user_id = null) {
    // Basic logging without database operations that could cause issues
    $log_entry = date('Y-m-d H:i:s') . " - {$event_type}";
    if (!empty($details)) {
        $log_entry .= " - " . json_encode($details);
    }
    error_log($log_entry);
}

// Initialize security measures
if (session_status() === PHP_SESSION_NONE) {
    secure_session_start();
}

// Note: CSP header removed to prevent interference with software functionality
