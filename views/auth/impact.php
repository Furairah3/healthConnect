<?php
// healthconnect/views/auth/impact.php
session_start();
require_once '../../app/config/database.php';

// Check if user is logged in and is a volunteer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'volunteer') {
    header('Location: login.php?error=required');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get detailed impact statistics - FIXED: removed closed_date column, using request_date instead
$sql = "SELECT 
        COUNT(CASE WHEN request_status = 'closed' THEN 1 END) as total_helped,
        COUNT(CASE WHEN request_status = 'responded' THEN 1 END) as active_helping,
        COUNT(CASE WHEN request_status = 'pending' AND responded_by_user_id = :user_id THEN 1 END) as pending_response,
        COUNT(*) as total_requests_handled,
        AVG(TIMESTAMPDIFF(HOUR, response_date, NOW())) as avg_response_time,  -- FIXED: using NOW() instead of closed_date
        MIN(request_date) as first_response_date,
        MAX(CASE WHEN request_status = 'closed' THEN request_date ELSE response_date END) as last_response_date  -- FIXED: using request_date or response_date
        FROM hc_medical_requests 
        WHERE responded_by_user_id = :user_id2";
$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $user_id, ':user_id2' => $user_id]);
$impact_stats = $stmt->fetch();

// Get monthly impact data for chart
$monthly_data = [];
try {
    $sql = "SELECT 
            DATE_FORMAT(response_date, '%Y-%m') as month,
            COUNT(*) as helped_count,
            SUM(CASE WHEN request_status = 'closed' THEN 1 ELSE 0 END) as closed_count
            FROM hc_medical_requests 
            WHERE responded_by_user_id = :user_id 
            AND response_date IS NOT NULL
            AND response_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(response_date, '%Y-%m')
            ORDER BY month";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    $monthly_data = $stmt->fetchAll();
} catch (Exception $e) {
    $monthly_data = [];
}

// Get categories of help provided
$category_data = [];
try {
    $sql = "SELECT 
            CASE 
                WHEN request_title LIKE '%emergency%' OR request_title LIKE '%urgent%' THEN 'Emergency'
                WHEN request_title LIKE '%consult%' OR request_title LIKE '%advice%' THEN 'Consultation'
                WHEN request_title LIKE '%follow%' OR request_title LIKE '%check%' THEN 'Follow-up'
                WHEN request_title LIKE '%medication%' OR request_title LIKE '%prescription%' THEN 'Medication'
                ELSE 'General Health'
            END as category,
            COUNT(*) as count
            FROM hc_medical_requests 
            WHERE responded_by_user_id = :user_id
            GROUP BY category
            ORDER BY count DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    $category_data = $stmt->fetchAll();
} catch (Exception $e) {
    $category_data = [];
}

// Get patient feedback if available
$feedback = [];
try {
    // Check if feedback table exists
    $checkTable = $pdo->query("SHOW TABLES LIKE 'hc_feedback'")->fetch();
    
    if ($checkTable) {
        $sql = "SELECT f.feedback_rating, f.feedback_comment, u.full_name as patient_name,
                       r.request_title, f.created_at as feedback_date
                FROM hc_feedback f
                JOIN hc_medical_requests r ON f.request_id = r.request_id
                JOIN hc_users u ON r.patient_id = u.user_id
                WHERE r.responded_by_user_id = :user_id
                ORDER BY f.created_at DESC
                LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        $feedback = $stmt->fetchAll();
    }
} catch (Exception $e) {
    $feedback = [];
}

// Calculate impact level
$total_helped = $impact_stats['total_helped'] ?? 0;
if ($total_helped >= 20) {
    $impact_level = 'Community Hero';
    $level_class = 'hero';
    $level_color = '#ffd700';
} elseif ($total_helped >= 10) {
    $impact_level = 'Healthcare Champion';
    $level_class = 'champion';
    $level_color = '#28a745';
} elseif ($total_helped >= 5) {
    $impact_level = 'Active Helper';
    $level_class = 'helper';
    $level_color = '#17a2b8';
} elseif ($total_helped >= 1) {
    $impact_level = 'Getting Started';
    $level_class = 'starter';
    $level_color = '#6c757d';
} else {
    $impact_level = 'New Volunteer';
    $level_class = 'new';
    $level_color = '#adb5bd';
}

// Calculate next level requirements
if ($total_helped < 5) {
    $next_level = 'Active Helper';
    $next_level_required = 5 - $total_helped;
} elseif ($total_helped < 10) {
    $next_level = 'Healthcare Champion';
    $next_level_required = 10 - $total_helped;
} elseif ($total_helped < 20) {
    $next_level = 'Community Hero';
    $next_level_required = 20 - $total_helped;
} else {
    $next_level = 'Master Volunteer';
    $next_level_required = 30 - $total_helped;
}

// Format average response time
$avg_response_time = $impact_stats['avg_response_time'] ?? 0;
if ($avg_response_time > 24) {
    $avg_response_time_display = round($avg_response_time / 24, 1) . ' days';
} else {
    $avg_response_time_display = round($avg_response_time, 1) . ' hours';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Impact - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --impact-primary: #28a745;
            --impact-secondary: #20c997;
        }
        
        .impact-hero {
            background: linear-gradient(135deg, var(--impact-primary) 0%, var(--impact-secondary) 100%);
            color: white;
            padding: 80px 0 50px;
            border-bottom-left-radius: 30px;
            border-bottom-right-radius: 30px;
        }
        
        .impact-level-badge {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            padding: 10px 25px;
            font-size: 18px;
            font-weight: bold;
            display: inline-block;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .impact-stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: none;
            height: 100%;
        }
        
        .impact-stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .level-progress {
            height: 10px;
            background: #e9ecef;
            border-radius: 5px;
            margin: 15px 0;
            overflow: hidden;
        }
        
        .level-progress-fill {
            height: 100%;
            border-radius: 5px;
            transition: width 1s ease;
        }
        
        .level-new .level-progress-fill { background: #adb5bd; }
        .level-starter .level-progress-fill { background: #6c757d; }
        .level-helper .level-progress-fill { background: #17a2b8; }
        .level-champion .level-progress-fill { background: #28a745; }
        .level-hero .level-progress-fill { background: #ffd700; }
        
        .milestone-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-left: 4px solid var(--impact-primary);
            transition: all 0.3s ease;
        }
        
        .milestone-card:hover {
            transform: translateX(5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .milestone-icon {
            width: 50px;
            height: 50px;
            background: rgba(40, 167, 69, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: var(--impact-primary);
        }
        
        .feedback-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-top: 4px solid #ffc107;
        }
        
        .star-rating {
            color: #ffc107;
            font-size: 18px;
        }
        
        .category-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        
        .impact-timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .impact-timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(to bottom, var(--impact-primary), var(--impact-secondary));
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 30px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -33px;
            top: 5px;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background: var(--impact-primary);
            border: 3px solid white;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
        }
        
        .achievement-badge {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin: 0 auto 15px;
            border: 3px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
                    <li class="nav-item">
                        <a class="nav-link" href="volunteer-dashboard.php">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="community.php">
                            <i class="fas fa-users me-1"></i> Community
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="impact.php">
                            <i class="fas fa-chart-line me-1"></i> My Impact
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="volunteer-avatar me-2">
                                <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                            </div>
                            <span><?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="my-responses.php"><i class="fas fa-history me-2"></i> My Responses</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Impact Hero Section -->
    <div class="impact-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="fw-bold display-4 mb-3">Your Impact Journey</h1>
                    <p class="lead mb-4 opacity-75">Track your contributions and see how you're making a difference.</p>
                    
                    <div class="impact-level-badge mb-4 level-<?php echo $level_class; ?>" 
                         style="border-color: <?php echo $level_color; ?>; color: <?php echo $level_color; ?>;">
                        <i class="fas fa-trophy me-2"></i> <?php echo $impact_level; ?>
                    </div>
                    
                    <div class="level-progress">
                        <div class="level-progress-fill level-<?php echo $level_class; ?>" 
                             style="width: <?php echo min(($total_helped / 20) * 100, 100); ?>%;"></div>
                    </div>
                    
                    <?php if ($next_level_required > 0): ?>
                        <p class="text-white mb-0">
                            <i class="fas fa-arrow-up me-1"></i>
                            <strong><?php echo $next_level_required; ?></strong> more helps to reach <strong><?php echo $next_level; ?></strong>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="col-lg-6 text-end">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <div class="achievement-badge" style="background: linear-gradient(135deg, #ffd700, #ffa500);">
                                <i class="fas fa-heart text-white"></i>
                            </div>
                            <h4 class="fw-bold text-white mb-0"><?php echo $total_helped; ?></h4>
                            <small class="text-white opacity-75">Lives Impacted</small>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="achievement-badge" style="background: linear-gradient(135deg, #28a745, #20c997);">
                                <i class="fas fa-clock text-white"></i>
                            </div>
                            <h4 class="fw-bold text-white mb-0">
                                <?php echo $avg_response_time_display; ?>
                            </h4>
                            <small class="text-white opacity-75">Avg Response Time</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Impact Statistics -->
    <div class="container mt-5">
        <div class="row g-4 mb-5">
            <div class="col-lg-3 col-md-6">
                <div class="impact-stat-card">
                    <div class="stat-icon" style="background: rgba(40, 167, 69, 0.1); color: var(--impact-primary);">
                        <i class="fas fa-hands-helping"></i>
                    </div>
                    <div class="stat-number text-success"><?php echo $impact_stats['total_helped'] ?? 0; ?></div>
                    <h6 class="fw-bold mb-2">Successfully Helped</h6>
                    <p class="text-muted small mb-0">Patients you've helped complete their healthcare journey.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="impact-stat-card">
                    <div class="stat-icon" style="background: rgba(13, 202, 240, 0.1); color: #0dcaf0;">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stat-number text-info"><?php echo $impact_stats['active_helping'] ?? 0; ?></div>
                    <h6 class="fw-bold mb-2">Currently Helping</h6>
                    <p class="text-muted small mb-0">Active conversations with patients.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="impact-stat-card">
                    <div class="stat-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="stat-number text-warning"><?php echo $impact_stats['total_requests_handled'] ?? 0; ?></div>
                    <h6 class="fw-bold mb-2">Total Responses</h6>
                    <p class="text-muted small mb-0">All healthcare requests you've responded to.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="impact-stat-card">
                    <div class="stat-icon" style="background: rgba(111, 66, 193, 0.1); color: #6f42c1;">
                        <i class="fas fa-medal"></i>
                    </div>
                    <div class="stat-number text-purple"><?php echo $impact_level; ?></div>
                    <h6 class="fw-bold mb-2">Impact Level</h6>
                    <p class="text-muted small mb-0">Your current volunteer achievement level.</p>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Charts -->
            <div class="col-lg-8">
                <!-- Monthly Impact Chart -->
                <div class="chart-container mb-4">
                    <h5 class="fw-bold mb-4"><i class="fas fa-chart-bar me-2"></i> Your Monthly Impact</h5>
                    <canvas id="monthlyImpactChart" height="250"></canvas>
                </div>
                
                <!-- Help Categories -->
                <div class="chart-container">
                    <h5 class="fw-bold mb-4"><i class="fas fa-list-alt me-2"></i> Types of Help Provided</h5>
                    <div class="row g-3">
                        <?php if (empty($category_data)): ?>
                            <div class="col-12 text-center py-4">
                                <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                                <h6>No category data yet</h6>
                                <p class="text-muted small">Start helping patients to see your impact categories.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($category_data as $category): ?>
                                <div class="col-md-6">
                                    <div class="milestone-card">
                                        <div class="d-flex align-items-center">
                                            <div class="milestone-icon me-3">
                                                <i class="fas fa-stethoscope"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($category['category']); ?></h6>
                                                <p class="text-muted small mb-0"><?php echo $category['count']; ?> cases</p>
                                            </div>
                                            <?php if ($impact_stats['total_requests_handled'] > 0): ?>
                                                <span class="badge bg-success"><?php echo round(($category['count'] / $impact_stats['total_requests_handled']) * 100); ?>%</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Milestones & Feedback -->
            <div class="col-lg-4">
                <!-- Milestones -->
                <div class="chart-container mb-4">
                    <h5 class="fw-bold mb-4"><i class="fas fa-flag me-2"></i> Your Milestones</h5>
                    <div class="impact-timeline">
                        <?php if ($total_helped >= 1 && !empty($impact_stats['first_response_date'])): ?>
                            <div class="timeline-item">
                                <h6 class="fw-bold mb-1">First Help Provided</h6>
                                <p class="text-muted small mb-0">You helped your first patient!</p>
                                <small class="text-muted"><?php echo date('M d, Y', strtotime($impact_stats['first_response_date'])); ?></small>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($total_helped >= 5): ?>
                            <div class="timeline-item">
                                <h6 class="fw-bold mb-1">Active Helper Achievement</h6>
                                <p class="text-muted small mb-0">Helped 5+ patients successfully</p>
                                <small class="text-muted">Level Up!</small>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($total_helped >= 10): ?>
                            <div class="timeline-item">
                                <h6 class="fw-bold mb-1">Healthcare Champion</h6>
                                <p class="text-muted small mb-0">Reached 10+ successful helps</p>
                                <small class="text-muted">Community Leader</small>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($avg_response_time < 24 && $total_helped > 0): ?>
                            <div class="timeline-item">
                                <h6 class="fw-bold mb-1">Rapid Responder</h6>
                                <p class="text-muted small mb-0">Average response time under 24 hours</p>
                                <small class="text-muted">Quick Help!</small>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($total_helped == 0): ?>
                            <div class="timeline-item">
                                <h6 class="fw-bold mb-1">Start Your Journey</h6>
                                <p class="text-muted small mb-0">Help your first patient to begin</p>
                                <a href="respond-requests.php" class="btn btn-sm btn-success mt-2">
                                    <i class="fas fa-hands-helping me-1"></i> Start Helping
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Patient Feedback -->
                <?php if (!empty($feedback)): ?>
                    <div class="chart-container">
                        <h5 class="fw-bold mb-4"><i class="fas fa-star me-2"></i> Patient Feedback</h5>
                        <?php foreach ($feedback as $item): ?>
                            <div class="feedback-card mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($item['patient_name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars(substr($item['request_title'], 0, 30)); ?>...</small>
                                    </div>
                                    <div class="star-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?php echo $i <= $item['feedback_rating'] ? '' : '-o'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <?php if (!empty($item['feedback_comment'])): ?>
                                    <p class="text-muted small mb-0">"<?php echo htmlspecialchars(substr($item['feedback_comment'], 0, 80)); ?>"</p>
                                <?php endif; ?>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo date('M d, Y', strtotime($item['feedback_date'])); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Impact Summary -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="chart-container">
                    <h5 class="fw-bold mb-4"><i class="fas fa-trophy me-2"></i> Impact Summary</h5>
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <h2 class="fw-bold text-success"><?php echo $total_helped; ?></h2>
                            <small class="text-muted">Lives Impacted</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <h2 class="fw-bold text-primary">
                                <?php echo $impact_stats['total_requests_handled'] ?? 0; ?>
                            </h2>
                            <small class="text-muted">Total Responses</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <h2 class="fw-bold text-info">
                                <?php echo $avg_response_time_display; ?>
                            </h2>
                            <small class="text-muted">Avg Response Time</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <h2 class="fw-bold text-warning"><?php echo $impact_level; ?></h2>
                            <small class="text-muted">Current Level</small>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <a href="respond-requests.php" class="btn btn-success btn-lg px-5">
                            <i class="fas fa-hands-helping me-2"></i> Continue Making Impact
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">
                        <i class="fas fa-chart-line text-success me-2"></i>
                        HealthConnect Impact Dashboard
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Monthly Impact Chart
        <?php if (!empty($monthly_data)): ?>
        const monthlyCtx = document.getElementById('monthlyImpactChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?php 
                    $months = [];
                    foreach ($monthly_data as $data) {
                        $months[] = date('M Y', strtotime($data['month'] . '-01'));
                    }
                    echo json_encode($months);
                ?>,
                datasets: [{
                    label: 'Helped Patients',
                    data: <?php echo json_encode(array_column($monthly_data, 'helped_count')); ?>,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Closed Cases',
                    data: <?php echo json_encode(array_column($monthly_data, 'closed_count')); ?>,
                    borderColor: '#0dcaf0',
                    backgroundColor: 'rgba(13, 202, 240, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        <?php else: ?>
        // If no data, show a message
        document.getElementById('monthlyImpactChart').parentElement.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                <h6>No impact data yet</h6>
                <p class="text-muted small">Start helping patients to see your impact chart.</p>
            </div>
        `;
        <?php endif; ?>

        // Animation for progress bars
        document.querySelectorAll('.level-progress-fill').forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0';
            setTimeout(() => {
                bar.style.width = width;
            }, 500);
        });

        // Print impact summary
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Impact Summary for <?php echo htmlspecialchars($user_name); ?>:');
            console.log('- Total Helped: <?php echo $total_helped; ?> patients');
            console.log('- Impact Level: <?php echo $impact_level; ?>');
            <?php if ($next_level_required > 0): ?>
            console.log('- Next Goal: <?php echo $next_level_required; ?> more helps to <?php echo $next_level; ?>');
            <?php endif; ?>
        });
    </script>
</body>
</html>