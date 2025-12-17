<?php
// healthconnect/views/auth/admin-dashboard.php
session_start();
require_once '../../app/config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php?error=required');
    exit();
}

$user_name = $_SESSION['user_name'];

// Get quick stats
$stats_sql = "SELECT 
    (SELECT COUNT(*) FROM hc_users WHERE user_role = 'patient') as patients,
    (SELECT COUNT(*) FROM hc_users WHERE user_role = 'volunteer') as volunteers,
    (SELECT COUNT(*) FROM hc_users WHERE user_role = 'doctor' AND is_approved = 1) as doctors,
    (SELECT COUNT(*) FROM hc_medical_requests) as total_requests,
    (SELECT COUNT(*) FROM hc_doctor_verifications WHERE verification_status = 'pending_review') as pending_doctors,
    (SELECT COUNT(*) FROM hc_medical_requests WHERE request_status = 'pending') as pending_requests,
    (SELECT COUNT(*) FROM hc_forum_posts) as forum_posts,
    (SELECT COUNT(*) FROM hc_health_tips) as total_tips";

$stmt = $pdo->query($stats_sql);
$stats = $stmt->fetch();

// Get recent doctor applications
$doctors_sql = "SELECT u.user_id, u.full_name, u.email_address, u.date_created, 
                dv.document_filename
                FROM hc_users u
                LEFT JOIN hc_doctor_verifications dv ON u.user_id = dv.doctor_user_id
                WHERE u.user_role = 'doctor' AND u.is_approved = 0
                ORDER BY u.date_created DESC
                LIMIT 5";
$doctors_stmt = $pdo->query($doctors_sql);
$pending_doctors = $doctors_stmt->fetchAll();

// Get recent health tips
$tips_sql = "SELECT tip_id, tip_title, tip_date FROM hc_health_tips 
             ORDER BY tip_date DESC LIMIT 5";
$tips_stmt = $pdo->query($tips_sql);
$recent_tips = $tips_stmt->fetchAll();

// Get recent forum posts
$forum_sql = "SELECT fp.post_id, fp.title, u.full_name, fp.created_at 
              FROM hc_forum_posts fp 
              JOIN hc_users u ON fp.author_id = u.user_id 
              ORDER BY fp.created_at DESC LIMIT 5";
$forum_stmt = $pdo->query($forum_sql);
$recent_posts = $forum_stmt->fetchAll();

// Get system activity - FIXED: Removed reference to non-existent table
$activity_sql = "SELECT 
    (SELECT COUNT(*) FROM hc_users WHERE DATE(date_created) = CURDATE()) as today_users,
    (SELECT COUNT(*) FROM hc_medical_requests WHERE DATE(request_date) = CURDATE()) as today_requests,
    (SELECT COUNT(*) FROM hc_medical_requests WHERE responded_by_user_id IS NOT NULL AND DATE(request_date) = CURDATE()) as today_responses,
    (SELECT COUNT(*) FROM hc_forum_posts WHERE DATE(created_at) = CURDATE()) as today_forum_posts";

$activity_stmt = $pdo->query($activity_sql);
$activity = $activity_stmt->fetch();

// Get request statistics for reports
// Simple version without CASE statements
$reports_sql = "SELECT 
    COUNT(*) as total,
    (SELECT COUNT(*) FROM hc_medical_requests WHERE urgency_level = 'high') as high_priority_count,
    (SELECT COUNT(*) FROM hc_medical_requests WHERE request_status = 'pending') as pending_count,
    (SELECT COUNT(*) FROM hc_medical_requests WHERE request_status = 'responded') as responded_count,
    (SELECT COUNT(*) FROM hc_medical_requests WHERE request_status = 'closed') as closed_count,
    COUNT(DISTINCT patient_id) as unique_patients
    FROM hc_medical_requests";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --admin-primary: #6f42c1;
            --admin-secondary: #4a1fb8;
            --admin-accent: #a370f7;
            --admin-dark: #2e1065;
            --admin-light: #f0e7ff;
            --community-color: #20c997;
            --reports-color: #fd7e14;
            --animation-speed: 0.5s;
            --ease-out: cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            opacity: 0;
            animation: fadeIn 0.8s var(--ease-out) forwards;
            background: linear-gradient(135deg, #f8f9fa 0%, #f0f4ff 100%);
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
        
        @keyframes slideRight {
            from { transform: translateX(-30px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
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
        
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .admin-nav {
            background: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-secondary) 100%);
            background-size: 200% 200%;
            color: white;
            padding: 20px 0;
            box-shadow: 0 4px 20px rgba(111, 66, 193, 0.3);
            position: relative;
            overflow: hidden;
            animation: gradientShift 8s ease infinite;
        }
        
        .admin-nav::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,160L48,165.3C96,171,192,181,288,181.3C384,181,480,171,576,165.3C672,160,768,160,864,170.7C960,181,1056,203,1152,202.7C1248,203,1344,181,1392,170.7L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            animation: float 25s ease-in-out infinite;
        }
        
        .admin-nav::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 200%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent 0%, 
                rgba(255,255,255,0.15) 50%, 
                transparent 100%);
            animation: shimmer 4s infinite linear;
        }
        
        .stat-card {
            border-radius: 20px;
            padding: 25px;
            color: white;
            margin-bottom: 25px;
            transition: all 0.5s var(--ease-out);
            position: relative;
            overflow: hidden;
            animation: slideUp 0.6s var(--ease-out) forwards;
            opacity: 0;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            min-height: 180px;
            cursor: pointer;
        }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        .stat-card:nth-child(5) { animation-delay: 0.5s; }
        .stat-card:nth-child(6) { animation-delay: 0.6s; }
        
        .stat-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
        }
        
        .stat-card .counter {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
            display: inline-block;
        }
        
        .stat-card.patients { 
            background: linear-gradient(135deg, #0dcaf0, #0d6efd, #6610f2);
            background-size: 200% 200%;
            animation: gradientShift 5s ease infinite;
        }
        .stat-card.volunteers { 
            background: linear-gradient(135deg, #20c997, #198754, #146c43);
            background-size: 200% 200%;
            animation: gradientShift 5s ease infinite;
        }
        .stat-card.doctors { 
            background: linear-gradient(135deg, #6f42c1, #d63384, #fd7e14);
            background-size: 200% 200%;
            animation: gradientShift 5s ease infinite;
        }
        .stat-card.requests { 
            background: linear-gradient(135deg, #ffc107, #fd7e14, #dc3545);
            background-size: 200% 200%;
            animation: gradientShift 5s ease infinite;
        }
        .stat-card.forum { 
            background: linear-gradient(135deg, var(--community-color), #198754, #0dcaf0);
            background-size: 200% 200%;
            animation: gradientShift 5s ease infinite;
        }
        .stat-card.tips { 
            background: linear-gradient(135deg, #6610f2, #6f42c1, #a370f7);
            background-size: 200% 200%;
            animation: gradientShift 5s ease infinite;
        }
        
        .stat-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 2.5rem;
            opacity: 0.3;
            animation: float 4s ease-in-out infinite;
        }
        
        .admin-badge {
            background: rgba(255,255,255,0.2);
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
            display: inline-block;
        }
        
        .admin-badge:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.05);
        }
        
        .action-btn {
            min-width: 100px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .quick-action-card {
            transition: all 0.5s var(--ease-out);
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
            animation: slideRight 0.6s var(--ease-out) forwards;
            opacity: 0;
            position: relative;
        }
        
        .quick-action-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }
        
        .card-header {
            border-radius: 0 !important;
            border: none;
            padding: 25px 30px;
            position: relative;
            overflow: hidden;
        }
        
        .pending-badge {
            background: #ff4757;
            color: white;
            border-radius: 50px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
            animation: pulse 2s infinite;
            box-shadow: 0 4px 10px rgba(255, 71, 87, 0.4);
            position: relative;
            display: inline-block;
        }
        
        .list-group-item {
            border: none;
            padding: 15px 20px;
            transition: all 0.3s;
            border-radius: 10px !important;
            margin-bottom: 5px;
            position: relative;
            overflow: hidden;
        }
        
        .list-group-item:hover {
            background: linear-gradient(90deg, rgba(111, 66, 193, 0.1), rgba(255,255,255,0.1));
            transform: translateX(5px);
            padding-left: 25px;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-accent));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-right: 10px;
            box-shadow: 0 4px 10px rgba(111, 66, 193, 0.3);
            transition: all 0.3s;
        }
        
        .list-group-item:hover .user-avatar {
            transform: scale(1.1) rotate(10deg);
        }
        
        .today-activity {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-top: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            animation: slideUp 0.6s var(--ease-out) 0.5s forwards;
            opacity: 0;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        
        .activity-item:hover {
            background: rgba(111, 66, 193, 0.05);
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
        
        .activity-icon.users { background: rgba(13, 202, 240, 0.1); color: #0dcaf0; }
        .activity-icon.requests { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .activity-icon.responses { background: rgba(32, 201, 151, 0.1); color: #20c997; }
        .activity-icon.forum { background: rgba(32, 201, 151, 0.1); color: var(--community-color); }
        
        .activity-count {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--admin-dark);
            display: block;
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
        
        .forum-card {
            padding: 15px;
            border-radius: 10px;
            background: rgba(32, 201, 151, 0.05);
            margin-bottom: 10px;
            transition: all 0.3s;
            border-left: 4px solid var(--community-color);
        }
        
        .forum-card:hover {
            background: rgba(32, 201, 151, 0.1);
            transform: translateX(5px);
        }
        
        .report-card {
            padding: 15px;
            border-radius: 10px;
            background: rgba(253, 126, 20, 0.05);
            margin-bottom: 10px;
            transition: all 0.3s;
            border-left: 4px solid var(--reports-color);
        }
        
        .report-card:hover {
            background: rgba(253, 126, 20, 0.1);
            transform: translateX(5px);
        }
        
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
            background: rgba(111, 66, 193, 0.05);
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
        
        .notification-dot {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 8px;
            height: 8px;
            background: #ff4757;
            border-radius: 50%;
            animation: pulseDot 2s infinite;
        }
        
        @keyframes pulseDot {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.3); }
        }
        
        .metric-badge {
            font-size: 0.75rem;
            padding: 3px 8px;
            border-radius: 20px;
            margin-left: 5px;
        }
        
        .high-priority { background: #dc3545; color: white; }
        .medium-priority { background: #ffc107; color: black; }
        .low-priority { background: #20c997; color: white; }
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
    
    <!-- Admin Navigation -->
    <nav class="admin-nav">
        <div class="container position-relative">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-heartbeat fa-2x animate__animated animate__pulse animate__infinite" 
                           style="animation-duration: 3s; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));"></i>
                    </div>
                    <div>
                        <h3 class="mb-1 fw-bold animate-charcter">
                            HealthConnect Admin Dashboard
                        </h3>
                        <small class="opacity-90">
                            <i class="fas fa-user me-1"></i> 
                            Welcome, <?php echo htmlspecialchars($user_name); ?>
                            | <i class="fas fa-calendar me-1"></i> <?php echo date('F j, Y'); ?>
                        </small>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <div class="me-4 position-relative">
                        <span class="admin-badge me-2 btn-lift">
                            <i class="fas fa-user-shield me-2"></i> System Administrator
                        </span>
                        <?php if (($stats['pending_doctors'] ?? 0) > 0 || ($stats['pending_requests'] ?? 0) > 0): ?>
                            <span class="notification-dot"></span>
                        <?php endif; ?>
                    </div>
                    <a href="logout.php" class="btn btn-light btn-sm btn-lift">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Quick Stats -->
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-2">
                <div class="stat-card patients" onclick="window.location.href='admin-users.php?filter=patients'">
                    <div class="stat-icon">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <div class="counter" data-target="<?php echo $stats['patients']; ?>">0</div>
                    <p class="mb-0 fw-semibold">Patients</p>
                    <small>Registered users</small>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card volunteers" onclick="window.location.href='admin-users.php?filter=volunteers'">
                    <div class="stat-icon">
                        <i class="fas fa-hands-helping"></i>
                    </div>
                    <div class="counter" data-target="<?php echo $stats['volunteers']; ?>">0</div>
                    <p class="mb-0 fw-semibold">Volunteers</p>
                    <small>Active helpers</small>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card doctors" onclick="window.location.href='admin-users.php?filter=doctors'">
                    <div class="stat-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="counter" data-target="<?php echo $stats['doctors']; ?>">0</div>
                    <p class="mb-0 fw-semibold">Doctors</p>
                    <small>Verified</small>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card requests" onclick="window.location.href='admin-requests.php'">
                    <div class="stat-icon">
                        <i class="fas fa-file-medical"></i>
                    </div>
                    <div class="counter" data-target="<?php echo $stats['total_requests']; ?>">0</div>
                    <p class="mb-0 fw-semibold">Requests</p>
                    <small>Total submissions</small>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card forum" onclick="window.location.href='community.php'">
                    <div class="stat-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="counter" data-target="<?php echo $stats['forum_posts']; ?>">0</div>
                    <p class="mb-0 fw-semibold">Forum Posts</p>
                    <small>Community discussions</small>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card tips" onclick="window.location.href='admin-tips.php'">
                    <div class="stat-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <div class="counter" data-target="<?php echo $stats['total_tips']; ?>">0</div>
                    <p class="mb-0 fw-semibold">Health Tips</p>
                    <small>Educational content</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mt-4">
        <div class="row">
            <!-- Left Column: Quick Actions & Activity -->
            <div class="col-md-4">
                <div class="card shadow-sm mb-4 quick-action-card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt me-2 animate__animated animate__pulse" style="animation-duration: 2s"></i> 
                            Quick Actions
                        </h5>
                        <i class="fas fa-cogs fa-lg opacity-50"></i>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="admin-doctors.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar">
                                        <i class="fas fa-user-md"></i>
                                    </div>
                                    <div>
                                        <span class="fw-semibold">Approve Doctors</span>
                                        <br>
                                        <small class="text-muted">Review applications</small>
                                    </div>
                                </div>
                                <?php if ($stats['pending_doctors'] > 0): ?>
                                    <span class="pending-badge"><?php echo $stats['pending_doctors']; ?> pending</span>
                                <?php endif; ?>
                            </a>
                            <a href="admin-users.php" class="list-group-item list-group-item-action d-flex align-items-center">
                                <div class="user-avatar">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div>
                                    <span class="fw-semibold">Manage Users</span>
                                    <br>
                                    <small class="text-muted">All system users</small>
                                </div>
                            </a>
                            <a href="admin-requests.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar">
                                        <i class="fas fa-file-medical"></i>
                                    </div>
                                    <div>
                                        <span class="fw-semibold">View Requests</span>
                                        <br>
                                        <small class="text-muted">Medical consultations</small>
                                    </div>
                                </div>
                                <?php if ($stats['pending_requests'] > 0): ?>
                                    <span class="pending-badge"><?php echo $stats['pending_requests']; ?> pending</span>
                                <?php endif; ?>
                            </a>
                            <a href="community.php" class="list-group-item list-group-item-action d-flex align-items-center">
                                <div class="user-avatar" style="background: linear-gradient(135deg, var(--community-color), #198754);">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <div>
                                    <span class="fw-semibold">Community</span>
                                    <br>
                                    <small class="text-muted">Forum & Discussions</small>
                                </div>
                            </a>
                            <a href="report.php" class="list-group-item list-group-item-action d-flex align-items-center">
                                <div class="user-avatar" style="background: linear-gradient(135deg, var(--reports-color), #dc3545);">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <div>
                                    <span class="fw-semibold">Reports</span>
                                    <br>
                                    <small class="text-muted">Analytics & Statistics</small>
                                </div>
                            </a>
                            <a href="admin-tips.php" class="list-group-item list-group-item-action d-flex align-items-center">
                                <div class="user-avatar" style="background: linear-gradient(135deg, #20c997, #198754);">
                                    <i class="fas fa-lightbulb"></i>
                                </div>
                                <div>
                                    <span class="fw-semibold">Health Tips</span>
                                    <br>
                                    <small class="text-muted">Manage content</small>
                                </div>
                            </a>
                            <a href="admin-resources.php" class="list-group-item list-group-item-action d-flex align-items-center">
                                <div class="user-avatar" style="background: linear-gradient(135deg, #6f42c1, #4a1fb8);">
                                    <i class="fas fa-book-medical"></i>
                                </div>
                                <div>
                                    <span class="fw-semibold">Training Resources</span>
                                    <br>
                                    <small class="text-muted">Upload materials</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Today's Activity -->
                <div class="today-activity">
                    <h6 class="fw-bold mb-4 text-primary">
                        <i class="fas fa-chart-line me-2"></i> Today's Activity
                    </h6>
                    <div class="activity-item">
                        <div class="activity-icon users">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div>
                            <span class="activity-count"><?php echo $activity['today_users'] ?? 0; ?></span>
                            <span class="activity-label">New Users</span>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon requests">
                            <i class="fas fa-file-medical-alt"></i>
                        </div>
                        <div>
                            <span class="activity-count"><?php echo $activity['today_requests'] ?? 0; ?></span>
                            <span class="activity-label">New Requests</span>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon responses">
                            <i class="fas fa-comment-medical"></i>
                        </div>
                        <div>
                            <span class="activity-count"><?php echo $activity['today_responses'] ?? 0; ?></span>
                            <span class="activity-label">Responses</span>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon forum">
                            <i class="fas fa-comment"></i>
                        </div>
                        <div>
                            <span class="activity-count"><?php echo $activity['today_forum_posts'] ?? 0; ?></span>
                            <span class="activity-label">Forum Posts</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Middle Column: Pending Doctors & Forum -->
            <div class="col-md-4">
                <!-- Pending Doctors -->
                <div class="card shadow-sm mb-4 quick-action-card">
                    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-user-clock me-2"></i> 
                            Doctor Applications
                        </h5>
                        <?php if (!empty($pending_doctors)): ?>
                            <span class="pending-badge"><?php echo count($pending_doctors); ?> pending</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pending_doctors)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h6 class="fw-bold mb-2">All Caught Up!</h6>
                                <p class="text-muted mb-0">No pending doctor applications.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($pending_doctors as $index => $doctor): ?>
                                <div class="border-bottom pb-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="d-flex align-items-start">
                                            <div class="user-avatar me-3">
                                                <?php echo strtoupper(substr($doctor['full_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold mb-1">Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></h6>
                                                <small class="text-muted d-block">
                                                    <i class="fas fa-envelope"></i> 
                                                    <?php echo htmlspecialchars($doctor['email_address']); ?>
                                                </small>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar"></i> 
                                                    Applied: <?php echo date('M d, Y', strtotime($doctor['date_created'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                        <a href="admin-doctors.php?highlight=<?php echo $doctor['user_id']; ?>" 
                                           class="btn btn-sm btn-warning action-btn btn-lift">
                                            <i class="fas fa-eye me-1"></i> Review
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-3">
                                <a href="admin-doctors.php" class="btn btn-warning btn-sm btn-lift">
                                    <i class="fas fa-list me-1"></i> View All Applications
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Community Forum -->
                <div class="card shadow-sm mb-4 quick-action-card">
                    <div class="card-header text-white d-flex justify-content-between align-items-center" style="background: var(--community-color);">
                        <h5 class="mb-0">
                            <i class="fas fa-comments me-2"></i> 
                            Community Forum
                        </h5>
                        <i class="fas fa-users fa-lg opacity-50"></i>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_posts)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-comment-slash fa-3x text-muted mb-3"></i>
                                <h6 class="fw-bold mb-2">No Forum Posts Yet</h6>
                                <p class="text-muted mb-3">Community discussions will appear here.</p>
                                <a href="community.php" class="btn btn-outline-success btn-sm btn-lift">
                                    <i class="fas fa-comments me-1"></i> Go to Community
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_posts as $index => $post): ?>
                                <div class="forum-card">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div style="flex: 1;">
                                            <h6 class="fw-bold mb-1">
                                                <i class="fas fa-comment text-success me-2"></i>
                                                <?php echo htmlspecialchars($post['title']); ?>
                                            </h6>
                                            <small class="text-muted d-block">
                                                <i class="fas fa-user me-1"></i> 
                                                <?php echo htmlspecialchars($post['full_name']); ?>
                                            </small>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i> 
                                                <?php echo date('M d, Y H:i', strtotime($post['created_at'])); ?>
                                            </small>
                                        </div>
                                        <a href="community.php?post=<?php echo $post['post_id']; ?>" 
                                           class="btn btn-sm btn-outline-success btn-lift">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-3">
                                <a href="community.php" class="btn btn-success btn-sm btn-lift me-2">
                                    <i class="fas fa-comments me-1"></i> View Forum
                                </a>
                                <a href="community.php?action=moderate" class="btn btn-outline-success btn-sm btn-lift">
                                    <i class="fas fa-shield-alt me-1"></i> Moderate
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Health Tips & Reports -->
            <div class="col-md-4">
                <!-- Health Tips -->
                <div class="card shadow-sm mb-4 quick-action-card">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-lightbulb me-2"></i> 
                            Recent Health Tips
                        </h5>
                        <i class="fas fa-heartbeat fa-lg opacity-50"></i>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_tips)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-sticky-note fa-3x text-muted mb-3"></i>
                                <h6 class="fw-bold mb-2">No Health Tips Yet</h6>
                                <p class="text-muted mb-3">Start creating educational content.</p>
                                <a href="admin-tips.php?action=create" class="btn btn-success btn-sm btn-lift">
                                    <i class="fas fa-plus me-1"></i> Create First Tip
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_tips as $index => $tip): ?>
                                <div class="tip-card">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="fw-bold mb-1">
                                                <i class="fas fa-sticky-note text-success me-2"></i>
                                                <?php echo htmlspecialchars($tip['tip_title']); ?>
                                            </h6>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i> 
                                                Published: <?php echo date('M d, Y', strtotime($tip['tip_date'])); ?>
                                            </small>
                                        </div>
                                        <a href="admin-tips.php?edit=<?php echo $tip['tip_id']; ?>" 
                                           class="btn btn-sm btn-outline-success btn-lift">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-3">
                                <a href="admin-tips.php" class="btn btn-success btn-sm btn-lift me-2">
                                    <i class="fas fa-cogs me-1"></i> Manage Tips
                                </a>
                                <a href="admin-tips.php?action=create" class="btn btn-outline-success btn-sm btn-lift">
                                    <i class="fas fa-plus me-1"></i> Create New
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Reports Summary -->
                <div class="card shadow-sm mb-4 quick-action-card">
                    <div class="card-header text-white d-flex justify-content-between align-items-center" style="background: var(--reports-color);">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-bar me-2"></i> 
                            Reports Summary
                        </h5>
                        <i class="fas fa-chart-pie fa-lg opacity-50"></i>
                    </div>
                    <div class="card-body">
                        <div class="report-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="fw-bold mb-1">Total Requests</h6>
                                    <span class="activity-count"><?php echo $report_stats['total'] ?? 0; ?></span>
                                </div>
                                <span class="metric-badge high-priority"><?php echo $report_stats['high_priority'] ?? 0; ?> High</span>
                            </div>
                        </div>
                        
                        <div class="report-card">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold mb-0">Request Status</h6>
                            </div>
                            <div class="progress mb-1" style="height: 20px;">
                                <?php 
                                $total = $report_stats['total'] ?? 1;
                                $pending_pct = ($report_stats['pending'] ?? 0) / $total * 100;
                                $responded_pct = ($report_stats['responded'] ?? 0) / $total * 100;
                                $closed_pct = ($report_stats['closed'] ?? 0) / $total * 100;
                                ?>
                                <div class="progress-bar bg-warning" style="width: <?php echo $pending_pct; ?>%">
                                    Pending
                                </div>
                                <div class="progress-bar bg-info" style="width: <?php echo $responded_pct; ?>%">
                                    Responded
                                </div>
                                <div class="progress-bar bg-success" style="width: <?php echo $closed_pct; ?>%">
                                    Closed
                                </div>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span><?php echo $report_stats['pending'] ?? 0; ?> Pending</span>
                                <span><?php echo $report_stats['responded'] ?? 0; ?> Responded</span>
                                <span><?php echo $report_stats['closed'] ?? 0; ?> Closed</span>
                            </div>
                        </div>
                        
                        <div class="report-card">
                            <h6 class="fw-bold mb-2">Unique Patients Served</h6>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-users fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <span class="activity-count"><?php echo $report_stats['unique_patients'] ?? 0; ?></span>
                                    <span class="activity-label">Distinct patients</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="report.php" class="btn btn-warning btn-sm btn-lift me-2">
                                <i class="fas fa-chart-line me-1"></i> View Full Reports
                            </a>
                            <a href="report.php?action=export" class="btn btn-outline-warning btn-sm btn-lift">
                                <i class="fas fa-download me-1"></i> Export Data
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-5 py-4 bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h6>HealthConnect Admin System</h6>
                    <small class="text-muted">
                        <i class="fas fa-server me-1"></i> System Status: <span class="text-success">Online</span> | 
                        <i class="fas fa-database me-1"></i> Last Updated: <?php echo date('Y-m-d H:i:s'); ?>
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
        // Enhanced animations on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Animate counters
            const counters = document.querySelectorAll('.counter');
            const speed = 100; // Lower is faster
            
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
            
            // Start counters when page loads
            setTimeout(() => {
                counters.forEach(counter => {
                    animateCounter(counter);
                });
            }, 500);

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
                        #e0c3fc 29%,
                        #a370f7 67%,
                        #6f42c1 100%
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
                
                .stat-card:hover .stat-icon {
                    opacity: 0.5;
                    transform: scale(1.2);
                }
            `;
            document.head.appendChild(style);

            // Notification alert for pending items
            const pendingDoctors = <?php echo $stats['pending_doctors'] ?? 0; ?>;
            const pendingRequests = <?php echo $stats['pending_requests'] ?? 0; ?>;
            
            if (pendingDoctors > 0 || pendingRequests > 0) {
                setTimeout(() => {
                    const notification = document.createElement('div');
                    notification.className = 'position-fixed bottom-0 end-0 m-3 alert alert-warning alert-dismissible shadow-lg animate__animated animate__slideInUp';
                    notification.style.zIndex = '9999';
                    notification.style.maxWidth = '400px';
                    notification.innerHTML = `
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-bell text-warning me-3 fa-lg"></i>
                            <div>
                                <h6 class="mb-1 fw-bold">Attention Required!</h6>
                                <p class="mb-0 small">
                                    ${pendingDoctors} doctor applications and 
                                    ${pendingRequests} medical requests are awaiting review.
                                </p>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(notification);
                    
                    // Auto remove after 10 seconds
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.classList.add('animate__slideOutDown');
                            setTimeout(() => {
                                if (notification.parentNode) {
                                    notification.remove();
                                }
                            }, 500);
                        }
                    }, 10000);
                }, 2000);
            }
            
            // Auto-refresh activity data every 60 seconds
            setInterval(() => {
                console.log('Dashboard auto-refresh triggered');
                // You can add AJAX call here to refresh activity data
            }, 60000);
        });

        // Real-time update simulation (optional)
        function simulateLiveUpdates() {
            setInterval(() => {
                const counter = document.querySelector('.stat-card.requests .counter');
                if (counter && Math.random() > 0.8) {
                    const current = parseInt(counter.textContent.replace(/,/g, ''));
                    counter.textContent = (current + 1).toLocaleString();
                    
                    // Add visual feedback
                    counter.style.color = '#dc3545';
                    setTimeout(() => {
                        counter.style.color = 'white';
                    }, 1000);
                }
            }, 30000); // Check every 30 seconds
        }
        
        // Start simulation
        simulateLiveUpdates();
        
        // Card click effects
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('click', function() {
                this.style.transform = 'translateY(-20px) scale(1.05)';
                setTimeout(() => {
                    this.style.transform = 'translateY(-15px) scale(1.02)';
                }, 300);
            });
        });
    </script>
</body>
</html>
