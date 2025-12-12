<?php
// healthconnect/views/auth/like-tip.php
session_start();
require_once '../../app/config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit();
}

$tip_id = $_POST['tip_id'] ?? 0;
$action = $_POST['action'] ?? 'like';

if (!$tip_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid tip ID']);
    exit();
}

// Get current likes
$sql = "SELECT total_likes FROM hc_health_tips WHERE tip_id = :tip_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':tip_id' => $tip_id]);
$tip = $stmt->fetch();

if (!$tip) {
    echo json_encode(['success' => false, 'error' => 'Tip not found']);
    exit();
}

// Calculate new likes
$new_likes = $action === 'like' ? $tip['total_likes'] + 1 : max(0, $tip['total_likes'] - 1);

// Update database
$update_sql = "UPDATE hc_health_tips SET total_likes = :likes WHERE tip_id = :tip_id";
$update_stmt = $pdo->prepare($update_sql);
$update_stmt->execute([':likes' => $new_likes, ':tip_id' => $tip_id]);

echo json_encode([
    'success' => true,
    'action' => $action,
    'total_likes' => $new_likes
]);