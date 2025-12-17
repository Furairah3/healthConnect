<?php
// healthconnect/views/auth/doctor-dashboard.php
session_start();
require_once '../../app/config/database.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header('Location: login.php?error=required');
    exit();
}

// If doctor is not approved, redirect to pending approval
if (!isset($_SESSION['is_approved']) || !$_SESSION['is_approved']) {
    header('Location: pending-approval.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Helper function to safely format dates
function safeDateFormat($dateString, $format = 'M d') {
    if (empty($dateString) || $dateString === '0000-00-00 00:00:00' || $dateString === null) {
        return 'N/A';
    }
    return date($format, strtotime($dateString));
}

// Get doctor statistics
$sql = "SELECT 
        (SELECT COUNT(*) FROM hc_medical_requests WHERE responded_by_user_id = :doctor_id AND request_status = 'closed') as total_helped,
        (SELECT COUNT(*) FROM hc_medical_requests WHERE request_status = 'pending') as pending_requests,
        (SELECT COUNT(*) FROM hc_health_tips WHERE doctor_user_id = :doctor_id2) as total_tips,
        (SELECT COUNT(*) FROM hc_medical_requests WHERE responded_by_user_id = :doctor_id3 AND request_status = 'responded') as active_cases";
        
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':doctor_id' => $user_id,
    ':doctor_id2' => $user_id,
    ':doctor_id3' => $user_id
]);
$stats = $stmt->fetch();

// Get recent pending requests
$sql = "SELECT r.request_id, r.request_title, r.request_description, 
               r.request_date, u.full_name as patient_name, u.location,
               r.urgency_level
        FROM hc_medical_requests r
        JOIN hc_users u ON r.patient_id = u.user_id
        WHERE r.request_status = 'pending'
        ORDER BY r.request_date DESC
        LIMIT 5";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$pending_requests = $stmt->fetchAll();

// Get doctor's recent responses - FIXED to handle null response_date
$sql = "SELECT r.request_id, r.request_title, u.full_name as patient_name,
               COALESCE(r.response_date, r.request_date) as display_date, 
               r.request_status
        FROM hc_medical_requests r
        JOIN hc_users u ON r.patient_id = u.user_id
        WHERE r.responded_by_user_id = :doctor_id
        ORDER BY COALESCE(r.response_date, r.request_date) DESC
        LIMIT 5";
$stmt = $pdo->prepare($sql);
$stmt->execute([':doctor_id' => $user_id]);
$my_responses = $stmt->fetchAll();

// Get doctor's recent tips
$tips_sql = "SELECT tip_id, tip_title, total_likes, tip_date 
             FROM hc_health_tips 
             WHERE doctor_user_id = :doctor_id 
             ORDER BY tip_date DESC 
             LIMIT 3";
$tips_stmt = $pdo->prepare($tips_sql);
$tips_stmt->execute([':doctor_id' => $user_id]);
$recent_tips = $tips_stmt->fetchAll();

// Get today's activity
$activity_sql = "SELECT 
    (SELECT COUNT(*) FROM hc_medical_requests WHERE responded_by_user_id = :doctor_id1 AND DATE(response_date) = CURDATE()) as today_responses,
    (SELECT COUNT(*) FROM hc_health_tips WHERE doctor_user_id = :doctor_id2 AND DATE(tip_date) = CURDATE()) as today_tips";
$activity_stmt = $pdo->prepare($activity_sql);
$activity_stmt->execute([
    ':doctor_id1' => $user_id,
    ':doctor_id2' => $user_id
]);
$activity = $activity_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --doctor-primary: #0d6efd;
            --doctor-secondary: #052c65;
            --doctor-accent: #20c997;
            --doctor-dark: #041c4c;
            --doctor-light: #e3f2fd;
        }
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e3f2fd 100%);
            min-height: 100vh;
        }
        
        /* Simplified Header */
        .dashboard-header {
            background: linear-gradient(135deg, var(--doctor-primary) 0%, var(--doctor-secondary) 100%);
            color: white;
            padding: 60px 0 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        /* Cleaner Stat Cards */
        .stat-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            overflow: hidden;
            position: relative;
            min-height: 160px;
            background: white;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
        }
        
        .stat-card.patients { border-top: 4px solid #0d6efd; }
        .stat-card.pending { border-top: 4px solid #ffc107; }
        .stat-card.tips { border-top: 4px solid #20c997; }
        .stat-card.active { border-top: 4px solid #6f42c1; }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 15px;
            transition: all 0.3s ease;
        }
        
        .stat-card.patients .stat-icon { 
            background: rgba(13, 110, 253, 0.1); 
            color: #0d6efd;
        }
        .stat-card.pending .stat-icon { 
            background: rgba(255, 193, 7, 0.1); 
            color: #ffc107;
        }
        .stat-card.tips .stat-icon { 
            background: rgba(32, 201, 151, 0.1); 
            color: #20c997;
        }
        .stat-card.active .stat-icon { 
            background: rgba(111, 66, 193, 0.1); 
            color: #6f42c1;
        }
        
        .stat-card:hover .stat-icon {
            transform: scale(1.1);
        }
        
        .stat-card h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--doctor-dark);
        }
        
        .stat-card p {
            color: #666;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .stat-card small {
            color: #888;
            font-size: 0.85rem;
        }
        
        /* Clean Request Cards */
        .request-card {
            border: 1px solid rgba(0,0,0,0.08);
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.04);
            transition: all 0.3s ease;
            margin-bottom: 15px;
            padding: 15px;
            background: white;
        }
        
        .request-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-color: var(--doctor-primary);
            transform: translateY(-2px);
        }
        
        .urgency-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .urgency-high { background: #dc3545; color: white; }
        .urgency-medium { background: #ffc107; color: #212529; }
        .urgency-low { background: #20c997; color: white; }
        
        /* Clean Quick Action Cards */
        .quick-action-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
            background: white;
        }
        
        .quick-action-card:hover {
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .quick-action-card .card-body {
            padding: 1.5rem;
        }
        
        /* Tip Cards */
        .tip-card {
            padding: 12px 15px;
            border-radius: 8px;
            background: rgba(32, 201, 151, 0.05);
            margin-bottom: 10px;
            transition: all 0.3s;
            border-left: 3px solid #20c997;
            border: 1px solid rgba(32, 201, 151, 0.1);
        }
        
        .tip-card:hover {
            background: rgba(32, 201, 151, 0.1);
            border-left-color: #198754;
        }
        
        /* Welcome Message */
        .welcome-message {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        /* Activity Items */
        .activity-item {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            margin-bottom: 10px;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.2rem;
        }
        
        .activity-icon.responses { background: rgba(255, 255, 255, 0.2); color: white; }
        .activity-icon.tips { background: rgba(255, 255, 255, 0.2); color: white; }
        
        .activity-count {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            display: block;
        }
        
        .activity-label {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.9);
        }
        
        /* Doctor Badge */
        .doctor-badge {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
            display: inline-block;
        }
        
        /* Profile Avatar */
        .profile-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--doctor-primary), var(--doctor-secondary));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }
        
        /* Button Styles */
        .btn-lift {
            transition: all 0.3s ease;
        }
        
        .btn-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        /* Table Styles */
        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.04);
        }
        
        /* Footer */
        footer {
            margin-top: 40px;
            border-top: 1px solid rgba(0,0,0,0.05);
        }
        
        /* Animated Text */
        .animate-charcter {
            background: linear-gradient(
                to right,
                #ffffff 0%,
                #a6c1ee 25%,
                #0d6efd 50%,
                #052c65 75%
            );
            background-size: 200% auto;
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: textclip 3s linear infinite;
        }
        
        @keyframes textclip {
            to {
                background-position: 200% center;
            }
        }
        
        /* Counter Animation */
        @keyframes countUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .counter {
            animation: countUp 1s ease-out;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .dashboard-header {
                padding: 40px 0 30px;
            }
            
            .stat-card {
                margin-bottom: 20px;
            }
            
            .welcome-message {
                margin-bottom: 20px;
            }
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
                        <a class="nav-link active" href="doctor-dashboard.php">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="respond-requests.php">
                            <i class="fas fa-comments-medical me-1"></i> Respond to Requests
                            <?php if (($stats['pending_requests'] ?? 0) > 0): ?>
                                <span class="badge bg-danger ms-1">
                                    <?php echo $stats['pending_requests']; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create-tip.php">
                            <i class="fas fa-lightbulb me-1"></i> Health Tips
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="profile-avatar me-2">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <span>Dr. <?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="verification.php"><i class="fas fa-badge-check me-2"></i> Verification</a></li>
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
                        <h1 class="fw-bold mb-3 animate-charcter">
                            Welcome, Dr. <?php echo htmlspecialchars($user_name); ?>! 👨‍⚕️👩‍⚕️
                        </h1>
                        <p class="lead mb-0">Thank you for helping bridge the healthcare gap in rural communities.</p>
                        
                        <!-- Today's Activity -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="activity-item">
                                    <div class="activity-icon responses">
                                        <i class="fas fa-comment-medical"></i>
                                    </div>
                                    <div>
                                        <span class="activity-count"><?php echo $activity['today_responses'] ?? 0; ?></span>
                                        <span class="activity-label">Today's Responses</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="activity-item">
                                    <div class="activity-icon tips">
                                        <i class="fas fa-lightbulb"></i>
                                    </div>
                                    <div>
                                        <span class="activity-count"><?php echo $activity['today_tips'] ?? 0; ?></span>
                                        <span class="activity-label">Tips Created</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-end position-relative">
                    <div class="mb-4">
                        <span class="doctor-badge">
                            <i class="fas fa-badge-check me-1"></i> Verified Doctor
                        </span>
                    </div>
                    <a href="respond-requests.php" 
                    id="helpNowBtn"
                    class="btn btn-light btn-lg px-4 shadow btn-lift">
                        <i class="fas fa-hands-helping me-2"></i> Help Patients
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="container mb-5">
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card patients p-4 text-center">
                    <div class="stat-icon">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <h2 class="fw-bold mb-2 counter" data-target="<?php echo $stats['total_helped'] ?? 0; ?>">0</h2>
                    <p class="mb-0 fw-semibold">Patients Helped</p>
                    <small>Lifetime assistance</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card pending p-4 text-center">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h2 class="fw-bold mb-2 counter" data-target="<?php echo $stats['pending_requests'] ?? 0; ?>">0</h2>
                    <p class="mb-0 fw-semibold">Pending Requests</p>
                    <small>Need your help</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card tips p-4 text-center">
                    <div class="stat-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h2 class="fw-bold mb-2 counter" data-target="<?php echo $stats['total_tips'] ?? 0; ?>">0</h2>
                    <p class="mb-0 fw-semibold">Health Tips</p>
                    <small>Shared with community</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card active p-4 text-center">
                    <div class="stat-icon">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                    <h2 class="fw-bold mb-2 counter" data-target="<?php echo $stats['active_cases'] ?? 0; ?>">0</h2>
                    <p class="mb-0 fw-semibold">Active Cases</p>
                    <small>Currently assisting</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="row">
            <!-- Left Column: Pending Requests & Recent Tips -->
            <div class="col-lg-6">
                <!-- Pending Requests -->
                <div class="card mb-4 quick-action-card">
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-0"><i class="fas fa-hourglass-half text-warning me-2"></i> Pending Requests</h4>
                                <p class="text-muted mb-0 mt-1">Patients waiting for assistance</p>
                            </div>
                            <a href="respond-requests.php" class="btn btn-warning btn-sm btn-lift">
                                <i class="fas fa-hands-helping me-1"></i> View All
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <?php if (empty($pending_requests)): ?>
                            <div class="text-center py-4">
                                <div class="mb-3">
                                    <i class="fas fa-check-circle fa-3x text-success"></i>
                                </div>
                                <h5>No pending requests!</h5>
                                <p class="text-muted mb-0">All current requests have been responded to.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($pending_requests as $index => $request): ?>
                                <div class="request-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($request['request_title']); ?></h6>
                                        <span class="urgency-badge urgency-<?php echo strtolower($request['urgency_level'] ?? 'medium'); ?>">
                                            <?php echo $request['urgency_level'] ? htmlspecialchars($request['urgency_level']) : 'Medium'; ?>
                                        </span>
                                    </div>
                                    <p class="text-muted small mb-2">
                                        <?php echo substr(htmlspecialchars($request['request_description']), 0, 80); ?>...
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i>
                                                <?php echo htmlspecialchars($request['patient_name']); ?>
                                            </small>
                                            <?php if ($request['location']): ?>
                                                <small class="text-muted ms-3">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    <?php echo htmlspecialchars($request['location']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <a href="respond-request.php?id=<?php echo $request['request_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary btn-lift">
                                            <i class="fas fa-comment-medical me-1"></i> Respond
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Health Tips -->
                <?php if (!empty($recent_tips)): ?>
                    <div class="card mb-4 quick-action-card">
                        <div class="card-header bg-white border-0 py-3">
                            <h4 class="mb-0"><i class="fas fa-lightbulb text-success me-2"></i> My Recent Tips</h4>
                            <p class="text-muted mb-0 mt-1">Health tips you've shared</p>
                        </div>
                        <div class="card-body p-3">
                            <?php foreach ($recent_tips as $tip): ?>
                                <div class="tip-card">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="fw-bold mb-1">
                                                <i class="fas fa-sticky-note text-success me-2"></i>
                                                <?php echo htmlspecialchars($tip['tip_title']); ?>
                                            </h6>
                                            <small class="text-muted">
                                                <i class="fas fa-heart me-1 text-danger"></i> 
                                                <?php echo $tip['total_likes'] ?? 0; ?> likes
                                                <span class="ms-3">
                                                    <i class="fas fa-clock me-1"></i> 
                                                    <?php echo safeDateFormat($tip['tip_date']); ?>
                                                </span>
                                            </small>
                                        </div>
                                        <a href="edit-tip.php?id=<?php echo $tip['tip_id']; ?>" 
                                           class="btn btn-sm btn-outline-success btn-lift">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-3">
                                <a href="create-tip.php" class="btn btn-success btn-sm btn-lift me-2">
                                    <i class="fas fa-plus me-1"></i> Create New Tip
                                </a>
                                <a href="my-tips.php" class="btn btn-outline-success btn-sm btn-lift">
                                    <i class="fas fa-list me-1"></i> View All
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Recent Responses & Quick Actions -->
            <div class="col-lg-6">
                <!-- Recent Responses -->
                <div class="card mb-4 quick-action-card">
                    <div class="card-header bg-white border-0 py-3">
                        <h4 class="mb-0"><i class="fas fa-history text-primary me-2"></i> My Recent Responses</h4>
                        <p class="text-muted mb-0 mt-1">Your recent consultations</p>
                    </div>
                    <div class="card-body p-3">
                        <?php if (empty($my_responses)): ?>
                            <div class="text-center py-4">
                                <div class="mb-3">
                                    <i class="fas fa-comment-slash fa-3x text-muted"></i>
                                </div>
                                <h5>No responses yet</h5>
                                <p class="text-muted mb-0">Start helping patients by responding to requests.</p>
                                <a href="respond-requests.php" class="btn btn-primary mt-3 btn-lift">
                                    <i class="fas fa-hands-helping me-2"></i> Help Patients
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Patient</th>
                                            <th>Request</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($my_responses as $response): ?>
                                            <tr>
                                                <td>
                                                    <small><?php echo htmlspecialchars($response['patient_name']); ?></small>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars(substr($response['request_title'], 0, 20)); ?>...</small>
                                                </td>
                                                <td>
                                                    <small><?php echo safeDateFormat($response['display_date']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $response['request_status'] === 'closed' ? 'success' : 'info'; ?>">
                                                        <?php echo ucfirst($response['request_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="view-request.php?id=<?php echo $response['request_id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary btn-lift">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <a href="my-responses.php" class="btn btn-outline-primary btn-sm btn-lift">
                                    <i class="fas fa-list me-1"></i> View All Responses
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card quick-action-card">
                    <div class="card-header bg-white border-0 py-3">
                        <h4 class="mb-0"><i class="fas fa-bolt text-success me-2"></i> Quick Actions</h4>
                        <p class="text-muted mb-0 mt-1">Frequently used features</p>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <a href="respond-requests.php" class="card quick-action-card text-decoration-none h-100">
                                    <div class="card-body text-center p-3">
                                        <div class="mb-3">
                                            <i class="fas fa-comments-medical fa-2x text-primary"></i>
                                        </div>
                                        <h6 class="fw-bold mb-2">Respond to Requests</h6>
                                        <p class="text-muted small mb-0">Help patients with medical advice</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="create-tip.php" class="card quick-action-card text-decoration-none h-100">
                                    <div class="card-body text-center p-3">
                                        <div class="mb-3">
                                            <i class="fas fa-lightbulb fa-2x text-warning"></i>
                                        </div>
                                        <h6 class="fw-bold mb-2">Create Health Tip</h6>
                                        <p class="text-muted small mb-0">Share medical knowledge</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="patient-directory.php" class="card quick-action-card text-decoration-none h-100">
                                    <div class="card-body text-center p-3">
                                        <div class="mb-3">
                                            <i class="fas fa-users fa-2x text-info"></i>
                                        </div>
                                        <h6 class="fw-bold mb-2">Patient Directory</h6>
                                        <p class="text-muted small mb-0">View your patients</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="reports.php" class="card quick-action-card text-decoration-none h-100">
                                    <div class="card-body text-center p-3">
                                        <div class="mb-3">
                                            <i class="fas fa-chart-line fa-2x text-success"></i>
                                        </div>
                                        <h6 class="fw-bold mb-2">Reports & Analytics</h6>
                                        <p class="text-muted small mb-0">View your impact</p>
                                    </div>
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
                        <i class="fas fa-user-md text-primary me-2"></i>
                        HealthConnect Doctor Dashboard
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
        document.addEventListener('DOMContentLoaded', function() {
            // Animate counters
            const counters = document.querySelectorAll('.counter');
            const speed = 150;
            
            const animateCounter = (counter) => {
                const target = +counter.getAttribute('data-target');
                const count = +counter.innerText.replace(/,/g, '');
                const increment = target / speed;
                
                if (count < target) {
                    counter.innerText = Math.ceil(count + increment).toLocaleString();
                    setTimeout(() => animateCounter(counter), 20);
                } else {
                    counter.innerText = target.toLocaleString();
                }
            };
            
            // Start counters
            counters.forEach(counter => animateCounter(counter));

            // Add click effect to buttons
            document.querySelectorAll('.btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    this.style.transform = 'scale(0.98)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });

            // Simple hover effect for help button
            const helpNowBtn = document.getElementById('helpNowBtn');
            if (helpNowBtn) {
                helpNowBtn.addEventListener('mouseenter', () => {
                    helpNowBtn.style.transform = 'translateY(-3px)';
                });
                
                helpNowBtn.addEventListener('mouseleave', () => {
                    helpNowBtn.style.transform = '';
                });
            }

            // Auto-update pending requests count (simplified)
            function updatePendingCount() {
                // In a real app, you would make an API call here
                // For now, we'll just log to console
                console.log('Updating pending count...');
            }
            
            // Update every 60 seconds
            setInterval(updatePendingCount, 60000);
        });
    </script>
</body>
</html>
