<?php
// healthconnect/views/auth/get-post.php
session_start();
require_once '../../app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$post_id = $_GET['id'] ?? 0;

// Get post details
$sql = "SELECT fp.*, u.full_name, u.user_role 
        FROM hc_forum_posts fp
        JOIN hc_users u ON fp.author_id = u.user_id
        WHERE fp.post_id = :post_id";

$stmt = $pdo->prepare($sql);
$stmt->execute([':post_id' => $post_id]);
$post = $stmt->fetch();

if (!$post) {
    echo json_encode(['success' => false, 'message' => 'Post not found']);
    exit();
}

// Format the date
$post['created_at'] = date('M d, Y H:i', strtotime($post['created_at']));

echo json_encode(['success' => true, 'post' => $post]);
?>
