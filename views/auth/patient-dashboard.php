<?php
// healthconnect/views/auth/patient-dashboard.php
session_start();
require_once '../../app/config/database.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header('Location: login.php?error=required');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get patient's health requests
$sql = "SELECT r.request_id, r.request_title, r.request_description, 
               r.request_status, r.request_date, r.response_date,
               u.full_name as responded_by_name
        FROM hc_medical_requests r
        LEFT JOIN hc_users u ON r.responded_by_user_id = u.user_id
        WHERE r.patient_id = :patient_id
        ORDER BY r.request_date DESC
        LIMIT 5";
$stmt = $pdo->prepare($sql);
$stmt->execute([':patient_id' => $user_id]);
$requests = $stmt->fetchAll();

// Get medical tips - FIXED: using correct column name tip_id
$sql = "SELECT t.tip_id, t.tip_title, t.tip_content, t.tip_date,
               u.full_name as doctor_name, t.total_likes, t.category
        FROM hc_health_tips t
        JOIN hc_users u ON t.doctor_user_id = u.user_id
        WHERE t.is_published = 1
        ORDER BY t.tip_date DESC
        LIMIT 3";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$medical_tips = $stmt->fetchAll();

// Count requests by status
$pending_requests = array_filter($requests, function($req) {
    return $req['request_status'] === 'pending';
});

$responded_requests = array_filter($requests, function($req) {
    return $req['request_status'] === 'responded';
});

$closed_requests = array_filter($requests, function($req) {
    return $req['request_status'] === 'closed';
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        :root {
            --primary-light: #e3e9ff;
            --success-light: #d4edda;
            --warning-light: #fff3cd;
            --info-light: #d1ecf1;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            color: white;
            padding: 60px 0 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,192C672,181,768,139,864,138.7C960,139,1056,181,1152,197.3C1248,213,1344,203,1392,197.3L1440,192L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            background-position: center;
        }
        
        .stat-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            overflow: hidden;
            position: relative;
        }
        
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
        }
        
        .stat-card.pending::before { background: linear-gradient(90deg, #ffc107, #ff9800); }
        .stat-card.responded::before { background: linear-gradient(90deg, #28a745, #20c997); }
        .stat-card.closed::before { background: linear-gradient(90deg, #6c757d, #495057); }
        .stat-card.tips::before { background: linear-gradient(90deg, #17a2b8, #0dcaf0); }
        
        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin: 0 auto 20px;
        }
        
        .stat-card.pending .stat-icon { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .stat-card.responded .stat-icon { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .stat-card.closed .stat-icon { background: rgba(108, 117, 125, 0.1); color: #6c757d; }
        .stat-card.tips .stat-icon { background: rgba(23, 162, 184, 0.1); color: #17a2b8; }
        
        .request-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border-left: 4px solid #4361ee;
        }
        
        .request-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .request-status {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-responded {
            background: #d4edda;
            color: #155724;
        }
        
        .status-closed {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .tip-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: none;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .tip-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .like-btn {
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            background: none;
            padding: 0;
        }
        
        .like-btn:hover {
            transform: scale(1.2);
        }
        
        .liked {
            color: #dc3545 !important;
        }
        
        .quick-action-btn {
            padding: 15px;
            border-radius: 12px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
            text-align: center;
            display: block;
            color: #333;
            background: white;
            text-decoration: none;
        }
        
        .quick-action-btn:hover {
            border-color: #4361ee;
            background: rgba(67, 97, 238, 0.05);
            transform: translateY(-3px);
            color: #333;
        }
        
        .profile-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: bold;
        }
        
        .welcome-message {
            background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .tip-category {
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 15px;
            background: rgba(0,0,0,0.05);
        }
        
        .category-general { background: #e9ecef; color: #495057; }
        .category-chronic_disease { background: #d4edda; color: #155724; }
        .category-first_aid { background: #fff3cd; color: #856404; }
        .category-pediatric { background: #cce5ff; color: #004085; }
        .category-maternal_health { background: #f8d7da; color: #721c24; }
        .category-mental_health { background: #e0c8f0; color: #4a1f8b; }
        .category-wellness { background: #d1ecf1; color: #0c5460; }
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
                        <a class="nav-link active" href="patient-dashboard.php">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create-request.php">
                            <i class="fas fa-plus-circle me-1"></i> New Request
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="health-tips.php">
                            <i class="fas fa-lightbulb me-1"></i> Health Tips
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="profile-avatar me-2">
                                <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                            </div>
                            <span><?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a></li>
                            <li><a class="dropdown-item" href="my-requests.php"><i class="fas fa-file-medical me-2"></i> My Requests</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="welcome-message">
                        <h1 class="fw-bold mb-3">Welcome back, <?php echo htmlspecialchars($user_name); ?>! 👋</h1>
                        <p class="lead mb-0">How can we help you today? Your health is our priority.</p>
                    </div>
                </div>
                <div class="col-lg-4 text-end">
                    <a href="create-request.php" class="btn btn-light btn-lg px-4 shadow">
                        <i class="fas fa-plus-circle me-2"></i> New Health Request
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="container mb-5">
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card pending p-4 text-center">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h2 class="fw-bold mb-2"><?php echo count($pending_requests); ?></h2>
                    <p class="text-muted mb-0">Pending Requests</p>
                    <small class="text-warning">Awaiting response</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card responded p-4 text-center">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 class="fw-bold mb-2"><?php echo count($responded_requests); ?></h2>
                    <p class="text-muted mb-0">Responded</p>
                    <small class="text-success">Received advice</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card closed p-4 text-center">
                    <div class="stat-icon">
                        <i class="fas fa-archive"></i>
                    </div>
                    <h2 class="fw-bold mb-2"><?php echo count($closed_requests); ?></h2>
                    <p class="text-muted mb-0">Closed</p>
                    <small class="text-secondary">Completed cases</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card tips p-4 text-center">
                    <div class="stat-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h2 class="fw-bold mb-2"><?php echo count($medical_tips); ?></h2>
                    <p class="text-muted mb-0">Health Tips</p>
                    <small class="text-info">Available tips</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="row">
            <!-- Recent Requests -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0 py-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-0"><i class="fas fa-file-medical text-primary me-2"></i> Recent Health Requests</h4>
                                <p class="text-muted mb-0 mt-1">Your recent medical consultations</p>
                            </div>
                            <a href="create-request.php" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> New Request
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <?php if (empty($requests)): ?>
                            <div class="text-center py-5">
                                <div class="mb-4">
                                    <i class="fas fa-inbox fa-4x text-muted"></i>
                                </div>
                                <h5>No health requests yet</h5>
                                <p class="text-muted mb-4">Submit your first health request to get started</p>
                                <a href="create-request.php" class="btn btn-primary px-4">
                                    <i class="fas fa-plus me-2"></i> Create First Request
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($requests as $request): ?>
                                <div class="request-card mb-3 p-4">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h6 class="fw-bold mb-2"><?php echo htmlspecialchars($request['request_title']); ?></h6>
                                            <p class="text-muted small mb-2">
                                                <?php echo substr(htmlspecialchars($request['request_description']), 0, 100); ?>...
                                            </p>
                                            <div class="d-flex align-items-center">
                                                <span class="request-status status-<?php echo $request['request_status']; ?> me-3">
                                                    <?php echo ucfirst($request['request_status']); ?>
                                                </span>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo date('M d, Y', strtotime($request['request_date'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <?php if ($request['responded_by_name']): ?>
                                                <small class="text-muted d-block mb-2">
                                                    <i class="fas fa-user-md me-1"></i>
                                                    By <?php echo htmlspecialchars($request['responded_by_name']); ?>
                                                </small>
                                            <?php endif; ?>
                                            <a href="/~foureiratou.idi/healthConnect/views/auth/view-request.php?id=<?php echo $request['request_id']; ?>" 
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye me-1"></i> View Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-4">
                                <a href="my-requests.php" class="btn btn-outline-primary px-4">
                                    <i class="fas fa-list me-1"></i> View All Requests
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Health Tips -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0 py-4">
                        <h5 class="mb-0"><i class="fas fa-lightbulb text-warning me-2"></i> Health Tips</h5>
                        <p class="text-muted mb-0 mt-1">Tips from medical professionals</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if (empty($medical_tips)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-lightbulb fa-2x text-muted mb-3"></i>
                                <p class="text-muted">No health tips available yet.</p>
                                <a href="health-tips.php" class="btn btn-outline-warning btn-sm">
                                    <i class="fas fa-search me-1"></i> Browse Tips
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($medical_tips as $tip): ?>
                                <div class="tip-card p-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($tip['tip_title']); ?></h6>
                                        <?php if ($tip['category']): ?>
                                            <span class="tip-category category-<?php echo $tip['category']; ?>">
                                                <?php echo ucwords(str_replace('_', ' ', $tip['category'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="small text-muted mb-2">
                                        <?php 
                                        $content = strip_tags($tip['tip_content']);
                                        echo substr($content, 0, 80) . (strlen($content) > 80 ? '...' : ''); 
                                        ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-user-md me-1"></i>
                                            Dr. <?php echo htmlspecialchars($tip['doctor_name']); ?>
                                        </small>
                                        <div>
                                            <button class="like-btn me-2 text-muted" data-tip-id="<?php echo $tip['tip_id']; ?>">
                                                <i class="fas fa-heart"></i>
                                                <small><?php echo $tip['total_likes']; ?></small>
                                            </button>
                                            <a href="health-tips.php?id=<?php echo $tip['tip_id']; ?>" class="text-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center">
                                <a href="health-tips.php" class="btn btn-outline-warning btn-sm">
                                    <i class="fas fa-book-medical me-1"></i> Browse All Tips
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0 py-4">
                        <h5 class="mb-0"><i class="fas fa-bolt text-success me-2"></i> Quick Actions</h5>
                        <p class="text-muted mb-0 mt-1">Frequently used features</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-6">
                                <a href="create-request.php" class="quick-action-btn">
                                    <div class="mb-3">
                                        <i class="fas fa-plus-circle fa-2x text-primary"></i>
                                    </div>
                                    <h6 class="fw-bold mb-1">New Request</h6>
                                    <small class="text-muted">Submit health concern</small>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="health-tips.php" class="quick-action-btn">
                                    <div class="mb-3">
                                        <i class="fas fa-lightbulb fa-2x text-warning"></i>
                                    </div>
                                    <h6 class="fw-bold mb-1">Health Tips</h6>
                                    <small class="text-muted">Browse advice</small>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="my-requests.php" class="quick-action-btn">
                                    <div class="mb-3">
                                        <i class="fas fa-history fa-2x text-info"></i>
                                    </div>
                                    <h6 class="fw-bold mb-1">History</h6>
                                    <small class="text-muted">View past requests</small>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="profile.php" class="quick-action-btn">
                                    <div class="mb-3">
                                        <i class="fas fa-user fa-2x text-success"></i>
                                    </div>
                                    <h6 class="fw-bold mb-1">Profile</h6>
                                    <small class="text-muted">Update details</small>
                                </a>
                            </div>
                        </div>
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
                        <i class="fas fa-heartbeat text-primary me-2"></i>
                        HealthConnect Patient Dashboard
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
        // Simple like functionality (frontend only - for demo)
        document.querySelectorAll('.like-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const heartIcon = this.querySelector('i');
                const countElement = this.querySelector('small');
                
                if (heartIcon.classList.contains('liked')) {
                    heartIcon.classList.remove('liked');
                    heartIcon.classList.add('text-muted');
                    countElement.textContent = parseInt(countElement.textContent) - 1;
                } else {
                    heartIcon.classList.add('liked');
                    heartIcon.classList.remove('text-muted');
                    countElement.textContent = parseInt(countElement.textContent) + 1;
                }
            });
        });
        
        // Animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        // Observe cards for animation
        document.querySelectorAll('.stat-card, .request-card, .tip-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            observer.observe(card);
        });
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>