<?php
// healthconnect/api/tips.php

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header
header('Content-Type: application/json');

// Include database configuration
require_once '../app/config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

$action = $_GET['action'] ?? '';

// Handle like/unlike
if ($action === 'like') {
    try {
        // Get input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['tip_id'])) {
            echo json_encode(['success' => false, 'message' => 'Tip ID is required']);
            exit();
        }
        
        $tip_id = (int)$input['tip_id'];
        $user_id = $_SESSION['user_id'];
        
        // Check if already liked
        $sql = "SELECT like_id FROM hc_tip_likes WHERE tip_id = :tip_id AND user_who_liked_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':tip_id' => $tip_id, ':user_id' => $user_id]);
        $existing_like = $stmt->fetch();
        
        $pdo->beginTransaction();
        
        if ($existing_like) {
            // Unlike
            $sql = "DELETE FROM hc_tip_likes WHERE tip_id = :tip_id AND user_who_liked_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':tip_id' => $tip_id, ':user_id' => $user_id]);
            
            // Update like count
            $sql = "UPDATE hc_health_tips SET total_likes = total_likes - 1 WHERE tip_id = :tip_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':tip_id' => $tip_id]);
            
            $liked = false;
            $message = 'Tip unliked';
        } else {
            // Like
            $sql = "INSERT INTO hc_tip_likes (tip_id, user_who_liked_id) VALUES (:tip_id, :user_id)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':tip_id' => $tip_id, ':user_id' => $user_id]);
            
            // Update like count
            $sql = "UPDATE hc_health_tips SET total_likes = total_likes + 1 WHERE tip_id = :tip_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':tip_id' => $tip_id]);
            
            $liked = true;
            $message = 'Tip liked';
        }
        
        // Get updated like count
        $sql = "SELECT total_likes FROM hc_health_tips WHERE tip_id = :tip_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':tip_id' => $tip_id]);
        $tip = $stmt->fetch();
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'liked' => $liked,
            'message' => $message,
            'total_likes' => $tip['total_likes']
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Like error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error processing like']);
    }
    exit();
}

// Handle tip creation (for doctors)
if ($action === 'create' && $_SESSION['user_role'] === 'doctor' && $_SESSION['is_approved']) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['title']) || empty($input['content'])) {
            echo json_encode(['success' => false, 'message' => 'Title and content are required']);
            exit();
        }
        
        $title = cleanInput($input['title']);
        $content = cleanInput($input['content']);
        $doctor_id = $_SESSION['user_id'];
        
        $sql = "INSERT INTO hc_health_tips (doctor_user_id, tip_title, tip_content) 
                VALUES (:doctor_id, :title, :content)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':doctor_id' => $doctor_id,
            ':title' => $title,
            ':content' => $content
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Health tip created successfully'
        ]);
        
    } catch (Exception $e) {
        error_log("Tip creation error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error creating tip']);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>