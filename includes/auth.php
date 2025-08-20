<?php
session_start();

// Regenerate session ID periodically to prevent session fixation
if (!isset($_SESSION['last_regeneration']) || time() - $_SESSION['last_regeneration'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

require_once __DIR__ . '/config.php';

function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['last_activity']) && 
           (time() - $_SESSION['last_activity']) < 3600; // 1 hour timeout
}

function require_login() {
    global $base_url;
    if (!is_logged_in()) {
        header('Location: ' . $base_url . 'login.php');
        exit;
    }
    // Update last activity
    $_SESSION['last_activity'] = time();
}

function login($username, $password) {
    global $pdo;
    
    // Sanitize input
    $username = sanitize_input($username);
    
    if (empty($username) || empty($password)) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare('SELECT * FROM system_users WHERE username = ? AND status = 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['last_activity'] = time();
            $_SESSION['last_regeneration'] = time();
            
            // Log successful login
            log_error("User logged in successfully", ['user_id' => $user['id'], 'username' => $username]);
            
            return true;
        }
        
        // Log failed login attempt
        log_error("Failed login attempt", ['username' => $username, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
        
        return false;
    } catch (Exception $e) {
        log_error("Login error", ['error' => $e->getMessage(), 'username' => $username]);
        return false;
    }
}

function logout() {
    // Log logout
    if (isset($_SESSION['user_id'])) {
        log_error("User logged out", ['user_id' => $_SESSION['user_id'], 'username' => $_SESSION['username'] ?? 'unknown']);
    }
    
    session_unset();
    session_destroy();
}

function current_user() {
    return $_SESSION['user_name'] ?? $_SESSION['username'] ?? null;
}

function has_role($role_name) {
    if (!is_logged_in()) {
        return false;
    }
    
    try {
        global $pdo;
        $stmt = $pdo->prepare("SELECT r.role_name FROM roles r 
                               JOIN system_users u ON r.id = u.role_id 
                               WHERE u.id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $role = $stmt->fetchColumn();
        
        return $role === $role_name;
    } catch (Exception $e) {
        log_error("Role check error", ['error' => $e->getMessage(), 'user_id' => $_SESSION['user_id'] ?? 'unknown']);
        return false;
    }
}

function redirect_if_logged_in() {
    global $base_url;
    if (is_logged_in()) {
        header('Location: ' . $base_url . 'dashboard.php');
        exit;
    }
}

function register_user($username, $password, $role_id = 2) {
    global $pdo;
    
    // Validate input
    $username = sanitize_input($username);
    if (empty($username) || empty($password)) {
        return false;
    }
    
    // Check if username already exists
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM system_users WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetchColumn() > 0) {
        return false;
    }
    
    try {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO system_users (username, password, role_id, name, email, contact, address, signupdate) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())');
        $result = $stmt->execute([$username, $hash, $role_id, $username, $username . '@example.com', '', '']);
        
        if ($result) {
            log_error("New user registered", ['username' => $username, 'role_id' => $role_id]);
        }
        
        return $result;
    } catch (Exception $e) {
        log_error("User registration error", ['error' => $e->getMessage(), 'username' => $username]);
        return false;
    }
}

// Function to check if user has permission for specific action
function has_permission($action) {
    if (!is_logged_in()) {
        return false;
    }
    
    // Admin has all permissions
    if (has_role('admin')) {
        return true;
    }
    
    // Define permissions for different roles
    $permissions = [
        'user' => ['view_dashboard', 'view_products', 'view_sales', 'view_purchases'],
        'manager' => ['view_dashboard', 'view_products', 'view_sales', 'view_purchases', 'add_sales', 'add_purchases', 'edit_products'],
        'admin' => ['*'] // All permissions
    ];
    
    $user_role = $_SESSION['role_name'] ?? 'user';
    $user_permissions = $permissions[$user_role] ?? [];
    
    return in_array($action, $user_permissions) || in_array('*', $user_permissions);
}