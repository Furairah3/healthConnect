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

// Get doctor's recent responses
$sql = "SELECT r.request_id, r.request_title, u.full_name as patient_name,
               r.response_date, r.request_status
        FROM hc_medical_requests r
        JOIN hc_users u ON r.patient_id = u.user_id
        WHERE r.responded_by_user_id = :doctor_id
        ORDER BY r.response_date DESC
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
            --animation-speed: 0.5s;
            --ease-out: cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            opacity: 0;
            animation: fadeIn 0.8s var(--ease-out) forwards;
            background: linear-gradient(135deg, #f8f9fa 0%, #e3f2fd 100%);
            min-height: 100vh;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, var(--doctor-primary) 0%, var(--doctor-secondary) 100%);
            background-size: 200% 200%;
            color: white;
            padding: 80px 0 50px;
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
            animation: gradientShift 8s ease infinite;
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
        }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
            transform: translateX(-100%);
            transition: transform 0.8s;
        }
        
        .stat-card:hover::before {
            transform: translateX(100%);
        }
        
        .stat-card:hover {
            transform: translateY(-15px) scale(1.02);
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
        
        .stat-card.patients { 
            background: linear-gradient(135deg, #0d6efd, #0dcaf0, #6610f2);
            background-size: 200% 200%;
        }
        .stat-card.patients::after { background: linear-gradient(90deg, #0d6efd, #0dcaf0); }
        
        .stat-card.pending { 
            background: linear-gradient(135deg, #ffc107, #fd7e14, #dc3545);
            background-size: 200% 200%;
        }
        .stat-card.pending::after { background: linear-gradient(90deg, #ffc107, #fd7e14); }
        
        .stat-card.tips { 
            background: linear-gradient(135deg, #20c997, #198754, #146c43);
            background-size: 200% 200%;
        }
        .stat-card.tips::after { background: linear-gradient(90deg, #20c997, #198754); }
        
        .stat-card.active { 
            background: linear-gradient(135deg, #6f42c1, #d63384, #fd7e14);
            background-size: 200% 200%;
        }
        .stat-card.active::after { background: linear-gradient(90deg, #6f42c1, #d63384); }
        
        .stat-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 20px;
            transition: all 0.4s var(--ease-out);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(360deg);
        }
        
        .stat-card.patients .stat-icon { 
            background: rgba(255, 255, 255, 0.15);
            color: white;
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
        }
        .stat-card.pending .stat-icon { 
            background: rgba(255, 255, 255, 0.15);
            color: white;
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
        }
        .stat-card.tips .stat-icon { 
            background: rgba(255, 255, 255, 0.15);
            color: white;
            box-shadow: 0 5px 15px rgba(32, 201, 151, 0.3);
        }
        .stat-card.active .stat-icon { 
            background: rgba(255, 255, 255, 0.15);
            color: white;
            box-shadow: 0 5px 15px rgba(111, 66, 193, 0.3);
        }
        
        .stat-card h2 {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
            color: white;
        }
        
        .request-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
            transition: all 0.4s var(--ease-out);
            border-left: 5px solid var(--doctor-primary);
            animation: slideUp 0.5s var(--ease-out) forwards;
            opacity: 0;
            transform: translateX(-10px);
        }
        
        .request-card:nth-child(1) { animation-delay: 0.2s; }
        .request-card:nth-child(2) { animation-delay: 0.3s; }
        .request-card:nth-child(3) { animation-delay: 0.4s; }
        .request-card:nth-child(4) { animation-delay: 0.5s; }
        .request-card:nth-child(5) { animation-delay: 0.6s; }
        
        .request-card:hover {
            transform: translateY(-8px) translateX(0) !important;
            box-shadow: 0 15px 35px rgba(0,0,0,0.12);
            border-left: 5px solid var(--doctor-accent);
        }
        
        .urgency-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            transition: all 0.3s;
        }
        
        .urgency-badge:hover {
            transform: scale(1.1);
        }
        
        .urgency-high { 
            background: linear-gradient(135deg, #dc3545, #fd7e14);
            color: white;
            animation: pulse 2s infinite;
        }
        .urgency-medium { 
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
        }
        .urgency-low { 
            background: linear-gradient(135deg, #20c997, #198754);
            color: white;
        }
        
        .doctor-badge {
            background: linear-gradient(135deg, var(--doctor-primary), var(--doctor-secondary));
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
            display: inline-block;
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
        }
        
        .doctor-badge:hover {
            background: linear-gradient(135deg, var(--doctor-secondary), var(--doctor-primary));
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(13, 110, 253, 0.4);
        }
        
        .action-btn {
            min-width: 120px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .action-btn::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: -100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(255,255,255,0.3), 
                transparent);
            transition: left 0.5s;
        }
        
        .action-btn:hover::after {
            left: 100%;
        }
        
        .profile-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--doctor-primary), var(--doctor-secondary));
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
                var(--doctor-primary), 
                var(--doctor-accent), 
                var(--doctor-secondary));
            transform-origin: left;
            transform: scaleX(0);
            transition: transform 0.5s;
        }
        
        .quick-action-card:hover::before {
            transform: scaleX(1);
        }
        
        .tip-card {
            padding: 15px;
            border-radius: 10px;
            background: rgba(32, 201, 151, 0.05);
            margin-bottom: 10px;
            transition: all 0.3s;
            border-left: 4px solid #20c997;
        }
        
        .tip-card:hover {
            background: rgba(32, 201, 151, 0.1);
            transform: translateX(5px);
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
        
        .activity-icon.responses { background: rgba(13, 110, 253, 0.1); color: #0d6efd; }
        .activity-icon.tips { background: rgba(32, 201, 151, 0.1); color: #20c997; }
        
        .activity-count {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--doctor-dark);
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
        
        /* Button animations */
        .btn-lift {
            transition: all 0.3s;
        }
        
        .btn-lift:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        
        /* Ripple effect */
        .ripple {
            position: relative;
            overflow: hidden;
        }
        
        .ripple::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }
        
        .ripple:focus:not(:active)::after {
            animation: rippleEffect 1s ease-out;
        }
        
        @keyframes rippleEffect {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            100% {
                transform: scale(20, 20);
                opacity: 0;
            }
        }
        
        /* Status badges */
        .status-badge {
            transition: all 0.3s;
        }
        
        .status-badge:hover {
            transform: scale(1.1);
        }
        
        /* Shine effect for cards */
        .shine-effect {
            position: relative;
            overflow: hidden;
        }
        
        .shine-effect::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                to right,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.3) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            transform: rotate(30deg);
            transition: transform 0.8s;
        }
        
        .shine-effect:hover::after {
            transform: rotate(30deg) translate(10%, 10%);
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
                <i class="fas fa-heartbeat me-2 animate__animated animate__pulse animate__infinite" 
                   style="animation-duration: 3s"></i>HealthConnect
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
                                <span class="badge bg-danger ms-1 animate__animated animate__pulse animate__infinite" 
                                      style="animation-duration: 2s">
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
                    class="btn btn-light btn-lg px-4 shadow btn-lift ripple">
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
                <div class="stat-card patients p-4 text-center shine-effect">
                    <div class="stat-icon">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <h2 class="fw-bold mb-2 counter" data-target="<?php echo $stats['total_helped'] ?? 0; ?>">0</h2>
                    <p class="mb-0 fw-semibold" style="color: rgba(255,255,255,0.9);">Patients Helped</p>
                    <small style="color: rgba(255,255,255,0.8);">Lifetime assistance</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card pending p-4 text-center shine-effect">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h2 class="fw-bold mb-2 counter" data-target="<?php echo $stats['pending_requests'] ?? 0; ?>">0</h2>
                    <p class="mb-0 fw-semibold" style="color: rgba(255,255,255,0.9);">Pending Requests</p>
                    <small style="color: rgba(255,255,255,0.8);">Need your help</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card tips p-4 text-center shine-effect">
                    <div class="stat-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h2 class="fw-bold mb-2 counter" data-target="<?php echo $stats['total_tips'] ?? 0; ?>">0</h2>
                    <p class="mb-0 fw-semibold" style="color: rgba(255,255,255,0.9);">Health Tips</p>
                    <small style="color: rgba(255,255,255,0.8);">Shared with community</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card active p-4 text-center shine-effect">
                    <div class="stat-icon">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                    <h2 class="fw-bold mb-2 counter" data-target="<?php echo $stats['active_cases'] ?? 0; ?>">0</h2>
                    <p class="mb-0 fw-semibold" style="color: rgba(255,255,255,0.9);">Active Cases</p>
                    <small style="color: rgba(255,255,255,0.8);">Currently assisting</small>
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
                <div class="card shadow-sm border-0 mb-4 quick-action-card">
                    <div class="card-header bg-white border-0 py-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-0"><i class="fas fa-hourglass-half text-warning me-2"></i> Pending Requests</h4>
                                <p class="text-muted mb-0 mt-1">Patients waiting for assistance</p>
                            </div>
                            <a href="respond-requests.php" class="btn btn-warning btn-lift">
                                <i class="fas fa-hands-helping me-1"></i> View All
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <?php if (empty($pending_requests)): ?>
                            <div class="text-center py-4">
                                <div class="mb-4">
                                    <i class="fas fa-check-circle fa-3x text-success animate__animated animate__pulse animate__infinite" 
                                       style="animation-duration: 2s"></i>
                                </div>
                                <h5>No pending requests!</h5>
                                <p class="text-muted mb-0">All current requests have been responded to.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($pending_requests as $index => $request): ?>
                                <div class="request-card mb-3 p-3 shine-effect" style="animation-delay: <?php echo $index * 0.1; ?>s">
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
                                        <!-- FIXED BUTTON - Now uses respond-request.php instead of view-request.php -->
                                        <a href="respond-request.php?id=<?php echo $request['request_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary action-btn btn-lift">
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
                    <div class="card shadow-sm border-0 mb-4 quick-action-card">
                        <div class="card-header bg-white border-0 py-4">
                            <h4 class="mb-0"><i class="fas fa-lightbulb text-success me-2"></i> My Recent Tips</h4>
                            <p class="text-muted mb-0 mt-1">Health tips you've shared</p>
                        </div>
                        <div class="card-body p-4">
                            <?php foreach ($recent_tips as $index => $tip): ?>
                                <div class="tip-card page-transition" style="animation-delay: <?php echo $index * 0.1; ?>s">
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
                                                    <?php echo date('M d', strtotime($tip['tip_date'])); ?>
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
                            <div class="text-center mt-4">
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
                <div class="card shadow-sm border-0 mb-4 quick-action-card">
                    <div class="card-header bg-white border-0 py-4">
                        <h4 class="mb-0"><i class="fas fa-history text-primary me-2"></i> My Recent Responses</h4>
                        <p class="text-muted mb-0 mt-1">Your recent consultations</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if (empty($my_responses)): ?>
                            <div class="text-center py-4">
                                <div class="mb-4">
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
                                        <?php foreach ($my_responses as $index => $response): ?>
                                            <tr class="page-transition" style="animation-delay: <?php echo $index * 0.1; ?>s; transition: all 0.3s;">
                                                <td>
                                                    <small><?php echo htmlspecialchars($response['patient_name']); ?></small>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars(substr($response['request_title'], 0, 20)); ?>...</small>
                                                </td>
                                                <td>
                                                    <small><?php echo date('M d', strtotime($response['response_date'])); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $response['request_status'] === 'closed' ? 'success' : 'info'; ?> status-badge">
                                                        <?php echo ucfirst($response['request_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="view-request.php?id=<?php echo $response['request_id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
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
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0 py-4">
                        <h4 class="mb-0"><i class="fas fa-bolt text-success me-2"></i> Quick Actions</h4>
                        <p class="text-muted mb-0 mt-1">Frequently used features</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <a href="respond-requests.php" class="card quick-action-card text-decoration-none">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-comments-medical fa-3x text-primary"></i>
                                        </div>
                                        <h6 class="fw-bold mb-2">Respond to Requests</h6>
                                        <p class="text-muted small mb-0">Help patients with medical advice</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="create-tip.php" class="card quick-action-card text-decoration-none">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-lightbulb fa-3x text-warning"></i>
                                        </div>
                                        <h6 class="fw-bold mb-2">Create Health Tip</h6>
                                        <p class="text-muted small mb-0">Share medical knowledge</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="patient-directory.php" class="card quick-action-card text-decoration-none">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-users fa-3x text-info"></i>
                                        </div>
                                        <h6 class="fw-bold mb-2">Patient Directory</h6>
                                        <p class="text-muted small mb-0">View your patients</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="reports.php" class="card quick-action-card text-decoration-none">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-chart-line fa-3x text-success"></i>
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
                        <i class="fas fa-user-md text-primary me-2 animate__animated animate__pulse animate__infinite" 
                           style="animation-duration: 3s"></i>
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
        // Enhanced animations on page load
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

            // Add ripple effect to buttons
            document.querySelectorAll('.btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    const x = e.clientX - e.target.getBoundingClientRect().left;
                    const y = e.clientY - e.target.getBoundingClientRect().top;
                    
                    const ripple = document.createElement('span');
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    ripple.classList.add('ripple-effect');
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });

            // Add CSS for ripple effect and animations
            const style = document.createElement('style');
            style.textContent = `
                .ripple-effect {
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.6);
                    transform: scale(0);
                    animation: ripple-animation 0.6s linear;
                    pointer-events: none;
                }
                
                @keyframes ripple-animation {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
                
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
                
                .page-transition {
                    animation: pageFade 0.5s ease-out forwards;
                    opacity: 0;
                }
                
                @keyframes pageFade {
                    from { opacity: 0; transform: translateY(20px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                
                .stat-card, .quick-action-card {
                    will-change: transform;
                }
                
                /* Hover effects for table rows */
                table tbody tr {
                    transition: all 0.3s;
                }
                
                table tbody tr:hover {
                    background: rgba(13, 110, 253, 0.05);
                    transform: translateX(5px);
                }
            `;
            document.head.appendChild(style);

            // Animate help button
            const helpNowBtn = document.getElementById('helpNowBtn');
            if (helpNowBtn) {
                helpNowBtn.addEventListener('mouseenter', () => {
                    helpNowBtn.style.transform = 'translateY(-5px) scale(1.05)';
                    helpNowBtn.style.boxShadow = '0 15px 30px rgba(0,0,0,0.2)';
                });
                
                helpNowBtn.addEventListener('mouseleave', () => {
                    helpNowBtn.style.transform = 'translateY(-3px) scale(1)';
                    helpNowBtn.style.boxShadow = '0 10px 20px rgba(0,0,0,0.15)';
                });
                
                // Pulsing animation
                setInterval(() => {
                    helpNowBtn.classList.toggle('animate__pulse');
                }, 5000);
            }

            // Auto-update pending requests count
            function updatePendingCount() {
                const pendingElement = document.querySelector('.stat-card.pending .counter');
                if (pendingElement) {
                    fetch('../../api/doctor.php?action=get_pending_count')
                        .then(response => response.json())
                        .then(data => {
                            if (data.count > 0) {
                                // Update counter
                                pendingElement.setAttribute('data-target', data.count);
                                animateCounter(pendingElement);
                                
                                // Update badge in nav
                                const navBadge = document.querySelector('.nav-link[href="respond-requests.php"] .badge');
                                if (navBadge) {
                                    navBadge.textContent = data.count;
                                }
                            }
                        })
                        .catch(error => console.error('Update error:', error));
                }
            }
            
            // Update every 60 seconds
            setInterval(updatePendingCount, 60000);
        });
    </script>
</body>
</html>
