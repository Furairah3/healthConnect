<?php
// healthconnect/views/auth/logout.php

// Start session
session_start();

// Include database for logging
require_once '../../app/config/database.php';

// Log activity if user is logged in
if (isset($_SESSION['user_id'])) {
    try {
        // Log logout activity
        $sql = "INSERT INTO hc_activity_logs (user_id, activity_type, activity_description, ip_address, user_agent) 
                VALUES (:user_id, 'logout', 'User logged out', :ip, :agent)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        // Clear remember me cookie if exists
        if (isset($_COOKIE['healthconnect_remember'])) {
            $token = $_COOKIE['healthconnect_remember'];
            
            try {
                // Remove from database
                $sql = "DELETE FROM hc_user_sessions WHERE session_id = :token";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':token' => $token]);
            } catch (Exception $e) {
                error_log("Error removing session: " . $e->getMessage());
            }
            
            // Clear cookie
            setcookie('healthconnect_remember', '', time() - 3600, '/', '', false, true);
        }
        
    } catch (Exception $e) {
        error_log("Logout logging error: " . $e->getMessage());
    }
}

// Destroy all session data
$_SESSION = array();

// Destroy session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page with success message
header('Location: login.php?success=logout');
exit();
?>