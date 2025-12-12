<?php
// healthconnect/views/auth/health-tips.php
session_start();
require_once '../../app/config/database.php';

// Check if user is logged in (all users can view health tips)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?error=required');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];

// Handle liking/unliking tips
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tip_id'])) {
    $tip_id = $_POST['tip_id'];
    $action = $_POST['action'] ?? 'like';
    
    // Check if tip exists
    $check_sql = "SELECT tip_id, total_likes FROM hc_health_tips WHERE tip_id = :tip_id";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([':tip_id' => $tip_id]);
    $tip = $check_stmt->fetch();
    
    if ($tip) {
        $new_likes = $action === 'like' ? $tip['total_likes'] + 1 : max(0, $tip['total_likes'] - 1);
        
        // Update likes count
        $update_sql = "UPDATE hc_health_tips SET total_likes = :likes WHERE tip_id = :tip_id";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([':likes' => $new_likes, ':tip_id' => $tip_id]);
        
        // Record like in database (you'd need a hc_tip_likes table for this)
        // For now, we'll just update the count
        
        echo json_encode(['success' => true, 'total_likes' => $new_likes, 'action' => $action]);
        exit();
    }
    
    echo json_encode(['success' => false]);
    exit();
}

// Filter and search handling
$category = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'recent';

// Base query
$sql = "SELECT t.tip_id, t.tip_title, t.tip_content, t.tip_date, t.total_likes,
               t.category, t.difficulty_level, t.reading_time_minutes,
               u.full_name as doctor_name, u.profession as doctor_profession
        FROM hc_health_tips t
        JOIN hc_users u ON t.doctor_user_id = u.user_id
        WHERE t.is_published = 1 AND t.is_active = 1";

$params = [];

// Apply category filter
if ($category !== 'all') {
    $sql .= " AND t.category = :category";
    $params[':category'] = $category;
}

// Apply search
if (!empty($search)) {
    $sql .= " AND (t.tip_title LIKE :search OR t.tip_content LIKE :search)";
    $params[':search'] = "%$search%";
}

// Apply sorting
switch ($sort) {
    case 'popular':
        $sql .= " ORDER BY t.total_likes DESC, t.tip_date DESC";
        break;
    case 'oldest':
        $sql .= " ORDER BY t.tip_date ASC";
        break;
    case 'recent':
    default:
        $sql .= " ORDER BY t.tip_date DESC";
        break;
}

// Execute query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tips = $stmt->fetchAll();

// Get categories for filter
$categories_sql = "SELECT DISTINCT category FROM hc_health_tips WHERE category IS NOT NULL AND category != '' AND is_published = 1";
$categories_stmt = $pdo->query($categories_sql);
$categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);

// Get featured tips
$featured_sql = "SELECT t.tip_id, t.tip_title, t.category, u.full_name as doctor_name
                 FROM hc_health_tips t
                 JOIN hc_users u ON t.doctor_user_id = u.user_id
                 WHERE t.is_published = 1 AND t.is_featured = 1
                 ORDER BY t.tip_date DESC LIMIT 3";
$featured_stmt = $pdo->query($featured_sql);
$featured_tips = $featured_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Tips - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }
        
        .page-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 60px 0 40px;
            margin-bottom: 30px;
        }
        
        .tip-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
            overflow: hidden;
        }
        
        .tip-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
        
        .tip-category {
            font-size: 12px;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .category-general { background: #e9ecef; color: #495057; }
        .category-chronic_disease { background: #d4edda; color: #155724; }
        .category-first_aid { background: #fff3cd; color: #856404; }
        .category-pediatric { background: #cce5ff; color: #004085; }
        .category-maternal_health { background: #f8d7da; color: #721c24; }
        .category-mental_health { background: #e0c8f0; color: #4a1f8b; }
        .category-wellness { background: #d1ecf1; color: #0c5460; }
        
        .difficulty-badge {
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 15px;
            font-weight: 500;
        }
        
        .difficulty-basic { background: #d4edda; color: #155724; }
        .difficulty-intermediate { background: #fff3cd; color: #856404; }
        .difficulty-advanced { background: #f8d7da; color: #721c24; }
        
        .like-btn {
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            background: none;
            color: #6c757d;
        }
        
        .like-btn:hover {
            transform: scale(1.2);
        }
        
        .like-btn.liked {
            color: #dc3545 !important;
        }
        
        .featured-tip {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .featured-tip::before {
            content: "⭐ FEATURED";
            position: absolute;
            top: 10px;
            right: -30px;
            background: rgba(0,0,0,0.2);
            color: white;
            padding: 5px 40px;
            font-size: 10px;
            font-weight: bold;
            transform: rotate(45deg);
        }
        
        .reading-time {
            font-size: 12px;
            color: #6c757d;
        }
        
        .doctor-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: bold;
        }
        
        .filter-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .nav-back-btn {
            color: white;
            text-decoration: none;
        }
        
        .nav-back-btn:hover {
            color: rgba(255,255,255,0.8);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="../../index.php">
                <i class="fas fa-heartbeat me-2"></i>HealthConnect
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <?php if ($user_role === 'patient'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="patient-dashboard.php">
                                <i class="fas fa-home me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my-requests.php">
                                <i class="fas fa-file-medical me-1"></i> My Requests
                            </a>
                        </li>
                    <?php elseif ($user_role === 'volunteer'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="volunteer-dashboard.php">
                                <i class="fas fa-home me-1"></i> Dashboard
                            </a>
                        </li>
                    <?php elseif ($user_role === 'doctor'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="doctor-dashboard.php">
                                <i class="fas fa-home me-1"></i> Dashboard
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="health-tips.php">
                            <i class="fas fa-lightbulb me-1"></i> Health Tips
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="doctor-avatar me-2">
                                <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                            </div>
                            <span><?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <a href="<?php echo $user_role === 'patient' ? 'patient-dashboard.php' : ($user_role === 'volunteer' ? 'volunteer-dashboard.php' : 'doctor-dashboard.php'); ?>" 
                       class="nav-back-btn mb-3 d-inline-block">
                        <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                    </a>
                    <h1 class="fw-bold mb-3">Health Tips & Advice</h1>
                    <p class="lead mb-0">Expert medical advice from certified doctors. Stay informed about your health.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="bg-white rounded p-3 shadow-sm d-inline-block">
                        <h5 class="text-success mb-2"><?php echo count($tips); ?></h5>
                        <p class="text-muted mb-0">Health Tips Available</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Featured Tips -->
    <?php if (!empty($featured_tips)): ?>
        <div class="container mb-5">
            <h3 class="mb-4"><i class="fas fa-star text-warning me-2"></i> Featured Tips</h3>
            <div class="row g-4">
                <?php foreach ($featured_tips as $tip): ?>
                    <div class="col-md-4">
                        <div class="featured-tip">
                            <span class="tip-category category-<?php echo $tip['category'] ?? 'general'; ?> mb-2">
                                <?php echo ucwords(str_replace('_', ' ', $tip['category'] ?? 'general')); ?>
                            </span>
                            <h5 class="fw-bold"><?php echo htmlspecialchars($tip['tip_title']); ?></h5>
                            <p class="text-muted small mb-3">By Dr. <?php echo htmlspecialchars($tip['doctor_name']); ?></p>
                            <a href="view-tip.php?id=<?php echo $tip['tip_id']; ?>" class="btn btn-success btn-sm">
                                Read Now <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="container">
        <!-- Filters -->
        <div class="filter-card">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h5 class="mb-3">Categories</h5>
                    <div class="d-flex flex-wrap">
                        <a href="?category=all&sort=<?php echo $sort; ?>&search=<?php echo urlencode($search); ?>" 
                           class="btn btn-outline-success btn-sm mb-2 me-2 <?php echo $category === 'all' ? 'active' : ''; ?>">
                            All Categories
                        </a>
                        <?php foreach ($categories as $cat): ?>
                            <a href="?category=<?php echo urlencode($cat); ?>&sort=<?php echo $sort; ?>&search=<?php echo urlencode($search); ?>" 
                               class="btn btn-outline-success btn-sm mb-2 me-2 <?php echo $category === $cat ? 'active' : ''; ?>">
                                <?php echo ucwords(str_replace('_', ' ', $cat)); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <h5 class="mb-3">Sort By</h5>
                    <div class="btn-group" role="group">
                        <a href="?category=<?php echo urlencode($category); ?>&sort=recent&search=<?php echo urlencode($search); ?>" 
                           class="btn btn-outline-success <?php echo $sort === 'recent' ? 'active' : ''; ?>">
                            <i class="fas fa-clock me-1"></i> Most Recent
                        </a>
                        <a href="?category=<?php echo urlencode($category); ?>&sort=popular&search=<?php echo urlencode($search); ?>" 
                           class="btn btn-outline-success <?php echo $sort === 'popular' ? 'active' : ''; ?>">
                            <i class="fas fa-fire me-1"></i> Most Popular
                        </a>
                        <a href="?category=<?php echo urlencode($category); ?>&sort=oldest&search=<?php echo urlencode($search); ?>" 
                           class="btn btn-outline-success <?php echo $sort === 'oldest' ? 'active' : ''; ?>">
                            <i class="fas fa-history me-1"></i> Oldest First
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <form method="GET" class="mt-3 mt-md-0">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Search tips..." value="<?php echo htmlspecialchars($search); ?>">
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                            <button class="btn btn-success" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                            <?php if (!empty($search)): ?>
                                <a href="?category=<?php echo urlencode($category); ?>&sort=<?php echo $sort; ?>" 
                                   class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tips Grid -->
        <?php if (empty($tips)): ?>
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-lightbulb fa-4x text-muted"></i>
                </div>
                <h4>No health tips found</h4>
                <p class="text-muted mb-4">
                    <?php if (!empty($search)): ?>
                        No tips match your search criteria.
                    <?php elseif ($category !== 'all'): ?>
                        No tips found in the <?php echo str_replace('_', ' ', $category); ?> category.
                    <?php else: ?>
                        No health tips available yet.
                    <?php endif; ?>
                </p>
                <a href="?category=all" class="btn btn-success px-4">
                    <i class="fas fa-redo me-2"></i> View All Tips
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($tips as $tip): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="tip-card">
                            <div class="card-body">
                                <!-- Category & Difficulty -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <?php if ($tip['category']): ?>
                                        <span class="tip-category category-<?php echo $tip['category']; ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $tip['category'])); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($tip['difficulty_level']): ?>
                                        <span class="difficulty-badge difficulty-<?php echo $tip['difficulty_level']; ?>">
                                            <?php echo ucfirst($tip['difficulty_level']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Title -->
                                <h5 class="fw-bold mb-3"><?php echo htmlspecialchars($tip['tip_title']); ?></h5>
                                
                                <!-- Content Preview -->
                                <p class="text-muted mb-4">
                                    <?php 
                                    $content = strip_tags($tip['tip_content']);
                                    echo substr($content, 0, 120);
                                    if (strlen($content) > 120) echo '...';
                                    ?>
                                </p>
                                
                                <!-- Doctor Info & Stats -->
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="doctor-avatar me-2">
                                            <?php echo strtoupper(substr($tip['doctor_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <small class="fw-bold d-block">Dr. <?php echo htmlspecialchars($tip['doctor_name']); ?></small>
                                            <small class="text-muted"><?php echo htmlspecialchars($tip['doctor_profession'] ?? 'Doctor'); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button class="like-btn" data-tip-id="<?php echo $tip['tip_id']; ?>">
                                            <i class="fas fa-heart"></i>
                                            <small><?php echo $tip['total_likes']; ?></small>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Reading Time & Date -->
                                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                    <?php if ($tip['reading_time_minutes']): ?>
                                        <small class="reading-time">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo $tip['reading_time_minutes']; ?> min read
                                        </small>
                                    <?php endif; ?>
                                    
                                    <small class="text-muted">
                                        <?php echo date('M d, Y', strtotime($tip['tip_date'])); ?>
                                    </small>
                                </div>
                                
                                <!-- View Button -->
                                <div class="text-center mt-4">
                                    <a href="view-tip.php?id=<?php echo $tip['tip_id']; ?>" 
                                       class="btn btn-outline-success btn-sm w-100">
                                        <i class="fas fa-book-medical me-2"></i> Read Full Tip
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Total count -->
            <div class="text-center mt-5">
                <p class="text-muted">
                    Showing <?php echo count($tips); ?> tip<?php echo count($tips) !== 1 ? 's' : ''; ?>
                    <?php if ($category !== 'all'): ?> in <?php echo str_replace('_', ' ', $category); ?><?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">
                        <i class="fas fa-heartbeat text-success me-2"></i>
                        HealthConnect Health Tips Library
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0 text-muted">
                        &copy; <?php echo date('Y'); ?> HealthConnect. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Like functionality
        document.querySelectorAll('.like-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tipId = this.getAttribute('data-tip-id');
                const heartIcon = this.querySelector('i');
                const countElement = this.querySelector('small');
                
                if (!tipId) return;
                
                const isLiked = heartIcon.classList.contains('liked');
                const action = isLiked ? 'unlike' : 'like';
                
                // Send AJAX request
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'like-tip.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                if (response.action === 'like') {
                                    heartIcon.classList.add('liked');
                                    heartIcon.style.color = '#dc3545';
                                } else {
                                    heartIcon.classList.remove('liked');
                                    heartIcon.style.color = '';
                                }
                                countElement.textContent = response.total_likes;
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                        }
                    }
                };
                
                xhr.send('tip_id=' + tipId + '&action=' + action);
            });
        });
        
        // Filter buttons active state
        document.querySelectorAll('.btn-group .btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.btn-group .btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>