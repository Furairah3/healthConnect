<?php
// healthconnect/views/auth/admin-community.php
session_start();
require_once '../../app/config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php?error=required');
    exit();
}

$user_name = $_SESSION['user_name'];
$action = $_GET['action'] ?? '';
$post_id = $_GET['post_id'] ?? 0;
$message = '';
$message_type = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_post'])) {
        $post_id = $_POST['post_id'];
        try {
            $sql = "DELETE FROM hc_forum_posts WHERE post_id = :post_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':post_id' => $post_id]);
            
            $message = 'Post deleted successfully';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error deleting post: ' . $e->getMessage();
            $message_type = 'danger';
        }
    } elseif (isset($_POST['pin_post'])) {
        $post_id = $_POST['post_id'];
        $is_pinned = $_POST['is_pinned'];
        
        $sql = "UPDATE hc_forum_posts SET is_pinned = :is_pinned WHERE post_id = :post_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':is_pinned' => $is_pinned,
            ':post_id' => $post_id
        ]);
        
        $message = $is_pinned ? 'Post pinned successfully' : 'Post unpinned successfully';
        $message_type = 'success';
    }
}

// Get forum statistics
$stats_sql = "SELECT 
    COUNT(*) as total_posts,
    COUNT(DISTINCT author_id) as unique_authors,
    SUM(CASE WHEN is_pinned = 1 THEN 1 ELSE 0 END) as pinned_posts,
    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_posts
    FROM hc_forum_posts";

$stats_stmt = $pdo->query($stats_sql);
$stats = $stats_stmt->fetch();

// Get all forum posts with filters
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$sql = "SELECT fp.*, u.full_name, u.user_role,
               (SELECT COUNT(*) FROM hc_forum_replies fr WHERE fr.post_id = fp.post_id) as reply_count
        FROM hc_forum_posts fp
        JOIN hc_users u ON fp.author_id = u.user_id
        WHERE 1=1";

$params = [];

if ($search) {
    $sql .= " AND (fp.title LIKE :search OR fp.content LIKE :search OR u.full_name LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($filter === 'pinned') {
    $sql .= " AND fp.is_pinned = 1";
} elseif ($filter === 'recent') {
    $sql .= " AND fp.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($filter === 'no_replies') {
    $sql .= " AND (SELECT COUNT(*) FROM hc_forum_replies fr WHERE fr.post_id = fp.post_id) = 0";
}

$sql .= " ORDER BY fp.is_pinned DESC, fp.created_at DESC";

// Get total count for pagination
$count_sql = str_replace("fp.*, u.full_name, u.user_role", "COUNT(*) as total", $sql);
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_posts = $count_stmt->fetch()['total'];
$total_pages = ceil($total_posts / $limit);

// Get posts for current page
$sql .= " LIMIT :limit OFFSET :offset";
$params[':limit'] = $limit;
$params[':offset'] = $offset;

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$posts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Community - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --community-color: #20c997;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e8f4fc 100%);
            min-height: 100vh;
        }
        
        .community-header {
            background: linear-gradient(135deg, var(--community-color), #198754);
            color: white;
            padding: 60px 0 30px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            text-align: center;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.12);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--community-color);
            display: block;
            margin-bottom: 10px;
        }
        
        .post-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border-left: 4px solid var(--community-color);
        }
        
        .post-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        
        .post-card.pinned {
            border-left: 4px solid var(--warning-color);
            background: #fffbf0;
        }
        
        .user-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-patient { background: #e3f2fd; color: #0d6efd; }
        .badge-volunteer { background: #d1f2eb; color: #198754; }
        .badge-doctor { background: #f0e7ff; color: #6f42c1; }
        .badge-admin { background: #ffeaa7; color: #e17055; }
        
        .pinned-badge {
            background: var(--warning-color);
            color: black;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 5px;
        }
        
        .btn-community {
            background: var(--community-color);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .btn-community:hover {
            background: #198754;
            transform: translateY(-2px);
            color: white;
        }
        
        .search-box {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .action-buttons .btn {
            margin-right: 5px;
            margin-bottom: 5px;
        }
        
        @media (max-width: 768px) {
            .action-buttons .btn {
                width: 100%;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="../../index.php">
                <i class="fas fa-heartbeat me-2"></i>HealthConnect
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="admin-dashboard.php">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin-community.php">
                            <i class="fas fa-comments me-1"></i> Community
                        </a>
                    </li>
                    <li class="nav-item">
                        <span class="badge bg-primary">
                            <i class="fas fa-user-shield me-1"></i> Admin
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <div class="community-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="fw-bold mb-3">
                        <i class="fas fa-comments me-2"></i> Community Management
                    </h1>
                    <p class="lead mb-0">Manage forum posts and discussions</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="admin-dashboard.php" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $stats['total_posts']; ?></span>
                    <span>Total Posts</span>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $stats['unique_authors']; ?></span>
                    <span>Unique Authors</span>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $stats['pinned_posts']; ?></span>
                    <span>Pinned Posts</span>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $stats['today_posts']; ?></span>
                    <span>Today's Posts</span>
                </div>
            </div>
        </div>
        
        <!-- Search and Filters -->
        <div class="search-box">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search posts..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <select name="filter" class="form-control">
                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Posts</option>
                        <option value="pinned" <?php echo $filter === 'pinned' ? 'selected' : ''; ?>>Pinned Posts</option>
                        <option value="recent" <?php echo $filter === 'recent' ? 'selected' : ''; ?>>Recent (7 days)</option>
                        <option value="no_replies" <?php echo $filter === 'no_replies' ? 'selected' : ''; ?>>No Replies</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i> Search
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Posts List -->
        <?php if (empty($posts)): ?>
            <div class="empty-state">
                <i class="fas fa-comment-slash fa-4x text-muted mb-4"></i>
                <h3>No Posts Found</h3>
                <p class="text-muted">No forum posts match your search criteria.</p>
                <a href="admin-community.php" class="btn btn-primary mt-3">
                    <i class="fas fa-redo me-2"></i> Clear Filters
                </a>
            </div>
        <?php else: ?>
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        Showing <?php echo count($posts); ?> of <?php echo $total_posts; ?> posts
                        <?php if ($filter !== 'all'): ?>
                            <span class="badge bg-info ms-2"><?php echo ucfirst($filter); ?> filter</span>
                        <?php endif; ?>
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="admin-community.php?action=export" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-download me-1"></i> Export
                        </a>
                        <a href="admin-community.php?action=moderate" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-shield-alt me-1"></i> Moderate All
                        </a>
                    </div>
                </div>
                
                <?php foreach ($posts as $post): ?>
                    <div class="post-card <?php echo $post['is_pinned'] ? 'pinned' : ''; ?>">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="fw-bold mb-1">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                    <?php if ($post['is_pinned']): ?>
                                        <span class="pinned-badge">
                                            <i class="fas fa-thumbtack me-1"></i> Pinned
                                        </span>
                                    <?php endif; ?>
                                </h5>
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <span class="user-badge badge-<?php echo $post['user_role']; ?>">
                                        <?php echo htmlspecialchars($post['full_name']); ?>
                                        <small>(<?php echo $post['user_role']; ?>)</small>
                                    </span>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('M d, Y H:i', strtotime($post['created_at'])); ?>
                                    </small>
                                    <small class="text-muted">
                                        <i class="fas fa-comment me-1"></i>
                                        <?php echo $post['reply_count']; ?> replies
                                    </small>
                                </div>
                            </div>
                            <div class="action-buttons">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                    <input type="hidden" name="is_pinned" value="<?php echo $post['is_pinned'] ? '0' : '1'; ?>">
                                    <button type="submit" name="pin_post" 
                                            class="btn btn-sm btn-<?php echo $post['is_pinned'] ? 'warning' : 'outline-warning'; ?>">
                                        <i class="fas fa-thumbtack"></i>
                                        <?php echo $post['is_pinned'] ? 'Unpin' : 'Pin'; ?>
                                    </button>
                                </form>
                                <button type="button" class="btn btn-sm btn-danger" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteModal<?php echo $post['post_id']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        
                        <p class="mb-3">
                            <?php echo nl2br(htmlspecialchars(substr($post['content'], 0, 200))); ?>
                            <?php if (strlen($post['content']) > 200): ?>...<?php endif; ?>
                        </p>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="view-post.php?id=<?php echo $post['post_id']; ?>" 
                               class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-eye me-1"></i> View Full Post
                            </a>
                            <?php if ($post['reply_count'] > 0): ?>
                                <a href="view-post.php?id=<?php echo $post['post_id']; ?>#replies" 
                                   class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-comments me-1"></i> View Replies
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Delete Modal -->
                        <div class="modal fade" id="deleteModal<?php echo $post['post_id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title text-danger">
                                            <i class="fas fa-exclamation-triangle me-2"></i> Confirm Delete
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Are you sure you want to delete this post?</p>
                                        <p class="fw-bold">"<?php echo htmlspecialchars($post['title']); ?>"</p>
                                        <p class="text-danger">
                                            <small>
                                                This will also delete all replies to this post. 
                                                This action cannot be undone.
                                            </small>
                                        </p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                            Cancel
                                        </button>
                                        <form method="POST">
                                            <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                            <button type="submit" name="delete_post" class="btn btn-danger">
                                                Delete Permanently
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="mt-5 py-4 bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h6>HealthConnect Community Management</h6>
                    <small class="text-muted">
                        <i class="fas fa-comments me-1"></i> 
                        Managing <?php echo $total_posts; ?> forum posts
                    </small>
                </div>
                <div class="col-md-6 text-end">
                    <small class="text-muted">
                        © <?php echo date('Y'); ?> HealthConnect. All rights reserved.
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Confirm before deleting
        document.querySelectorAll('button[name="delete_post"]').forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
