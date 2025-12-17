<?php
// healthconnect/views/auth/patient-dashboard.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header('Location: login.php?error=required');
    exit();
}

require_once '../../app/config/database.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get patient statistics
$sql = "SELECT 
        (SELECT COUNT(*) FROM hc_medical_requests WHERE patient_id = :patient_id) as total_requests,
        (SELECT COUNT(*) FROM hc_medical_requests WHERE patient_id = :patient_id2 AND request_status = 'responded') as responded_requests,
        (SELECT COUNT(*) FROM hc_medical_requests WHERE patient_id = :patient_id3 AND request_status = 'pending') as pending_requests,
        (SELECT COUNT(*) FROM hc_medical_requests WHERE patient_id = :patient_id4 AND request_status = 'closed') as closed_requests";
        
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':patient_id' => $user_id,
    ':patient_id2' => $user_id,
    ':patient_id3' => $user_id,
    ':patient_id4' => $user_id
]);
$stats = $stmt->fetch();

// Get recent medical requests
$sql = "SELECT r.request_id, r.request_title, r.request_description, 
               r.request_date, r.request_status, r.urgency_level,
               d.full_name as doctor_name, r.response_date
        FROM hc_medical_requests r
        LEFT JOIN hc_users d ON r.responded_by_user_id = d.user_id
        WHERE r.patient_id = :patient_id
        ORDER BY r.request_date DESC
        LIMIT 5";
$stmt = $pdo->prepare($sql);
$stmt->execute([':patient_id' => $user_id]);
$recent_requests = $stmt->fetchAll();

// Get recent health tips for patient
$tips_sql = "SELECT t.tip_id, t.tip_title, t.tip_content, 
                    t.category, t.tip_date, t.total_likes,
                    d.full_name as doctor_name
             FROM hc_health_tips t
             JOIN hc_users d ON t.doctor_user_id = d.user_id
             WHERE t.is_published = 1
             ORDER BY t.tip_date DESC
             LIMIT 5";
$tips_stmt = $pdo->prepare($tips_sql);
$tips_stmt->execute();
$recent_tips = $tips_stmt->fetchAll();

// Get today's activity
$today = date('Y-m-d');
$activity_sql = "SELECT 
    (SELECT COUNT(*) FROM hc_medical_requests WHERE patient_id = :patient_id1 AND DATE(request_date) = :today) as today_requests,
    (SELECT COUNT(*) FROM hc_medical_requests WHERE patient_id = :patient_id2 AND DATE(response_date) = :today2 AND request_status = 'responded') as today_responses";
$activity_stmt = $pdo->prepare($activity_sql);
$activity_stmt->execute([
    ':patient_id1' => $user_id,
    ':patient_id2' => $user_id,
    ':today' => $today,
    ':today2' => $today
]);
$activity = $activity_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --patient-primary: #0d6efd;
            --patient-secondary: #052c65;
            --patient-accent: #20c997;
            --patient-danger: #dc3545;
            --patient-warning: #ffc107;
            --patient-light: #e3f2fd;
            --animation-speed: 0.5s;
            --ease-out: cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e3f2fd 100%);
            min-height: 100vh;
            opacity: 0;
            animation: fadeIn 0.8s var(--ease-out) forwards;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .patient-header {
            background: linear-gradient(135deg, var(--patient-primary) 0%, var(--patient-secondary) 100%);
            color: white;
            padding: 80px 0 50px;
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
            animation: gradientShift 8s ease infinite;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .patient-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,192C672,181,768,139,864,138.7C960,139,1056,181,1152,197.3C1248,213,1344,203,1392,197.3L1440,192L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            animation: float 25s ease-in-out infinite;
        }
        
        .stat-card {
            border-radius: 20px;
            border: none;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            transition: all 0.5s var(--ease-out);
            overflow: hidden;
            position: relative;
            animation: slideUp 0.6s var(--ease-out) forwards;
            opacity: 0;
            min-height: 180px;
            background: white;
        }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
        }
        
        .stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            transform-origin: left;
            transform: scaleX(0);
            transition: transform 0.6s var(--ease-out);
        }
        
        .stat-card:hover::after {
            transform: scaleX(1);
        }
        
        .stat-card.total { border-top: 5px solid var(--patient-primary); }
        .stat-card.total::after { background: linear-gradient(90deg, var(--patient-primary), #0dcaf0); }
        
        .stat-card.responded { border-top: 5px solid var(--patient-accent); }
        .stat-card.responded::after { background: linear-gradient(90deg, var(--patient-accent), #198754); }
        
        .stat-card.pending { border-top: 5px solid var(--patient-warning); }
        .stat-card.pending::after { background: linear-gradient(90deg, var(--patient-warning), #fd7e14); }
        
        .stat-card.closed { border-top: 5px solid #6f42c1; }
        .stat-card.closed::after { background: linear-gradient(90deg, #6f42c1, #d63384); }
        
        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin: 0 auto 15px;
            transition: all 0.4s var(--ease-out);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(360deg);
        }
        
        .stat-card.total .stat-icon { 
            background: rgba(13, 110, 253, 0.1); 
            color: var(--patient-primary);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
        }
        .stat-card.responded .stat-icon { 
            background: rgba(32, 201, 151, 0.1); 
            color: var(--patient-accent);
            box-shadow: 0 5px 15px rgba(32, 201, 151, 0.3);
        }
        .stat-card.pending .stat-icon { 
            background: rgba(255, 193, 7, 0.1); 
            color: var(--patient-warning);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
        }
        .stat-card.closed .stat-icon { 
            background: rgba(111, 66, 193, 0.1); 
            color: #6f42c1;
            box-shadow: 0 5px 15px rgba(111, 66, 193, 0.3);
        }
        
        .stat-card h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--patient-secondary);
        }
        
        .request-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
            transition: all 0.4s var(--ease-out);
            border-left: 5px solid var(--patient-primary);
            animation: slideUp 0.5s var(--ease-out) forwards;
            opacity: 0;
            transform: translateX(-10px);
            background: white;
            padding: 20px;
            margin-bottom: 15px;
        }
        
        .request-card:nth-child(1) { animation-delay: 0.2s; }
        .request-card:nth-child(2) { animation-delay: 0.3s; }
        .request-card:nth-child(3) { animation-delay: 0.4s; }
        .request-card:nth-child(4) { animation-delay: 0.5s; }
        .request-card:nth-child(5) { animation-delay: 0.6s; }
        
        .request-card:hover {
            transform: translateY(-8px) translateX(0) !important;
            box-shadow: 0 15px 35px rgba(0,0,0,0.12);
            border-left: 5px solid var(--patient-accent);
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            transition: all 0.3s;
        }
        
        .status-pending { 
            background: linear-gradient(135deg, var(--patient-warning), #fd7e14);
            color: white;
        }
        .status-responded { 
            background: linear-gradient(135deg, var(--patient-accent), #198754);
            color: white;
        }
        .status-closed { 
            background: linear-gradient(135deg, #6f42c1, #d63384);
            color: white;
        }
        
        .urgency-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .urgency-high { background: #dc3545; color: white; }
        .urgency-medium { background: #ffc107; color: #212529; }
        .urgency-low { background: #20c997; color: white; }
        
        .tip-card {
            padding: 15px;
            border-radius: 10px;
            background: rgba(32, 201, 151, 0.05);
            margin-bottom: 10px;
            transition: all 0.3s;
            border-left: 4px solid #20c997;
            border: 1px solid rgba(32, 201, 151, 0.1);
        }
        
        .tip-card:hover {
            background: rgba(32, 201, 151, 0.1);
            transform: translateX(5px);
        }
        
        .welcome-message {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(255,255,255,0.2);
            animation: slideUp 0.8s var(--ease-out) 0.2s forwards;
            opacity: 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .quick-action-card {
            transition: all 0.5s var(--ease-out);
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
            position: relative;
            background: white;
        }
        
        .quick-action-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }
        
        .quick-action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, 
                var(--patient-primary), 
                var(--patient-accent), 
                var(--patient-secondary));
            transform-origin: left;
            transform: scaleX(0);
            transition: transform 0.5s;
        }
        
        .quick-action-card:hover::before {
            transform: scaleX(1);
        }
        
        .profile-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--patient-primary), var(--patient-secondary));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
            transition: all 0.3s var(--ease-out);
            box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
        }
        
        .profile-avatar:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 6px 15px rgba(13, 110, 253, 0.4);
        }
        
        /* Today's Activity */
        .today-activity {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            animation: slideUp 0.6s var(--ease-out) 0.3s forwards;
            opacity: 0;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .activity-item:hover {
            background: rgba(13, 110, 253, 0.05);
            transform: translateX(5px);
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
            transition: all 0.3s;
        }
        
        .activity-item:hover .activity-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        .activity-icon.requests { background: rgba(13, 110, 253, 0.1); color: #0d6efd; }
        .activity-icon.responses { background: rgba(32, 201, 151, 0.1); color: #20c997; }
        
        .activity-count {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--patient-dark);
            display: block;
        }
        
        .activity-label {
            font-size: 0.9rem;
            color: #666;
        }
        
        /* Floating elements */
        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .floating-element {
            position: absolute;
            background: rgba(13, 110, 253, 0.05);
            border-radius: 50%;
            animation: floatElement 20s infinite linear;
        }
        
        @keyframes floatElement {
            0% {
                transform: translate(0, 0) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translate(100px, -100vh) rotate(360deg);
                opacity: 0;
            }
        }
        
        .btn-lift {
            transition: all 0.3s;
        }
        
        .btn-lift:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        
        /* Animated Text */
        .animate-charcter {
            background-image: linear-gradient(
                -225deg,
                #ffffff 0%,
                #a6c1ee 29%,
                #0d6efd 67%,
                #052c65 100%
            );
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: textclip 3s linear infinite;
            display: inline-block;
        }
        
        @keyframes textclip {
            to {
                background-position: 200% center;
            }
        }
        
        @media (max-width: 768px) {
            .patient-header {
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
    <!-- Floating Background Elements -->
    <div class="floating-elements">
        <?php for ($i = 0; $i < 15; $i++): ?>
            <div class="floating-element" 
                 style="width: <?php echo rand(20, 80); ?>px; 
                        height: <?php echo rand(20, 80); ?>px;
                        top: <?php echo rand(0, 100); ?>%;
                        left: <?php echo rand(0, 100); ?>%;
                        animation-delay: <?php echo rand(0, 20); ?>s;
                        animation-duration: <?php echo rand(15, 30); ?>s;"></div>
        <?php endfor; ?>
    </div>
    
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
                        <a class="nav-link" href="new-request.php">
                            <i class="fas fa-plus-circle me-1"></i> New Request
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my-requests.php">
                            <i class="fas fa-history me-1"></i> My Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="resources.php">
                            <i class="fas fa-book-medical me-1"></i> Resources
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="profile-avatar me-2">
                                <i class="fas fa-user"></i>
                            </div>
                            <span><?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="reports.php"><i class="fas fa-file-medical me-2"></i> Medical History</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Dashboard Header -->
    <div class="patient-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="welcome-message">
                        <h1 class="fw-bold mb-3 animate-charcter">
                            Welcome, <?php echo htmlspecialchars($user_name); ?>! 👋
                        </h1>
                        <p class="lead mb-0">Your health and wellbeing are our top priority. Get medical advice from certified doctors.</p>
                        
                        <!-- Today's Activity -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="activity-item">
                                    <div class="activity-icon requests">
                                        <i class="fas fa-comment-medical"></i>
                                    </div>
                                    <div>
                                        <span class="activity-count"><?php echo $activity['today_requests'] ?? 0; ?></span>
                                        <span class="activity-label">Today's Requests</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="activity-item">
                                    <div class="activity-icon responses">
                                        <i class="fas fa-comment-dots"></i>
                                    </div>
                                    <div>
                                        <span class="activity-count"><?php echo $activity['today_responses'] ?? 0; ?></span>
                                        <span class="activity-label">Today's Responses</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-end position-relative">
                    <div class="mb-4">
                        <span class="badge bg-light text-primary p-3" style="font-size: 14px; border-radius: 25px;">
                            <i class="fas fa-shield-alt me-1"></i> Secure & Private
                        </span>
                    </div>
                    <a href="new-request.php" 
                    id="helpNowBtn"
                    class="btn btn-light btn-lg px-4 shadow btn-lift">
                        <i class="fas fa-stethoscope me-2"></i> Get Medical Help
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="container mb-5">
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card total p-4 text-center">
                    <div class="stat-icon">
                        <i class="fas fa-file-medical"></i>
                    </div>
                    <h2 class="fw-bold mb-2"><?php echo $stats['total_requests'] ?? 0; ?></h2>
                    <p class="mb-0 fw-semibold">Total Requests</p>
                    <small class="text-muted">All medical requests</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card responded p-4 text-center">
                    <div class="stat-icon">
                        <i class="fas fa-comment-medical"></i>
                    </div>
                    <h2 class="fw-bold mb-2"><?php echo $stats['responded_requests'] ?? 0; ?></h2>
                    <p class="mb-0 fw-semibold">Responded</p>
                    <small class="text-muted">Doctor responded</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card pending p-4 text-center">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h2 class="fw-bold mb-2"><?php echo $stats['pending_requests'] ?? 0; ?></h2>
                    <p class="mb-0 fw-semibold">Pending</p>
                    <small class="text-muted">Waiting for response</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card closed p-4 text-center">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 class="fw-bold mb-2"><?php echo $stats['closed_requests'] ?? 0; ?></h2>
                    <p class="mb-0 fw-semibold">Closed</p>
                    <small class="text-muted">Successfully resolved</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="row">
            <!-- Left Column: Recent Requests & Health Tips -->
            <div class="col-lg-6">
                <!-- Recent Requests -->
                <div class="card shadow-sm border-0 mb-4 quick-action-card">
                    <div class="card-header bg-white border-0 py-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-0"><i class="fas fa-history text-primary me-2"></i> Recent Requests</h4>
                                <p class="text-muted mb-0 mt-1">Your recent medical consultations</p>
                            </div>
                            <a href="my-requests.php" class="btn btn-primary btn-lift">
                                <i class="fas fa-list me-1"></i> View All
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <?php if (empty($recent_requests)): ?>
                            <div class="text-center py-4">
                                <div class="mb-4">
                                    <i class="fas fa-comment-slash fa-3x text-muted"></i>
                                </div>
                                <h5>No requests yet</h5>
                                <p class="text-muted mb-0">Start by creating your first medical request.</p>
                                <a href="new-request.php" class="btn btn-primary mt-3 btn-lift">
                                    <i class="fas fa-plus-circle me-2"></i> Create Request
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_requests as $index => $request): ?>
                                <div class="request-card" style="animation-delay: <?php echo $index * 0.1; ?>s">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($request['request_title']); ?></h6>
                                        <div>
                                            <span class="urgency-badge urgency-<?php echo strtolower($request['urgency_level'] ?? 'medium'); ?> me-1">
                                                <?php echo $request['urgency_level'] ? htmlspecialchars($request['urgency_level']) : 'Medium'; ?>
                                            </span>
                                            <span class="status-badge status-<?php echo strtolower($request['request_status']); ?>">
                                                <?php echo ucfirst($request['request_status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <p class="text-muted small mb-2">
                                        <?php echo substr(htmlspecialchars($request['request_description']), 0, 80); ?>...
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <?php if ($request['doctor_name']): ?>
                                                <small class="text-muted">
                                                    <i class="fas fa-user-md me-1"></i>
                                                    Dr. <?php echo htmlspecialchars($request['doctor_name']); ?>
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    Waiting for doctor
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <a href="view-request.php?id=<?php echo $request['request_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary btn-lift">
                                            <i class="fas fa-eye me-1"></i> View
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Health Tips -->
                <?php if (!empty($recent_tips)): ?>
                    <div class="card shadow-sm border-0 mb-4 quick-action-card">
                        <div class="card-header bg-white border-0 py-4">
                            <h4 class="mb-0"><i class="fas fa-lightbulb text-success me-2"></i> Health Tips</h4>
                            <p class="text-muted mb-0 mt-1">Latest health advice from doctors</p>
                        </div>
                        <div class="card-body p-4">
                            <?php foreach ($recent_tips as $index => $tip): ?>
                                <div class="tip-card" style="animation-delay: <?php echo $index * 0.1; ?>s">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="fw-bold mb-1">
                                                <i class="fas fa-sticky-note text-success me-2"></i>
                                                <?php echo htmlspecialchars($tip['tip_title']); ?>
                                            </h6>
                                            <small class="text-muted">
                                                <i class="fas fa-user-md me-1 text-primary"></i> 
                                                Dr. <?php echo htmlspecialchars($tip['doctor_name']); ?>
                                                <span class="ms-3">
                                                    <i class="fas fa-clock me-1"></i> 
                                                    <?php echo date('M d', strtotime($tip['tip_date'])); ?>
                                                </span>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-3">
                                <a href="health-tips.php" class="btn btn-outline-success btn-sm btn-lift">
                                    <i class="fas fa-list me-1"></i> View More Tips
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Quick Actions & Resources -->
            <div class="col-lg-6">
                <!-- Quick Actions -->
                <div class="card shadow-sm border-0 mb-4 quick-action-card">
                    <div class="card-header bg-white border-0 py-4">
                        <h4 class="mb-0"><i class="fas fa-bolt text-warning me-2"></i> Quick Actions</h4>
                        <p class="text-muted mb-0 mt-1">Quick access to features</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <a href="new-request.php" class="card quick-action-card text-decoration-none">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-stethoscope fa-3x text-primary"></i>
                                        </div>
                                        <h6 class="fw-bold mb-2">Medical Request</h6>
                                        <p class="text-muted small mb-0">Get medical advice</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="my-requests.php" class="card quick-action-card text-decoration-none">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-history fa-3x text-info"></i>
                                        </div>
                                        <h6 class="fw-bold mb-2">Request History</h6>
                                        <p class="text-muted small mb-0">View past requests</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="resources.php" class="card quick-action-card text-decoration-none">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-book-medical fa-3x text-danger"></i>
                                        </div>
                                        <h6 class="fw-bold mb-2">Resources</h6>
                                        <p class="text-muted small mb-0">Medical references</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="emergency.php" class="card quick-action-card text-decoration-none">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-ambulance fa-3x text-danger"></i>
                                        </div>
                                        <h6 class="fw-bold mb-2">Emergency</h6>
                                        <p class="text-muted small mb-0">Emergency contacts</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Emergency Contact -->
                <div class="card shadow-sm border-0 mb-4 quick-action-card">
                    <div class="card-header bg-white border-0 py-4">
                        <h4 class="mb-0"><i class="fas fa-phone-alt text-danger me-2"></i> Emergency Contacts</h4>
                        <p class="text-muted mb-0 mt-1">Important emergency numbers</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="d-flex align-items-center p-3 border rounded">
                                    <div class="bg-danger text-white rounded-circle p-3 me-3">
                                        <i class="fas fa-ambulance"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-1">Emergency Ambulance</h6>
                                        <p class="text-muted mb-0">Call: 911 or 112</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex align-items-center p-3 border rounded">
                                    <div class="bg-warning text-white rounded-circle p-3 me-3">
                                        <i class="fas fa-hospital"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-1">Nearest Hospital</h6>
                                        <p class="text-muted mb-0">Contact local hospital</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex align-items-center p-3 border rounded">
                                    <div class="bg-info text-white rounded-circle p-3 me-3">
                                        <i class="fas fa-user-md"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-1">Doctor Helpline</h6>
                                        <p class="text-muted mb-0">24/7 medical advice</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Health Status -->
                <div class="card shadow-sm border-0 quick-action-card">
                    <div class="card-header bg-white border-0 py-4">
                        <h4 class="mb-0"><i class="fas fa-heartbeat text-danger me-2"></i> Health Status</h4>
                        <p class="text-muted mb-0 mt-1">Your health overview</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="display-6 fw-bold text-primary"><?php echo $stats['closed_requests'] ?? 0; ?></div>
                                <small class="text-muted">Resolved</small>
                            </div>
                            <div class="col-4">
                                <div class="display-6 fw-bold text-warning"><?php echo $stats['pending_requests'] ?? 0; ?></div>
                                <small class="text-muted">Active</small>
                            </div>
                            <div class="col-4">
                                <div class="display-6 fw-bold text-success"><?php echo $stats['responded_requests'] ?? 0; ?></div>
                                <small class="text-muted">In Progress</small>
                            </div>
                        </div>
                        <div class="progress mt-4" style="height: 10px;">
                            <div class="progress-bar bg-success" style="width: <?php echo ($stats['closed_requests'] ?? 0) > 0 ? (($stats['closed_requests'] / ($stats['total_requests'] ?: 1)) * 100) : 0; ?>%"></div>
                            <div class="progress-bar bg-warning" style="width: <?php echo ($stats['pending_requests'] ?? 0) > 0 ? (($stats['pending_requests'] / ($stats['total_requests'] ?: 1)) * 100) : 0; ?>%"></div>
                        </div>
                        <div class="text-center mt-3">
                            <a href="reports.php" class="btn btn-outline-primary btn-sm btn-lift">
                                <i class="fas fa-chart-line me-1"></i> View Health Report
                            </a>
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
                        &copy; <?php echo date('Y'); ?> HealthConnect. Your health partner.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Counter animation
        document.addEventListener('DOMContentLoaded', function() {
            // Add CSS for animations
            const style = document.createElement('style');
            style.textContent = `
                .counter {
                    animation: countUp 1s ease-out;
                }
                
                @keyframes countUp {
                    from { opacity: 0; transform: translateY(10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
            `;
            document.head.appendChild(style);

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

            // Auto-refresh dashboard every 2 minutes
            setInterval(() => {
                if (!document.hidden) {
                    location.reload();
                }
            }, 120000);
        });
    </script>
</body>
</html>
