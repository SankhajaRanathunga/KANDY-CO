<?php
/**
 * KANDY CO. - Authentication & Security Helpers
 */

require_once __DIR__ . '/functions.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function getLoggedInUser($pdo) {
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare("SELECT id, full_name, email, phone, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('warning', 'Please log in to access this page.');
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? 'index.php'));
        exit();
    }
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        setFlash('danger', 'Unauthorized administrative access. Please log in as an admin.');
        header('Location: ../admin/login.php');
        exit();
    }
}

function loginUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];

    if ($user['role'] === 'admin') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $user['full_name'];
    }
}

function logoutUserSession() {
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_email']);
    unset($_SESSION['user_role']);
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_username']);
    session_destroy();
}
