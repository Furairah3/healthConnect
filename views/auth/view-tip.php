<?php
// healthconnect/views/auth/view-tip.php
session_start();
require_once '../../app/config/database.php';

// Check if user is logged in (all users can view tips)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?error=required');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];

// Get tip ID from URL
$tip_id = $_GET['id'] ?? 0;

if (!$tip_id) {
    header('Location: health-tips.php?error=notfound');
    exit();
}

// Get the tip details
$sql = "SELECT t.*, 
               u.full_name as doctor_name, 
               u.profession as doctor_profession,
               u.email_address as doctor_email
        FROM hc_health_tips t
        JOIN hc_users u ON t.doctor_user_id = u.user_id
        WHERE t.tip_id = :tip_id AND t.is_published = 1";
        
$stmt = $pdo->prepare($sql);
$stmt->execute([':tip_id' => $tip_id]);
$tip = $stmt->fetch();

if (!$tip) {
    header('Location: health-tips.php?error=notfound');
    exit();
}

// Increment view count
$update_sql = "UPDATE hc_health_tips SET total_views = total_views + 1 WHERE tip_id = :tip_id";
$update_stmt = $pdo->prepare($update_sql);
$update_stmt->execute([':tip_id' => $tip_id]);

// Handle like/unlike
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'like') {
        $new_likes = $tip['total_likes'] + 1;
        $update_likes_sql = "UPDATE hc_health_tips SET total_likes = :likes WHERE tip_id = :tip_id";
        $update_likes_stmt = $pdo->prepare($update_likes_sql);
        $update_likes_stmt->execute([':likes' => $new_likes, ':tip_id' => $tip_id]);
        
        $tip['total_likes'] = $new_likes;
    }
    
    // For AJAX requests
    if (isset($_POST['ajax'])) {
        echo json_encode(['success' => true, 'total_likes' => $tip['total_likes']]);
        exit();
    }
    
    // For regular form submission
    header('Location: view-tip.php?id=' . $tip_id);
    exit();
}

// Get related tips (same category)
$related_sql = "SELECT t.tip_id, t.tip_title, t.total_likes, t.tip_date,
                       u.full_name as doctor_name
                FROM hc_health_tips t
                JOIN hc_users u ON t.doctor_user_id = u.user_id
                WHERE t.category = :category 
                AND t.tip_id != :tip_id 
                AND t.is_published = 1
                ORDER BY t.tip_date DESC
                LIMIT 3";
                
$related_stmt = $pdo->prepare($related_sql);
$related_stmt->execute([
    ':category' => $tip['category'],
    ':tip_id' => $tip_id
]);
$related_tips = $related_stmt->fetchAll();

// Get doctor's other tips
$doctor_tips_sql = "SELECT t.tip_id, t.tip_title, t.category, t.tip_date
                    FROM hc_health_tips t
                    WHERE t.doctor_user_id = :doctor_id 
                    AND t.tip_id != :tip_id 
                    AND t.is_published = 1
                    ORDER BY t.tip_date DESC
                    LIMIT 3";
                    
$doctor_tips_stmt = $pdo->prepare($doctor_tips_sql);
$doctor_tips_stmt->execute([
    ':doctor_id' => $tip['doctor_user_id'],
    ':tip_id' => $tip_id
]);
$doctor_other_tips = $doctor_tips_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tip['tip_title']); ?> - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }
        
        .tip-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 60px 0 40px;
            margin-bottom: 30px;
        }
        
        .tip-content {
            font-size: 18px;
            line-height: 1.8;
            color: #333;
        }
        
        .tip-content h2, .tip-content h3, .tip-content h4 {
            margin-top: 30px;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        
        .tip-content p {
            margin-bottom: 20px;
        }
        
        .tip-content ul, .tip-content ol {
            margin-bottom: 20px;
            padding-left: 20px;
        }
        
        .tip-content li {
            margin-bottom: 8px;
        }
        
        .tip-content table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        
        .tip-content th, .tip-content td {
            padding: 10px;
            border: 1px solid #dee2e6;
        }
        
        .tip-content th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .tip-content blockquote {
            border-left: 4px solid #28a745;
            padding-left: 20px;
            margin: 20px 0;
            font-style: italic;
            color: #6c757d;
        }
        
        .tip-meta {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .category-badge {
            font-size: 14px;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .category-general { background: #e9ecef; color: #495057; }
        .category-chronic_disease { background: #d4edda; color: #155724; }
        .category-first_aid { background: #fff3cd; color: #856404; }
        .category-pediatric { background: #cce5ff; color: #004085; }
        .category-maternal_health { background: #f8d7da; color: #721c24; }
        .category-mental_health { background: #e0c8f0; color: #4a1f8b; }
        .category-wellness { background: #d1ecf1; color: #0c5460; }
        
        .doctor-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .doctor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .doctor-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: bold;
            margin: 0 auto 20px;
        }
        
        .action-btn {
            border-radius: 25px;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .like-btn {
            background: white;
            border: 2px solid #dc3545;
            color: #dc3545;
        }
        
        .like-btn:hover, .like-btn.liked {
            background: #dc3545;
            color: white;
        }
        
        .related-tip-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .related-tip-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .stat-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 15px;
            border-radius: 20px;
            background: #f8f9fa;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .stat-badge i {
            margin-right: 5px;
        }
        
        .reading-progress {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: #e9ecef;
            z-index: 1000;
        }
        
        .reading-progress-bar {
            height: 100%;
            background: #28a745;
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .share-dropdown {
            min-width: 200px;
        }
        
        .print-btn:hover {
            background: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Reading Progress Bar -->
    <div class="reading-progress">
        <div class="reading-progress-bar" id="readingProgress"></div>
    </div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-success" href="../../index.php">
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
                        <a class="nav-link" href="health-tips.php">
                            <i class="fas fa-lightbulb me-1"></i> All Tips
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <div style="width: 35px; height: 35px; background: linear-gradient(135deg, #28a745, #20c997); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: bold;">
                                <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                            </div>
                            <span class="ms-2"><?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?></span>
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

    <!-- Tip Header -->
    <div class="tip-header">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-transparent p-0">
                            <li class="breadcrumb-item"><a href="health-tips.php" class="text-white">Health Tips</a></li>
                            <li class="breadcrumb-item"><a href="health-tips.php?category=<?php echo urlencode($tip['category']); ?>" class="text-white"><?php echo ucwords(str_replace('_', ' ', $tip['category'])); ?></a></li>
                            <li class="breadcrumb-item active text-white" aria-current="page">Tip Details</li>
                        </ol>
                    </nav>
                    <h1 class="fw-bold mb-4"><?php echo htmlspecialchars($tip['tip_title']); ?></h1>
                    
                    <div class="d-flex flex-wrap align-items-center mb-3">
                        <div class="me-4 mb-2">
                            <i class="fas fa-user-md me-2"></i>
                            <strong>By Dr. <?php echo htmlspecialchars($tip['doctor_name']); ?></strong>
                        </div>
                        <div class="me-4 mb-2">
                            <i class="fas fa-calendar me-2"></i>
                            <?php echo date('F j, Y', strtotime($tip['tip_date'])); ?>
                        </div>
                        <?php if ($tip['reading_time_minutes']): ?>
                            <div class="me-4 mb-2">
                                <i class="fas fa-clock me-2"></i>
                                <?php echo $tip['reading_time_minutes']; ?> min read
                            </div>
                        <?php endif; ?>
                        <div class="mb-2">
                            <i class="fas fa-eye me-2"></i>
                            <?php echo $tip['total_views'] + 1; ?> views
                        </div>
                    </div>
                    
                    <?php if ($tip['category']): ?>
                        <span class="category-badge category-<?php echo $tip['category']; ?>">
                            <?php echo ucwords(str_replace('_', ' ', $tip['category'])); ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($tip['difficulty_level']): ?>
                        <span class="badge bg-light text-dark ms-2">
                            <?php echo ucfirst($tip['difficulty_level']); ?> Level
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="row">
            <!-- Left Column: Tip Content -->
            <div class="col-lg-8">
                <!-- Action Buttons -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="like">
                            <button type="submit" class="btn like-btn action-btn <?php echo $tip['total_likes'] > 0 ? 'liked' : ''; ?>">
                                <i class="fas fa-heart me-2"></i>
                                Like <span class="badge bg-white text-dark ms-2"><?php echo $tip['total_likes']; ?></span>
                            </button>
                        </form>
                        
                        <div class="btn-group ms-3">
                            <button type="button" class="btn btn-outline-success action-btn" data-bs-toggle="dropdown">
                                <i class="fas fa-share-alt me-2"></i> Share
                            </button>
                            <div class="dropdown-menu share-dropdown">
                                <h6 class="dropdown-header">Share this tip</h6>
                                <a class="dropdown-item" href="#" onclick="shareOnFacebook()">
                                    <i class="fab fa-facebook me-2 text-primary"></i> Facebook
                                </a>
                                <a class="dropdown-item" href="#" onclick="shareOnTwitter()">
                                    <i class="fab fa-twitter me-2 text-info"></i> Twitter
                                </a>
                                <a class="dropdown-item" href="#" onclick="shareOnWhatsApp()">
                                    <i class="fab fa-whatsapp me-2 text-success"></i> WhatsApp
                                </a>
                                <a class="dropdown-item" href="#" onclick="copyLink()">
                                    <i class="fas fa-link me-2"></i> Copy Link
                                </a>
                            </div>
                        </div>
                        
                        <button class="btn btn-outline-secondary action-btn ms-3" onclick="window.print()">
                            <i class="fas fa-print me-2"></i> Print
                        </button>
                    </div>
                    
                    <div>
                        <?php if ($user_role === 'doctor' && $tip['doctor_user_id'] == $user_id): ?>
                            <a href="edit-tip.php?id=<?php echo $tip_id; ?>" class="btn btn-warning action-btn">
                                <i class="fas fa-edit me-2"></i> Edit
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Tip Content -->
                <article class="tip-content mb-5">
                    <?php echo $tip['tip_content']; ?>
                </article>
                
                <!-- Disclaimer -->
                <?php if (!empty($tip['disclaimer'])): ?>
                    <div class="alert alert-info">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i> Disclaimer</h5>
                        <?php echo nl2br(htmlspecialchars($tip['disclaimer'])); ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-light border">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Medical Disclaimer:</strong> This information is for educational purposes only and is not intended as medical advice. Always consult with a qualified healthcare professional for medical concerns.
                        </small>
                    </div>
                <?php endif; ?>
                
                <!-- Tags -->
                <?php if (!empty($tip['tags'])): ?>
                    <div class="mb-5">
                        <h5 class="mb-3"><i class="fas fa-tags me-2"></i> Tags</h5>
                        <div class="d-flex flex-wrap">
                            <?php 
                            $tags = explode(',', $tip['tags']);
                            foreach ($tags as $tag): 
                                $tag = trim($tag);
                                if (!empty($tag)):
                            ?>
                                <a href="health-tips.php?search=<?php echo urlencode($tag); ?>" class="btn btn-sm btn-outline-success me-2 mb-2">
                                    #<?php echo htmlspecialchars($tag); ?>
                                </a>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Right Column: Sidebar -->
            <div class="col-lg-4">
                <!-- Doctor Info -->
                <div class="doctor-card mb-4">
                    <div class="card-body text-center">
                        <div class="doctor-avatar">
                            <?php echo strtoupper(substr($tip['doctor_name'], 0, 1)); ?>
                        </div>
                        <h4 class="fw-bold mb-2">Dr. <?php echo htmlspecialchars($tip['doctor_name']); ?></h4>
                        <p class="text-muted mb-3"><?php echo htmlspecialchars($tip['doctor_profession']); ?></p>
                        
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="stat-badge">
                                    <i class="fas fa-lightbulb text-warning"></i>
                                    <?php 
                                    $doctor_tips_count_sql = "SELECT COUNT(*) as count FROM hc_health_tips WHERE doctor_user_id = :doctor_id";
                                    $doctor_tips_count_stmt = $pdo->prepare($doctor_tips_count_sql);
                                    $doctor_tips_count_stmt->execute([':doctor_id' => $tip['doctor_user_id']]);
                                    $doctor_tips_count = $doctor_tips_count_stmt->fetch()['count'];
                                    echo $doctor_tips_count . ' Tips';
                                    ?>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-badge">
                                    <i class="fas fa-star text-warning"></i>
                                    <?php 
                                    $doctor_likes_sql = "SELECT SUM(total_likes) as total FROM hc_health_tips WHERE doctor_user_id = :doctor_id";
                                    $doctor_likes_stmt = $pdo->prepare($doctor_likes_sql);
                                    $doctor_likes_stmt->execute([':doctor_id' => $tip['doctor_user_id']]);
                                    $doctor_likes = $doctor_likes_stmt->fetch()['total'] ?? 0;
                                    echo number_format($doctor_likes) . ' Likes';
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($tip['doctor_email'])): ?>
                            <a href="mailto:<?php echo htmlspecialchars($tip['doctor_email']); ?>" class="btn btn-outline-success w-100 mb-2">
                                <i class="fas fa-envelope me-2"></i> Contact Doctor
                            </a>
                        <?php endif; ?>
                        
                        <a href="health-tips.php?search=<?php echo urlencode($tip['doctor_name']); ?>" class="btn btn-success w-100">
                            <i class="fas fa-stethoscope me-2"></i> View All Tips
                        </a>
                    </div>
                </div>
                
                <!-- Doctor's Other Tips -->
                <?php if (!empty($doctor_other_tips)): ?>
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0">
                            <h5 class="fw-bold mb-0">More from Dr. <?php echo htmlspecialchars(explode(' ', $tip['doctor_name'])[0]); ?></h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($doctor_other_tips as $other_tip): ?>
                                <div class="mb-3 pb-3 border-bottom">
                                    <a href="view-tip.php?id=<?php echo $other_tip['tip_id']; ?>" class="text-decoration-none">
                                        <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($other_tip['tip_title']); ?></h6>
                                    </a>
                                    <small class="text-muted d-block mb-1">
                                        <i class="fas fa-tag me-1"></i>
                                        <?php echo ucwords(str_replace('_', ' ', $other_tip['category'])); ?>
                                    </small>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo date('M d, Y', strtotime($other_tip['tip_date'])); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                            <a href="health-tips.php?search=<?php echo urlencode($tip['doctor_name']); ?>" class="btn btn-outline-success btn-sm w-100">
                                View All <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Related Tips -->
                <?php if (!empty($related_tips)): ?>
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0">
                            <h5 class="fw-bold mb-0">Related Tips</h5>
                            <p class="text-muted mb-0 small">More tips about <?php echo str_replace('_', ' ', $tip['category']); ?></p>
                        </div>
                        <div class="card-body">
                            <?php foreach ($related_tips as $related): ?>
                                <div class="related-tip-card mb-3">
                                    <div class="card-body">
                                        <a href="view-tip.php?id=<?php echo $related['tip_id']; ?>" class="text-decoration-none">
                                            <h6 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($related['tip_title']); ?></h6>
                                        </a>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="fas fa-user-md me-1"></i>
                                                Dr. <?php echo htmlspecialchars($related['doctor_name']); ?>
                                            </small>
                                            <div>
                                                <small class="text-muted me-3">
                                                    <i class="fas fa-heart text-danger"></i>
                                                    <?php echo $related['total_likes']; ?>
                                                </small>
                                                <small class="text-muted">
                                                    <?php echo date('M d', strtotime($related['tip_date'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <a href="health-tips.php?category=<?php echo urlencode($tip['category']); ?>" class="btn btn-outline-success btn-sm w-100">
                                Browse Category <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">
                        <i class="fas fa-heartbeat text-success me-2"></i>
                        HealthConnect Health Tips
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
        // Reading progress
        window.addEventListener('scroll', function() {
            const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = (winScroll / height) * 100;
            document.getElementById('readingProgress').style.width = scrolled + '%';
        });
        
        // Share functions
        function shareOnFacebook() {
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.title);
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}&quote=${title}`, '_blank');
        }
        
        function shareOnTwitter() {
            const url = encodeURIComponent(window.location.href);
            const text = encodeURIComponent(document.title);
            window.open(`https://twitter.com/intent/tweet?url=${url}&text=${text}`, '_blank');
        }
        
        function shareOnWhatsApp() {
            const url = encodeURIComponent(window.location.href);
            const text = encodeURIComponent(document.title);
            window.open(`https://api.whatsapp.com/send?text=${text}%20${url}`, '_blank');
        }
        
        function copyLink() {
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(() => {
                alert('Link copied to clipboard!');
            });
        }
        
        // AJAX like functionality
        document.querySelector('.like-btn').addEventListener('click', function(e) {
            if (this.tagName === 'BUTTON') {
                e.preventDefault();
                
                const form = this.closest('form');
                const formData = new FormData(form);
                formData.append('ajax', '1');
                
                fetch('view-tip.php?id=<?php echo $tip_id; ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const badge = this.querySelector('.badge');
                        badge.textContent = data.total_likes;
                        
                        if (data.total_likes > 0) {
                            this.classList.add('liked');
                            this.style.background = '#dc3545';
                            this.style.color = 'white';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    form.submit(); // Fallback to regular form submission
                });
            }
        });
        
        // Print styling
        const originalTitle = document.title;
        window.addEventListener('beforeprint', () => {
            document.title = "Health Tip: <?php echo addslashes($tip['tip_title']); ?> - HealthConnect";
        });
        
        window.addEventListener('afterprint', () => {
            document.title = originalTitle;
        });
        
        // Add class to tables for better styling
        document.querySelectorAll('.tip-content table').forEach(table => {
            table.classList.add('table', 'table-bordered');
        });
    </script>
</body>
</html>