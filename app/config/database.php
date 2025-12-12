<?php
// healthconnect/app/config/database.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'foureiratou.idi');
define('DB_PASS', 'Fouri@2025SQL');
define('DB_NAME', 'webtech_2025A_foureiratou_idi');
define('IS_DEV', true);

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    if (IS_DEV) {
        die("Database connection failed: " . $e->getMessage());
    } else {
        error_log("Database connection failed: " . $e->getMessage());
        die("System temporarily unavailable. Please try again later.");
    }
}

// Session timeout (24 minutes)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1440)) {
    session_unset();
    session_destroy();
    if (IS_DEV) {
        header('Location: /healthconnect/views/auth/login.php?error=timeout');
    } else {
        header('Location: /healthconnect/views/auth/login.php?error=timeout');
    }
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// CSRF token generation (only if not exists)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper function for prepared statements
function executePreparedQuery($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        error_log("SQL Error: " . $e->getMessage() . " - Query: " . $sql);
        return false;
    }
}

// Sanitize input helper
function cleanInput($data) {
    if (empty($data)) return '';
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']);
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}
?>