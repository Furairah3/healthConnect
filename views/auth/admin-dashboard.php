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
    (SELECT COUNT(*) FROM hc_medical_requests WHERE request_status = 'pending') as pending_requests";

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

// Get system activity
$activity_sql = "SELECT 
    (SELECT COUNT(*) FROM hc_users WHERE DATE(date_created) = CURDATE()) as today_users,
    (SELECT COUNT(*) FROM hc_medical_requests WHERE DATE(request_date) = CURDATE()) as today_requests,
    (SELECT COUNT(*) FROM hc_medical_responses WHERE DATE(response_date) = CURDATE()) as today_responses";
$activity_stmt = $pdo->query($activity_sql);
$activity = $activity_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --admin-primary: #6f42c1;
            --admin-secondary: #4a1fb8;
            --admin-accent: #a370f7;
            --admin-dark: #2e1065;
            --admin-light: #f0e7ff;
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
        
        @keyframes rotate3D {
            0% { transform: perspective(1000px) rotateY(0deg); }
            100% { transform: perspective(1000px) rotateY(360deg); }
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
        
        .stat-card h3 {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
            position: relative;
            display: inline-block;
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
        
        .stat-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 2.5rem;
            opacity: 0.3;
            animation: float 4s ease-in-out infinite;
        }
        
        .stat-card.patients .stat-icon { animation-delay: 0.1s; }
        .stat-card.volunteers .stat-icon { animation-delay: 0.2s; }
        .stat-card.doctors .stat-icon { animation-delay: 0.3s; }
        .stat-card.requests .stat-icon { animation-delay: 0.4s; }
        
        .stat-card small {
            opacity: 0.9;
            font-weight: 500;
            letter-spacing: 0.5px;
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
        
        .quick-action-card:nth-child(1) { animation-delay: 0.2s; }
        .quick-action-card:nth-child(2) { animation-delay: 0.3s; }
        .quick-action-card:nth-child(3) { animation-delay: 0.4s; }
        
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
                var(--admin-primary), 
                var(--admin-accent), 
                var(--admin-secondary));
            transform-origin: left;
            transform: scaleX(0);
            transition: transform 0.5s;
        }
        
        .quick-action-card:hover::before {
            transform: scaleX(1);
        }
        
        .card-header {
            border-radius: 0 !important;
            border: none;
            padding: 25px 30px;
            position: relative;
            overflow: hidden;
        }
        
        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
            transform: translateX(-100%);
            animation: shimmer 3s infinite;
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
        
        .pending-badge::after {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #ff4757, #ff6b81);
            border-radius: 50px;
            z-index: -1;
            opacity: 0.5;
            animation: pulse 2s infinite;
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
        
        .list-group-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--admin-primary);
            transform: scaleY(0);
            transition: transform 0.3s;
        }
        
        .list-group-item:hover::before {
            transform: scaleY(1);
        }
        
        .list-group-item i {
            transition: all 0.3s;
        }
        
        .list-group-item:hover i {
            transform: scale(1.2);
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
        
        .activity-item:last-child {
            border-bottom: none;
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
        
        .activity-count {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--admin-dark);
            display: block;
        }
        
        .activity-label {
            font-size: 0.9rem;
            color: #666;
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
        
        /* Button animations */
        .btn-lift {
            transition: all 0.3s;
        }
        
        .btn-lift:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        
        /* Page transitions */
        .page-transition {
            animation: pageFade 0.5s ease-out;
        }
        
        @keyframes pageFade {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Notification dot */
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
                            HealthConnect Admin
                        </h3>
                        <small class="opacity-90">
                            <i class="fas fa-user me-1"></i> 
                            Welcome, <?php echo htmlspecialchars($user_name); ?>
                        </small>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <div class="me-4 position-relative">
                        <span class="admin-badge me-2 btn-lift">
                            <i class="fas fa-user-shield me-2"></i> Super Administrator
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
            <div class="col-md-3">
                <div class="stat-card patients">
                    <div class="stat-icon">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <div class="counter" data-target="<?php echo $stats['patients']; ?>">0</div>
                    <p class="mb-0 fw-semibold">Patients</p>
                    <small>Registered users seeking help</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card volunteers">
                    <div class="stat-icon">
                        <i class="fas fa-hands-helping"></i>
                    </div>
                    <div class="counter" data-target="<?php echo $stats['volunteers']; ?>">0</div>
                    <p class="mb-0 fw-semibold">Volunteers</p>
                    <small>Active healthcare helpers</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card doctors">
                    <div class="stat-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="counter" data-target="<?php echo $stats['doctors']; ?>">0</div>
                    <p class="mb-0 fw-semibold">Doctors</p>
                    <small>Verified professionals</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card requests">
                    <div class="stat-icon">
                        <i class="fas fa-file-medical"></i>
                    </div>
                    <div class="counter" data-target="<?php echo $stats['total_requests']; ?>">0</div>
                    <p class="mb-0 fw-semibold">Requests</p>
                    <small>Total medical requests</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mt-4">
        <div class="row">
            <!-- Left Column: Quick Actions -->
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
                                    <span class="pending-badge"><?php echo $stats['pending_requests']; ?> new</span>
                                <?php endif; ?>
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
                </div>
            </div>

            <!-- Middle Column: Pending Doctors -->
            <div class="col-md-4">
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
                            <div class="text-center py-5">
                                <div class="mb-4">
                                    <i class="fas fa-check-circle fa-3x text-success animate__animated animate__pulse animate__infinite" 
                                       style="animation-duration: 2s"></i>
                                </div>
                                <h6 class="fw-bold mb-2">All Caught Up!</h6>
                                <p class="text-muted mb-0">No pending doctor applications.</p>
                                <a href="admin-users.php?filter=doctors" class="btn btn-warning mt-3 btn-sm btn-lift">
                                    <i class="fas fa-user-md me-1"></i> View All Doctors
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($pending_doctors as $index => $doctor): ?>
                                <div class="border-bottom pb-3 mb-3 page-transition" style="animation-delay: <?php echo $index * 0.1; ?>s">
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
            </div>

            <!-- Right Column: Recent Health Tips -->
            <div class="col-md-4">
                <div class="card shadow-sm mb-4 quick-action-card">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-lightbulb me-2 animate__animated animate__flash" 
                               style="animation-duration: 3s; animation-iteration-count: 3"></i> 
                            Recent Health Tips
                        </h5>
                        <i class="fas fa-heartbeat fa-lg opacity-50"></i>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_tips)): ?>
                            <div class="text-center py-4">
                                <div class="mb-4">
                                    <i class="fas fa-sticky-note fa-3x text-muted"></i>
                                </div>
                                <h6 class="fw-bold mb-2">No Health Tips Yet</h6>
                                <p class="text-muted mb-3">Start creating educational content for users.</p>
                                <a href="admin-tips.php?action=create" class="btn btn-success btn-sm btn-lift">
                                    <i class="fas fa-plus me-1"></i> Create First Tip
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_tips as $index => $tip): ?>
                                <div class="tip-card page-transition" style="animation-delay: <?php echo $index * 0.1; ?>s">
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
                            <div class="text-center mt-4">
                                <a href="admin-tips.php" class="btn btn-success btn-sm btn-lift me-2">
                                    <i class="fas fa-cogs me-1"></i> Manage All Tips
                                </a>
                                <a href="admin-tips.php?action=create" class="btn btn-outline-success btn-sm btn-lift">
                                    <i class="fas fa-plus me-1"></i> Create New
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enhanced animations on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Animate counters
            const counters = document.querySelectorAll('.counter');
            const speed = 150; // Lower is faster
            
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
            counters.forEach(counter => {
                animateCounter(counter);
            });

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

            // Animate cards on scroll
            const cards = document.querySelectorAll('.quick-action-card, .stat-card');
            const cardObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });
            
            cards.forEach(card => cardObserver.observe(card));

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
                
                .stat-card, .quick-action-card {
                    will-change: transform;
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
            `;
            document.head.appendChild(style);

            // Add hover effect to cards
            document.querySelectorAll('.stat-card, .quick-action-card').forEach(card => {
                card.classList.add('shine-effect');
            });

            // Notification alert for pending items
            const pendingItems = <?php echo json_encode($stats['pending_doctors'] > 0 || $stats['pending_requests'] > 0); ?>;
            if (pendingItems) {
                setTimeout(() => {
                    const notification = document.createElement('div');
                    notification.className = 'position-fixed bottom-0 end-0 m-3 alert alert-warning alert-dismissible shadow-lg';
                    notification.style.zIndex = '9999';
                    notification.style.animation = 'slideUp 0.5s ease-out';
                    notification.innerHTML = `
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-bell text-warning me-3 fa-lg"></i>
                            <div>
                                <h6 class="mb-1">Pending Items Need Attention!</h6>
                                <p class="mb-0 small">
                                    ${<?php echo $stats['pending_doctors']; ?>} doctor applications and 
                                    ${<?php echo $stats['pending_requests']; ?>} requests are awaiting review.
                                </p>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(notification);
                    
                    // Auto remove after 10 seconds
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.remove();
                        }
                    }, 10000);
                }, 2000);
            }
        });

        // Add interactive hover effects
        document.querySelectorAll('.list-group-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                const icon = this.querySelector('i');
                if (icon) {
                    icon.style.transform = 'scale(1.2) rotate(5deg)';
                }
            });
            
            item.addEventListener('mouseleave', function() {
                const icon = this.querySelector('i');
                if (icon) {
                    icon.style.transform = 'scale(1) rotate(0)';
                }
            });
        });

        // Real-time update simulation (optional)
        function simulateLiveUpdates() {
            setInterval(() => {
                const counter = document.querySelector('.stat-card.patients .counter');
                if (counter) {
                    const current = parseInt(counter.textContent.replace(/,/g, ''));
                    const increment = Math.random() > 0.7 ? 1 : 0;
                    if (increment > 0) {
                        counter.textContent = (current + increment).toLocaleString();
                        counter.style.animation = 'none';
                        setTimeout(() => {
                            counter.style.animation = 'pulse 0.5s ease';
                        }, 10);
                    }
                }
            }, 30000); // Check every 30 seconds
        }
        
        // Start simulation
        simulateLiveUpdates();
    </script>
</body>
</html>
