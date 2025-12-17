<?php
// healthconnect/views/auth/volunteer-dashboard.php
session_start();
require_once '../../app/config/database.php';

// Check if user is logged in and is a volunteer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'volunteer') {
    header('Location: login.php?error=required');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get volunteer details including profession and location
$sql = "SELECT full_name, email_address, profession, location 
        FROM hc_users 
        WHERE user_id = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$volunteer_details = $stmt->fetch();

$profession = $volunteer_details['profession'] ?? 'Healthcare Volunteer';
$location = $volunteer_details['location'] ?? 'Not specified';

// Get volunteer statistics
$sql = "SELECT 
        (SELECT COUNT(*) FROM hc_medical_requests WHERE responded_by_user_id = :volunteer_id AND request_status = 'closed') as total_helped,
        (SELECT COUNT(*) FROM hc_medical_requests WHERE request_status = 'pending') as pending_requests,
        (SELECT COUNT(*) FROM hc_medical_requests WHERE responded_by_user_id = :volunteer_id2 AND request_status = 'responded') as active_responses,
        (SELECT COUNT(*) FROM hc_medical_requests WHERE responded_by_user_id = :volunteer_id3) as total_responses";
        
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':volunteer_id' => $user_id,
    ':volunteer_id2' => $user_id,
    ':volunteer_id3' => $user_id
]);
$stats = $stmt->fetch();

// Get recent pending requests
$sql = "SELECT r.request_id, r.request_title, r.request_description, 
               r.request_date, u.full_name as patient_name, 
               u.profession as patient_profession
        FROM hc_medical_requests r
        JOIN hc_users u ON r.patient_id = u.user_id
        WHERE r.request_status = 'pending'
        ORDER BY r.request_date DESC
        LIMIT 5";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$pending_requests = $stmt->fetchAll();

// Get volunteer's recent responses
$sql = "SELECT r.request_id, r.request_title, u.full_name as patient_name,
               r.response_date, r.request_status
        FROM hc_medical_requests r
        JOIN hc_users u ON r.patient_id = u.user_id
        WHERE r.responded_by_user_id = :volunteer_id
        ORDER BY r.response_date DESC
        LIMIT 5";
$stmt = $pdo->prepare($sql);
$stmt->execute([':volunteer_id' => $user_id]);
$my_responses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Dashboard - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --volunteer-primary: #198754;
            --volunteer-secondary: #146c43;
            --volunteer-accent: #20c997;
            --volunteer-light: #d1f2eb;
            --animation-speed: 0.5s;
            --ease-out: cubic-bezier(0.25, 0.46, 0.45, 0.94);
            --ease-in-out: cubic-bezier(0.42, 0, 0.58, 1);
        }
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            opacity: 0;
            animation: fadeIn 0.8s var(--ease-out) forwards;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9f5ee 100%);
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
        
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, 
                var(--volunteer-primary) 0%, 
                var(--volunteer-secondary) 50%,
                #0d5038 100%);
            background-size: 200% 200%;
            color: white;
            padding: 80px 0 50px;
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
            animation: gradientShift 10s ease infinite;
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
            animation: float 20s ease-in-out infinite;
        }
        
        .dashboard-header::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 200%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent 0%, 
                rgba(255,255,255,0.1) 50%, 
                transparent 100%);
            animation: shimmer 3s infinite linear;
        }
        
        .stat-card {
            border-radius: 20px;
            border: none;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            transition: all 0.4s var(--ease-out);
            overflow: hidden;
            position: relative;
            animation: slideUp 0.6s var(--ease-out) forwards;
            opacity: 0;
        }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        
        .stat-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
        }
        
        .stat-card::before {
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
        
        .stat-card:hover::before {
            transform: scaleX(1);
        }
        
        .stat-card.helped::before { background: linear-gradient(90deg, #198754, #20c997, #4cd964); }
        .stat-card.pending::before { background: linear-gradient(90deg, #ffc107, #ff9800, #ff5722); }
        .stat-card.active::before { background: linear-gradient(90deg, #0dcaf0, #0d6efd, #6610f2); }
        .stat-card.total::before { background: linear-gradient(90deg, #6f42c1, #d63384, #fd7e14); }
        
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
        
        .stat-card.helped .stat-icon { 
            background: linear-gradient(135deg, rgba(25, 135, 84, 0.1), rgba(32, 201, 151, 0.2));
            color: #198754;
            box-shadow: 0 5px 15px rgba(25, 135, 84, 0.2);
        }
        .stat-card.pending .stat-icon { 
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(255, 152, 0, 0.2));
            color: #ffc107;
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.2);
        }
        .stat-card.active .stat-icon { 
            background: linear-gradient(135deg, rgba(13, 202, 240, 0.1), rgba(13, 110, 253, 0.2));
            color: #0dcaf0;
            box-shadow: 0 5px 15px rgba(13, 202, 240, 0.2);
        }
        .stat-card.total .stat-icon { 
            background: linear-gradient(135deg, rgba(111, 66, 193, 0.1), rgba(214, 51, 132, 0.2));
            color: #6f42c1;
            box-shadow: 0 5px 15px rgba(111, 66, 193, 0.2);
        }
        
        .stat-icon::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.8) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .stat-card:hover .stat-icon::after {
            opacity: 1;
        }
        
        .stat-card h2 {
            background: linear-gradient(90deg, #333, #666);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
            font-size: 2.5rem;
            transition: all 0.3s;
        }
        
        .stat-card:hover h2 {
            background: linear-gradient(90deg, var(--volunteer-primary), var(--volunteer-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .request-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
            transition: all 0.4s var(--ease-out);
            border-left: 5px solid var(--volunteer-primary);
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
            border-left: 5px solid var(--volunteer-accent);
        }
        
        .volunteer-badge {
            background: linear-gradient(135deg, var(--volunteer-primary), var(--volunteer-secondary));
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s var(--ease-out);
            display: inline-block;
            box-shadow: 0 4px 15px rgba(25, 135, 84, 0.3);
        }
        
        .volunteer-badge:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(25, 135, 84, 0.4);
            background: linear-gradient(135deg, var(--volunteer-secondary), var(--volunteer-primary));
        }
        
        .action-btn {
            min-width: 120px;
            transition: all 0.3s var(--ease-out);
            position: relative;
            overflow: hidden;
        }
        
        .action-btn::after {
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
        
        .action-btn:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }
        
        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            100% {
                transform: scale(20, 20);
                opacity: 0;
            }
        }
        
        .profile-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--volunteer-primary), var(--volunteer-secondary));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
            transition: all 0.3s var(--ease-out);
            box-shadow: 0 4px 10px rgba(25, 135, 84, 0.3);
        }
        
        .profile-avatar:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 6px 15px rgba(25, 135, 84, 0.4);
        }
        
        .welcome-message {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: slideUp 0.8s var(--ease-out) 0.2s forwards;
            opacity: 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .impact-meter {
            height: 12px;
            background: rgba(255,255,255,0.2);
            border-radius: 6px;
            overflow: hidden;
            margin-top: 10px;
            position: relative;
        }
        
        .impact-fill {
            height: 100%;
            background: linear-gradient(90deg, #20c997, #198754, #146c43);
            border-radius: 6px;
            width: 0;
            position: relative;
            overflow: hidden;
        }
        
        .impact-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 100%;
            background: linear-gradient(90deg,
                transparent,
                rgba(255,255,255,0.4),
                transparent);
            animation: shimmer 2s infinite;
        }
        
        .volunteer-info-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            animation: slideUp 0.6s var(--ease-out) 0.3s forwards;
            opacity: 0;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            padding: 8px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        
        .info-item:hover {
            transform: translateX(5px);
            border-bottom-color: var(--volunteer-accent);
        }
        
        .info-item i {
            width: 30px;
            color: var(--volunteer-primary);
            transition: all 0.3s;
        }
        
        .info-item:hover i {
            transform: scale(1.2);
            color: var(--volunteer-accent);
        }
        
        .action-card {
            border: none;
            border-radius: 15px;
            transition: all 0.4s var(--ease-out);
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
            position: relative;
            overflow: hidden;
            animation: slideUp 0.5s var(--ease-out) forwards;
            opacity: 0;
        }
        
        .action-card:nth-child(1) { animation-delay: 0.1s; }
        .action-card:nth-child(2) { animation-delay: 0.2s; }
        .action-card:nth-child(3) { animation-delay: 0.3s; }
        .action-card:nth-child(4) { animation-delay: 0.4s; }
        
        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(255,255,255,0.3), 
                transparent);
            transition: left 0.7s;
        }
        
        .action-card:hover::before {
            left: 100%;
        }
        
        .action-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }
        
        .nav-item .nav-link {
            position: relative;
            padding: 10px 15px;
            transition: all 0.3s;
        }
        
        .nav-item .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 3px;
            background: var(--volunteer-accent);
            transition: all 0.3s var(--ease-out);
            border-radius: 3px;
        }
        
        .nav-item .nav-link:hover::after,
        .nav-item .nav-link.active::after {
            width: 80%;
            left: 10%;
        }
        
        .badge {
            transition: all 0.3s;
        }
        
        .badge:hover {
            transform: scale(1.1);
        }
        
        /* Floating particles effect */
        .floating-particles {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: 0;
        }
        
        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            animation: floatParticle 20s infinite linear;
        }
        
        @keyframes floatParticle {
            0% {
                transform: translateY(0) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) translateX(100px);
                opacity: 0;
            }
        }
        
        /* Notification bell animation */
        .notification-bell {
            position: relative;
        }
        
        .notification-bell .fa-bell {
            animation: ringBell 2s ease infinite;
            transform-origin: top center;
        }
        
        @keyframes ringBell {
            0%, 100% { transform: rotate(0); }
            10%, 30%, 50%, 70%, 90% { transform: rotate(10deg); }
            20%, 40%, 60%, 80% { transform: rotate(-10deg); }
        }
        
        /* Counter animation */
        .counter {
            display: inline-block;
        }
        
        /* Loading skeleton */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 4px;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* Card hover shine effect */
        .card-hover-shine {
            position: relative;
            overflow: hidden;
        }
        
        .card-hover-shine::before {
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
        
        .card-hover-shine:hover::before {
            transform: rotate(30deg) translate(10%, 10%);
        }
        
        /* Scroll animations */
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s var(--ease-out);
        }
        
        .animate-on-scroll.visible {
            opacity: 1;
            transform: translateY(0);
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
                        <a class="nav-link active" href="volunteer-dashboard.php">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="respond-requests.php">
                            <i class="fas fa-hands-helping me-1"></i> Help Requests
                            <?php if (($stats['pending_requests'] ?? 0) > 0): ?>
                                <span class="badge bg-danger ms-1 animate__animated animate__pulse animate__infinite" style="animation-duration: 2s">
                                    <?php echo $stats['pending_requests']; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="training.php">
                            <i class="fas fa-book-medical me-1"></i> Training
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
                            <li><a class="dropdown-item" href="my-responses.php"><i class="fas fa-history me-2"></i> My Responses</a></li>

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
                <div class="col-lg-6">
                    <div class="welcome-message">
                        <h1 class="fw-bold mb-3 animate-charcter">Welcome, <?php echo htmlspecialchars($user_name); ?>! 🙌</h1>
                        <p class="lead mb-0">Your volunteer work makes a real difference in rural healthcare access.</p>
                        
                        <!-- Volunteer Info -->
                        <div class="volunteer-info-card">
                            <div class="info-item">
                                <i class="fas fa-briefcase"></i>
                                <span style="color: black;"><strong>Profession:</strong> <?php echo htmlspecialchars($profession); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span style="color: black;"><strong>Location:</strong> <?php echo htmlspecialchars($location); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-envelope"></i>
                                <span style="color: black;"><strong>Email:</strong> <?php echo htmlspecialchars($volunteer_details['email_address'] ?? ''); ?></span>
                            </div>
                        </div>
                        
                        <!-- Impact Meter -->
                        <div class="mt-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-white">Your Impact Level</span>
                                <span class="text-white fw-bold impact-level">
                                    <?php 
                                    $totalHelped = $stats['total_helped'] ?? 0;
                                    if ($totalHelped >= 20) {
                                        echo 'Healthcare Hero';
                                    } elseif ($totalHelped >= 10) {
                                        echo 'Seasoned Helper';
                                    } elseif ($totalHelped >= 5) {
                                        echo 'Active Helper';
                                    } else {
                                        echo 'Getting Started';
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="impact-meter">
                                <div class="impact-fill" 
                                     data-width="<?php echo min($totalHelped * 5, 100); ?>%"
                                     style="width: 0;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 text-end position-relative">
                    <!-- Floating Particles -->
                    <div class="floating-particles">
                        <?php for ($i = 0; $i < 20; $i++): ?>
                            <div class="particle" 
                                 style="width: <?php echo rand(2, 6); ?>px; 
                                        height: <?php echo rand(2, 6); ?>px;
                                        left: <?php echo rand(0, 100); ?>%;
                                        animation-delay: <?php echo rand(0, 20); ?>s;
                                        animation-duration: <?php echo rand(15, 30); ?>s;"></div>
                        <?php endfor; ?>
                    </div>
                    
                    <div class="mb-4">
                        <a href="volunteer-info.php" 
                        class="volunteer-badge me-3" 
                        style="text-decoration: none;">
                            <i class="fas fa-hands-helping me-1"></i> Healthcare Volunteer
                        </a>
                        <a href="profile.php" 
                        class="btn btn-outline-light btn-lg px-4 me-2 pulse-on-hover">
                            <i class="fas fa-edit me-2"></i> Update Profile
                        </a>
                    </div>
                    <a href="respond-requests.php" 
                    id="helpNowBtn"
                    class="btn btn-light btn-lg px-4 shadow hover-lift">
                        <i class="fas fa-plus-circle me-2"></i> Help Someone Now
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="container mb-5">
        <div class="row g-4 animate-on-scroll">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card helped p-4 text-center">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 class="fw-bold mb-2 counter" data-target="<?php echo $stats['total_helped'] ?? 0; ?>">0</h2>
                    <p class="text-muted mb-0">Successfully Helped</p>
                    <small class="text-success">Patients assisted</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card pending p-4 text-center">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h2 class="fw-bold mb-2 counter" data-target="<?php echo $stats['pending_requests'] ?? 0; ?>">0</h2>
                    <p class="text-muted mb-0">Pending Requests</p>
                    <small class="text-warning">Need assistance</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card active p-4 text-center">
                    <div class="stat-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h2 class="fw-bold mb-2 counter" data-target="<?php echo $stats['active_responses'] ?? 0; ?>">0</h2>
                    <p class="text-muted mb-0">Active Responses</p>
                    <small class="text-info">Currently helping</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card total p-4 text-center">
                    <div class="stat-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <h2 class="fw-bold mb-2 counter" data-target="<?php echo $stats['total_responses'] ?? 0; ?>">0</h2>
                    <p class="text-muted mb-0">Total Responses</p>
                    <small class="text-purple">All-time responses</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="row">
            <!-- Pending Requests -->
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 mb-4 animate-on-scroll">
                    <div class="card-header bg-white border-0 py-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-0"><i class="fas fa-hands-helping text-success me-2"></i> Requests Needing Help</h4>
                                <p class="text-muted mb-0 mt-1">Patients waiting for your assistance</p>
                            </div>
                            <a href="respond-requests.php" class="btn btn-success hover-lift">
                                <i class="fas fa-search me-1"></i> Browse All
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <?php if (empty($pending_requests)): ?>
                            <div class="text-center py-5">
                                <div class="mb-4">
                                    <i class="fas fa-check-circle fa-3x text-success animate__animated animate__pulse animate__infinite" style="animation-duration: 2s"></i>
                                </div>
                                <h5>No pending requests!</h5>
                                <p class="text-muted mb-4">All current requests have been responded to.</p>
                                <a href="training.php" class="btn btn-outline-success hover-lift">
                                    <i class="fas fa-graduation-cap me-2"></i> View Training Materials
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($pending_requests as $index => $request): ?>
                                <div class="request-card mb-3 p-3 card-hover-shine" style="animation-delay: <?php echo $index * 0.1; ?>s">
                                    <h6 class="fw-bold mb-2"><?php echo htmlspecialchars($request['request_title']); ?></h6>
                                    <p class="text-muted small mb-2">
                                        <?php echo substr(htmlspecialchars($request['request_description']), 0, 100); ?>...
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i>
                                                <?php echo htmlspecialchars($request['patient_name']); ?>
                                            </small>
                                            <?php if (!empty($request['patient_profession'])): ?>
                                                <small class="text-muted ms-3">
                                                    <i class="fas fa-briefcase me-1"></i>
                                                    <?php echo htmlspecialchars($request['patient_profession']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <a href="respond-requests.php?request_id=<?php echo $request['request_id']; ?>" 
                                           class="btn btn-sm btn-success action-btn hover-lift">
                                            <i class="fas fa-comment me-1"></i> Respond
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- My Recent Responses -->
            <div class="col-lg-5">
                <div class="card shadow-sm border-0 mb-4 animate-on-scroll">
                    <div class="card-header bg-white border-0 py-4">
                        <h4 class="mb-0"><i class="fas fa-history text-primary me-2"></i> My Recent Help</h4>
                        <p class="text-muted mb-0 mt-1">Your recent volunteer work</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if (empty($my_responses)): ?>
                            <div class="text-center py-4">
                                <div class="mb-4">
                                    <i class="fas fa-hands-helping fa-3x text-muted animate__animated animate__wobble animate__infinite" style="animation-duration: 3s"></i>
                                </div>
                                <h5>Start helping!</h5>
                                <p class="text-muted mb-0">Your responses will appear here.</p>
                                <a href="respond-requests.php" class="btn btn-success mt-3 hover-lift">
                                    <i class="fas fa-plus-circle me-2"></i> Help First Patient
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($my_responses as $response): ?>
                                <div class="d-flex align-items-center mb-3 pb-3 border-bottom hover-lift" style="transition: all 0.3s; padding: 10px; border-radius: 10px;">
                                    <div class="flex-shrink-0">
                                        <div class="bg-light rounded-circle p-3">
                                            <i class="fas fa-user-injured text-success"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($response['patient_name']); ?></h6>
                                        <p class="text-muted small mb-0">
                                            <?php echo htmlspecialchars(substr($response['request_title'], 0, 30)); ?>...
                                        </p>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo date('M d', strtotime($response['response_date'])); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-<?php echo $response['request_status'] === 'closed' ? 'success' : 'info'; ?> hover-lift">
                                        <?php echo ucfirst($response['request_status']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-3">
                                <a href="my-responses.php" class="btn btn-outline-success btn-sm hover-lift">
                                    <i class="fas fa-list me-1"></i> View All Responses
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm border-0 animate-on-scroll">
                    <div class="card-header bg-white border-0 py-4">
                        <h4 class="mb-0"><i class="fas fa-bolt text-warning me-2"></i> Volunteer Tools</h4>
                        <p class="text-muted mb-0 mt-1">Resources and tools for volunteers</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6">
                                <a href="training.php" class="card action-card text-decoration-none">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-graduation-cap fa-3x text-primary"></i>
                                        </div>
                                        <h6 class="fw-bold mb-2">Training Materials</h6>
                                        <p class="text-muted small mb-0">Learn best practices</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="resources.php" class="card action-card text-decoration-none">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-book-medical fa-3x text-success"></i>
                                        </div>
                                        <h6 class="fw-bold mb-2">Health Resources</h6>
                                        <p class="text-muted small mb-0">Reference materials</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="community.php" class="card action-card text-decoration-none">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-users fa-3x text-info"></i>
                                        </div>
                                        <h6 class="fw-bold mb-2">Volunteer Community</h6>
                                        <p class="text-muted small mb-0">Connect with others</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="impact.php" class="card action-card text-decoration-none">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-chart-line fa-3x text-warning"></i>
                                        </div>
                                        <h6 class="fw-bold mb-2">Your Impact</h6>
                                        <p class="text-muted small mb-0">See your contribution</p>
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
                        <i class="fas fa-hands-helping text-success me-2 animate__animated animate__pulse animate__infinite" style="animation-duration: 3s"></i>
                        HealthConnect Volunteer Dashboard
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
            // Animate impact meter
            const impactFill = document.querySelector('.impact-fill');
            if (impactFill) {
                const width = impactFill.getAttribute('data-width');
                setTimeout(() => {
                    impactFill.style.transition = 'width 1.5s ease-out';
                    impactFill.style.width = width;
                }, 800);
            }

            // Animate statistics counters
            const counters = document.querySelectorAll('.counter');
            const speed = 200; // Lower is faster
            
            const animateCounter = (counter) => {
                const target = +counter.getAttribute('data-target');
                const count = +counter.innerText;
                const increment = target / speed;
                
                if (count < target) {
                    counter.innerText = Math.ceil(count + increment);
                    setTimeout(() => animateCounter(counter), 20);
                } else {
                    counter.innerText = target.toLocaleString();
                }
            };
            
            // Start counters when element is in view
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        animateCounter(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.5 });
            
            counters.forEach(counter => observer.observe(counter));

            // Add scroll animations
            const scrollElements = document.querySelectorAll('.animate-on-scroll');
            const elementInView = (el, dividend = 1) => {
                const elementTop = el.getBoundingClientRect().top;
                return (
                    elementTop <= (window.innerHeight || document.documentElement.clientHeight) / dividend
                );
            };
            
            const displayScrollElement = (element) => {
                element.classList.add('visible');
            };
            
            const handleScrollAnimation = () => {
                scrollElements.forEach((el) => {
                    if (elementInView(el, 1.25)) {
                        displayScrollElement(el);
                    }
                });
            };
            
            window.addEventListener('scroll', () => {
                handleScrollAnimation();
            });
            
            // Initial check
            handleScrollAnimation();

            // Button hover effects
            document.querySelectorAll('.hover-lift').forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 10px 20px rgba(0,0,0,0.15)';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '';
                });
            });

            // Ripple effect for buttons
            document.querySelectorAll('.action-btn').forEach(button => {
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

            // Animate the main help button
            const helpNowBtn = document.getElementById('helpNowBtn');
            if (helpNowBtn) {
                helpNowBtn.addEventListener('mouseenter', () => {
                    helpNowBtn.style.transform = 'translateY(-5px) scale(1.05)';
                    helpNowBtn.style.boxShadow = '0 15px 30px rgba(0,0,0,0.2)';
                });
                
                helpNowBtn.addEventListener('mouseleave', () => {
                    helpNowBtn.style.transform = 'translateY(0) scale(1)';
                    helpNowBtn.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
                });
                
                // Pulsing animation for attention
                setInterval(() => {
                    helpNowBtn.classList.toggle('animate__pulse');
                }, 5000);
            }

            // Add CSS for ripple effect
            const style = document.createElement('style');
            style.textContent = `
                .ripple-effect {
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.6);
                    transform: scale(0);
                    animation: ripple-animation 0.6s linear;
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
                        #c8f7dc 29%,
                        #84e6b8 67%,
                        #198754 100%
                    );
                    background-size: 200% auto;
                    color: #fff;
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
                
                .pulse-on-hover:hover {
                    animation: pulse 0.5s ease-in-out;
                }
                
                .request-card, .action-card, .stat-card {
                    will-change: transform;
                }
            `;
            document.head.appendChild(style);
        });

        // Add confetti effect for significant achievements
        function celebrateAchievement() {
            const totalHelped = <?php echo $stats['total_helped'] ?? 0; ?>;
            if (totalHelped > 0 && totalHelped % 5 === 0) {
                createConfetti();
            }
        }

        function createConfetti() {
            const colors = ['#198754', '#20c997', '#146c43', '#0d5038'];
            const confettiCount = 50;
            
            for (let i = 0; i < confettiCount; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.cssText = `
                    position: fixed;
                    width: 10px;
                    height: 10px;
                    background: ${colors[Math.floor(Math.random() * colors.length)]};
                    top: -20px;
                    left: ${Math.random() * 100}vw;
                    border-radius: 2px;
                    z-index: 9999;
                    pointer-events: none;
                `;
                
                document.body.appendChild(confetti);
                
                const animation = confetti.animate([
                    { transform: `translateY(0) rotate(0deg)`, opacity: 1 },
                    { transform: `translateY(${window.innerHeight}px) rotate(${Math.random() * 360}deg)`, opacity: 0 }
                ], {
                    duration: Math.random() * 3000 + 2000,
                    easing: 'cubic-bezier(0.215, 0.61, 0.355, 1)'
                });
                
                animation.onfinish = () => confetti.remove();
            }
        }

        // Trigger celebration on page load if applicable
        setTimeout(celebrateAchievement, 1500);
    </script>
</body>
</html>
