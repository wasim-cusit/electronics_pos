<?php
session_start();
require_once __DIR__ . '/config.php';

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    global $base_url;
    if (!is_logged_in()) {
        header('Location: ' . $base_url . 'login.php');
        exit;
    }
}

function login($username, $password) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM system_users WHERE username = ? AND status = 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_name'] = $user['name'];
        return true;
    }
    return false;
}

function logout() {
    session_unset();
    session_destroy();
}

function current_user() {
    return $_SESSION['user_name'] ?? $_SESSION['username'] ?? null;
}

function has_role($role_name) {
    global $pdo;
    if (!isset($_SESSION['role_id'])) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare('SELECT role_name FROM roles WHERE id = ?');
        $stmt->execute([$_SESSION['role_id']]);
        $role = $stmt->fetchColumn();
        
        // Debug: Log the role check
        error_log("User ID: " . $_SESSION['user_id'] . ", Role ID: " . $_SESSION['role_id'] . ", Found Role: " . $role . ", Checking for: " . $role_name);
        
        return $role === $role_name;
    } catch (Exception $e) {
        error_log("Role check error: " . $e->getMessage());
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
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO system_users (username, password, role_id, name, email, contact, address, signupdate) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())');
    return $stmt->execute([$username, $hash, $role_id, $username, $username . '@example.com', '', '']);
}