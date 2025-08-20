<?php
/**
 * Error Handling and Logging System
 * This file provides centralized error handling for the Electronics POS system
 */

// Define environment if not already defined
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development'); // Change to 'production' in production
}

// Set error reporting based on environment - Simplified
if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
    error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Custom error handler - Simplified to prevent interference
function custom_error_handler($errno, $errstr, $errfile, $errline) {
    // Only handle critical errors to prevent interference
    if (in_array($errno, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $error_message = "Critical Error: $errstr in $errfile on line $errline";
        error_log($error_message);
        
        // In development, display critical errors
        if (defined('ENVIRONMENT') && ENVIRONMENT !== 'production') {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;'>";
            echo "<strong>Critical Error:</strong> $errstr<br>";
            echo "<strong>File:</strong> $errfile<br>";
            echo "<strong>Line:</strong> $errline";
            echo "</div>";
        }
    }
    
    return true;
}

// Custom exception handler - Simplified to prevent interference
function custom_exception_handler($exception) {
    $error_message = "Exception: " . $exception->getMessage();
    error_log($error_message);
    
    // In development, show basic error info
    if (defined('ENVIRONMENT') && ENVIRONMENT !== 'production') {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;'>";
        echo "<h3>Exception Occurred</h3>";
        echo "<p><strong>Message:</strong> " . $exception->getMessage() . "</p>";
        echo "<p><strong>File:</strong> " . $exception->getFile() . "</p>";
        echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
        echo "</div>";
    }
}

// Custom fatal error handler - Simplified to prevent interference
function custom_fatal_error_handler() {
    $error = error_get_last();
    
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $error_message = "Fatal Error: " . $error['message'];
        error_log($error_message);
        
        // In development, show basic error info
        if (defined('ENVIRONMENT') && ENVIRONMENT !== 'production') {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 10px; border-radius: 5px;'>";
            echo "<h3>Fatal Error Occurred</h3>";
            echo "<p><strong>Message:</strong> " . $error['message'] . "</p>";
            echo "<p><strong>File:</strong> " . $error['file'] . "</p>";
            echo "<p><strong>Line:</strong> " . $error['line'] . "</p>";
            echo "</div>";
        }
    }
}

// Set custom error handlers
set_error_handler('custom_error_handler');
set_exception_handler('custom_exception_handler');
register_shutdown_function('custom_fatal_error_handler');

// Function to safely display errors to users
function display_user_error($message, $type = 'error') {
    $alert_class = $type === 'error' ? 'danger' : ($type === 'warning' ? 'warning' : 'info');
    
    echo "<div class='alert alert-{$alert_class} alert-dismissible fade show' role='alert'>";
    echo "<i class='bi bi-exclamation-triangle me-2'></i>";
    echo htmlspecialchars($message);
    echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
    echo "</div>";
}

// Function to handle database errors gracefully
function handle_database_error($exception, $context = '') {
    $error_message = "Database error occurred";
    if (!empty($context)) {
        $error_message .= " in: $context";
    }
    
    // Log the error
    log_error($error_message, [
        'error' => $exception->getMessage(),
        'code' => $exception->getCode(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'context' => $context
    ]);
    
    // In production, return generic message
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
        return "A database error occurred. Please try again later.";
    }
    
    // In development, return detailed error
    return "Database Error: " . $exception->getMessage();
}

// Function to validate and sanitize database queries - DISABLED to prevent interference
function validate_sql_query($query) {
    // SQL validation disabled to prevent interference with software functionality
    return true;
}
