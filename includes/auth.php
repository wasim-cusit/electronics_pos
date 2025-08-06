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
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['username'] = $user['username'];
        return true;
    }
    return false;
}

function logout() {
    session_unset();
    session_destroy();
}

function current_user() {
    return $_SESSION['username'] ?? null;
}

function has_role($role_name) {
    global $pdo;
    if (!isset($_SESSION['role_id'])) return false;
    $stmt = $pdo->prepare('SELECT name FROM roles WHERE id = ?');
    $stmt->execute([$_SESSION['role_id']]);
    $role = $stmt->fetchColumn();
    return $role === $role_name;
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
    $stmt = $pdo->prepare('INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)');
    return $stmt->execute([$username, $hash, $role_id]);
}