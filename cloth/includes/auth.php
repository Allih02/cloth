<?php
// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . getBasePath() . "auth/login.php");
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: " . getBasePath() . "index.php");
        exit();
    }
}

// Get base path based on current directory
function getBasePath() {
    $currentPath = $_SERVER['REQUEST_URI'];
    if (strpos($currentPath, '/auth/') !== false || 
        strpos($currentPath, '/products/') !== false || 
        strpos($currentPath, '/sales/') !== false || 
        strpos($currentPath, '/stock/') !== false || 
        strpos($currentPath, '/suppliers/') !== false || 
        strpos($currentPath, '/reports/') !== false) {
        return '../';
    }
    return '';
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>