<?php
// healthconnect/views/auth/view-post.php
session_start();
require_once '../../app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?error=required');
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
    header('Location: ' . $_SESSION['user_role'] . '-dashboard.php?error=post_not_found');
    exit();
}

// Get replies
$replies_sql = "SELECT fr.*, u.full_name, u.user_role 
                FROM hc_forum_replies fr
                JOIN hc_users u ON fr.author_id = u.user_id
                WHERE fr.post_id = :post_id
                ORDER BY fr.created_at ASC";
$replies_stmt = $pdo->prepare($replies_sql);
$replies_stmt->execute([':post_id' => $post_id]);
$replies = $replies_stmt->fetchAll();

// Handle new reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reply'])) {
    $reply_content = $_POST['reply_content'] ?? '';
    
    if (!empty($reply_content)) {
        $insert_sql = "INSERT INTO hc_forum_replies (post_id, author_id, content) 
                       VALUES (:post_id, :author_id, :content)";
        $insert_stmt = $pdo->prepare($insert_sql);
        $insert_stmt->execute([
            ':post_id' => $post_id,
            ':author_id' => $_SESSION['user_id'],
            ':content' => $reply_content
        ]);
        
        header('Location: view-post.php?id=' . $post_id . '#replies');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Post - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container py-4">
        <!-- Back button -->
        <div class="mb-4">
            <a href="admin-community.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Back to Community
            </a>
        </div>
        
        <!-- Post Content -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><?php echo htmlspecialchars($post['title']); ?></h4>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <span class="badge bg-secondary">
                            <?php echo ucfirst($post['user_role']); ?>
                        </span>
                        <span class="ms-2"><?php echo htmlspecialchars($post['full_name']); ?></span>
                    </div>
                    <small class="text-muted">
                        <i class="fas fa-clock me-1"></i>
                        <?php echo date('M d, Y H:i', strtotime($post['created_at'])); ?>
                    </small>
                </div>
                
                <div class="post-content">
                    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                </div>
            </div>
        </div>
        
        <!-- Replies Section -->
        <div id="replies">
            <h5 class="mb-3">
                <i class="fas fa-comments me-2 text-success"></i>
                Replies (<?php echo count($replies); ?>)
            </h5>
            
            <?php if (empty($replies)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No replies yet. Be the first to respond!
                </div>
            <?php else: ?>
                <?php foreach ($replies as $reply): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge bg-secondary">
                                        <?php echo ucfirst($reply['user_role']); ?>
                                    </span>
                                    <span class="ms-2"><?php echo htmlspecialchars($reply['full_name']); ?></span>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo date('M d, Y H:i', strtotime($reply['created_at'])); ?>
                                </small>
                            </div>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
