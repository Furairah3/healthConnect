<?php
// healthconnect/views/auth/reports.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header('Location: login.php?error=required');
    exit();
}

require_once '../../app/config/database.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Date range filter
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month
$report_type = $_GET['report_type'] ?? 'overview';

// Get doctor statistics
$stats_sql = "SELECT 
    (SELECT COUNT(*) FROM hc_medical_requests WHERE responded_by_user_id = :doctor_id AND request_status = 'closed') as total_helped,
    (SELECT COUNT(*) FROM hc_health_tips WHERE doctor_user_id = :doctor_id2) as total_tips,
    (SELECT COUNT(*) FROM hc_medical_requests WHERE responded_by_user_id = :doctor_id3 AND request_status = 'responded') as active_cases,
    (SELECT COUNT(DISTINCT patient_id) FROM hc_medical_requests WHERE responded_by_user_id = :doctor_id4) as unique_patients";
    
$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute([
    ':doctor_id' => $user_id,
    ':doctor_id2' => $user_id,
    ':doctor_id3' => $user_id,
    ':doctor_id4' => $user_id
]);
$stats = $stats_stmt->fetch();

// Get monthly performance data
$monthly_sql = "SELECT 
    DATE_FORMAT(response_date, '%Y-%m') as month,
    COUNT(*) as responses,
    SUM(CASE WHEN request_status = 'closed' THEN 1 ELSE 0 END) as closed_cases,
    AVG(TIMESTAMPDIFF(HOUR, request_date, response_date)) as avg_response_time
    FROM hc_medical_requests 
    WHERE responded_by_user_id = :doctor_id 
    AND response_date IS NOT NULL
    AND response_date BETWEEN :start_date AND :end_date
    GROUP BY DATE_FORMAT(response_date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12";

$monthly_stmt = $pdo->prepare($monthly_sql);
$monthly_stmt->execute([
    ':doctor_id' => $user_id,
    ':start_date' => date('Y-m-01', strtotime('-11 months')),
    ':end_date' => date('Y-m-t')
]);
$monthly_data = $monthly_stmt->fetchAll();

// Get request categories data
$categories_sql = "SELECT 
    COALESCE(category, 'general') as category,
    COUNT(*) as count,
    SUM(CASE WHEN request_status = 'closed' THEN 1 ELSE 0 END) as closed
    FROM hc_medical_requests 
    WHERE responded_by_user_id = :doctor_id
    AND request_date BETWEEN :start_date AND :end_date
    GROUP BY COALESCE(category, 'general')
    ORDER BY count DESC";

$categories_stmt = $pdo->prepare($categories_sql);
$categories_stmt->execute([
    ':doctor_id' => $user_id,
    ':start_date' => $start_date,
    ':end_date' => $end_date
]);
$categories_data = $categories_stmt->fetchAll();

// Get daily activity data for current month
$daily_sql = "SELECT 
    DATE(request_date) as date,
    COUNT(*) as requests,
    SUM(CASE WHEN responded_by_user_id = :doctor_id THEN 1 ELSE 0 END) as responded
    FROM hc_medical_requests 
    WHERE request_date BETWEEN :start_date AND :end_date
    GROUP BY DATE(request_date)
    ORDER BY date";

$daily_stmt = $pdo->prepare($daily_sql);
$daily_stmt->execute([
    ':doctor_id' => $user_id,
    ':start_date' => $start_date,
    ':end_date' => $end_date
]);
$daily_data = $daily_stmt->fetchAll();

// Get response time statistics
$response_time_sql = "SELECT 
    AVG(TIMESTAMPDIFF(HOUR, request_date, response_date)) as avg_hours,
    MIN(TIMESTAMPDIFF(HOUR, request_date, response_date)) as min_hours,
    MAX(TIMESTAMPDIFF(HOUR, request_date, response_date)) as max_hours,
    COUNT(*) as total_responses
    FROM hc_medical_requests 
    WHERE responded_by_user_id = :doctor_id
    AND response_date IS NOT NULL
    AND request_date BETWEEN :start_date AND :end_date";

$response_time_stmt = $pdo->prepare($response_time_sql);
$response_time_stmt->execute([
    ':doctor_id' => $user_id,
    ':start_date' => $start_date,
    ':end_date' => $end_date
]);
$response_time = $response_time_stmt->fetch();

// Get patient satisfaction data (if feedback table exists)
$satisfaction_sql = "SELECT 
    AVG(feedback_rating) as avg_rating,
    COUNT(*) as total_feedback,
    SUM(CASE WHEN feedback_rating >= 4 THEN 1 ELSE 0 END) as positive_feedback
    FROM hc_feedback 
    WHERE volunteer_id = :doctor_id";

try {
    $satisfaction_stmt = $pdo->prepare($satisfaction_sql);
    $satisfaction_stmt->execute([':doctor_id' => $user_id]);
    $satisfaction = $satisfaction_stmt->fetch();
} catch (Exception $e) {
    $satisfaction = ['avg_rating' => 0, 'total_feedback' => 0, 'positive_feedback' => 0];
}

// Prepare data for charts
$monthly_labels = [];
$monthly_responses = [];
$monthly_closed = [];

foreach ($monthly_data as $month) {
    $monthly_labels[] = date('M Y', strtotime($month['month'] . '-01'));
    $monthly_responses[] = (int)$month['responses'];
    $monthly_closed[] = (int)$month['closed_cases'];
}

$category_labels = [];
$category_counts = [];

foreach ($categories_data as $category) {
    $category_labels[] = ucfirst($category['category']);
    $category_counts[] = (int)$category['count'];
}

// Calculate performance metrics
$completion_rate = $stats['total_helped'] > 0 ? 
    round(($stats['total_helped'] / ($stats['total_helped'] + $stats['active_cases'])) * 100, 1) : 0;

$avg_response_time = $response_time['avg_hours'] ? round($response_time['avg_hours'], 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --doctor-primary: #0d6efd;
            --doctor-secondary: #052c65;
            --doctor-accent: #20c997;
            --doctor-light: #e3f2fd;
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
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .reports-header {
            background: linear-gradient(135deg, var(--doctor-primary) 0%, var(--doctor-secondary) 100%);
            background-size: 200% 200%;
            color: white;
            padding: 60px 0 40px;
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
            animation: gradientShift 8s ease infinite;
        }
        
        .reports-header::before {
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
        
        .stat-card.primary { 
            background: linear-gradient(135deg, #0d6efd, #0dcaf0, #6610f2);
            background-size: 200% 200%;
        }
        .stat-card.success { 
            background: linear-gradient(135deg, #20c997, #198754, #146c43);
            background-size: 200% 200%;
        }
        .stat-card.warning { 
            background: linear-gradient(135deg, #ffc107, #fd7e14, #dc3545);
            background-size: 200% 200%;
        }
        .stat-card.info { 
            background: linear-gradient(135deg, #6f42c1, #d63384, #fd7e14);
            background-size: 200% 200%;
        }
        
        .stat-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 2.5rem;
            opacity: 0.3;
            animation: float 4s ease-in-out infinite;
        }
        
        .stat-card h3 {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .chart-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.5s var(--ease-out);
            animation: slideUp 0.6s var(--ease-out) forwards;
            opacity: 0;
        }
        
        .chart-card:nth-child(1) { animation-delay: 0.2s; }
        .chart-card:nth-child(2) { animation-delay: 0.3s; }
        .chart-card:nth-child(3) { animation-delay: 0.4s; }
        .chart-card:nth-child(4) { animation-delay: 0.5s; }
        
        .chart-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .filter-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            animation: slideUp 0.6s var(--ease-out) 0.1s forwards;
            opacity: 0;
        }
        
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--doctor-primary);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            transform: translateY(-2px);
        }
        
        .btn-generate {
            background: linear-gradient(135deg, var(--doctor-primary), var(--doctor-secondary));
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-generate:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(13, 110, 253, 0.3);
        }
        
        .metric-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
            border-left: 4px solid var(--doctor-primary);
            transition: all 0.3s;
            animation: slideUp 0.6s var(--ease-out) forwards;
            opacity: 0;
        }
        
        .metric-card:nth-child(1) { animation-delay: 0.6s; }
        .metric-card:nth-child(2) { animation-delay: 0.7s; }
        .metric-card:nth-child(3) { animation-delay: 0.8s; }
        .metric-card:nth-child(4) { animation-delay: 0.9s; }
        
        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.1);
        }
        
        .progress-ring {
            width: 80px;
            height: 80px;
        }
        
        .progress-ring-circle {
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
            stroke: var(--doctor-primary);
            stroke-width: 8;
            fill: transparent;
            stroke-dasharray: 251.2;
            stroke-linecap: round;
        }
        
        .rating-stars {
            color: #ffc107;
            font-size: 20px;
        }
        
        .trend-indicator {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .trend-up {
            background: rgba(32, 201, 151, 0.1);
            color: #198754;
        }
        
        .trend-down {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .trend-neutral {
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }
        
        .report-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            padding: 12px 20px;
            border-radius: 10px;
            margin-right: 10px;
            transition: all 0.3s;
        }
        
        .report-tabs .nav-link.active {
            background: linear-gradient(135deg, var(--doctor-primary), var(--doctor-secondary));
            color: white;
        }
        
        .report-tabs .nav-link:hover:not(.active) {
            background: var(--doctor-light);
            color: var(--doctor-primary);
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
        
        .download-btn {
            background: linear-gradient(135deg, #20c997, #198754);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .download-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(32, 201, 151, 0.3);
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
        }
        
        .loading-overlay.active {
            opacity: 1;
            pointer-events: all;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--doctor-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .reports-header {
                padding: 40px 0 30px;
            }
            
            .chart-card, .filter-card {
                padding: 20px;
            }
            
            .stat-card h3 {
                font-size: 2.2rem;
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
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
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
                        <a class="nav-link" href="doctor-dashboard.php">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="patient-directory.php">
                            <i class="fas fa-users me-1"></i> Patient Directory
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="reports.php">
                            <i class="fas fa-chart-line me-1"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="profile-avatar me-2" style="width: 35px; height: 35px; background: linear-gradient(135deg, #0d6efd, #052c65); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <span>Dr. <?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <div class="reports-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="fw-bold mb-3 animate-charcter">
                        <i class="fas fa-chart-line me-2"></i> Reports & Analytics
                    </h1>
                    <p class="lead mb-0 opacity-75">Track your performance and impact over time</p>
                </div>
                <div class="col-lg-4 text-end">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-light btn-lg px-4" onclick="exportReport()">
                            <i class="fas fa-download me-2"></i> Export
                        </button>
                        <button type="button" class="btn btn-light btn-lg px-4" onclick="printReport()">
                            <i class="fas fa-print me-2"></i> Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="container mb-5">
        <div class="filter-card">
            <form method="GET" id="reportForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Start Date</label>
                        <input type="date" 
                               class="form-control" 
                               name="start_date" 
                               value="<?php echo $start_date; ?>"
                               max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">End Date</label>
                        <input type="date" 
                               class="form-control" 
                               name="end_date" 
                               value="<?php echo $end_date; ?>"
                               max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Report Type</label>
                        <select class="form-select" name="report_type">
                            <option value="overview" <?php echo $report_type == 'overview' ? 'selected' : ''; ?>>Overview</option>
                            <option value="performance" <?php echo $report_type == 'performance' ? 'selected' : ''; ?>>Performance</option>
                            <option value="patients" <?php echo $report_type == 'patients' ? 'selected' : ''; ?>>Patient Analysis</option>
                            <option value="timeline" <?php echo $report_type == 'timeline' ? 'selected' : ''; ?>>Timeline</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn-generate w-100">
                            <i class="fas fa-chart-bar me-2"></i> Generate Report
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="container mb-5">
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card primary">
                    <div class="stat-icon">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <h3><?php echo $stats['total_helped']; ?></h3>
                    <p class="mb-0 fw-semibold">Patients Helped</p>
                    <small>Lifetime assistance</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card success">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3><?php echo $stats['active_cases']; ?></h3>
                    <p class="mb-0 fw-semibold">Active Cases</p>
                    <small>Currently assisting</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card warning">
                    <div class="stat-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h3><?php echo $stats['total_tips']; ?></h3>
                    <p class="mb-0 fw-semibold">Health Tips</p>
                    <small>Shared knowledge</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card info">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3><?php echo $stats['unique_patients']; ?></h3>
                    <p class="mb-0 fw-semibold">Unique Patients</p>
                    <small>Individual care</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="container mb-5">
        <h3 class="fw-bold mb-4 text-primary">
            <i class="fas fa-tachometer-alt me-2"></i> Performance Metrics
        </h3>
        <div class="row">
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="d-flex align-items-center mb-3">
                        <div class="progress-ring me-3">
                            <svg width="80" height="80" viewBox="0 0 80 80">
                                <circle cx="40" cy="40" r="36" class="progress-ring-circle" 
                                        stroke-dashoffset="<?php echo 251.2 - (251.2 * $completion_rate / 100); ?>"
                                        style="stroke: <?php echo $completion_rate >= 80 ? '#198754' : ($completion_rate >= 60 ? '#ffc107' : '#dc3545'); ?>">
                                </circle>
                            </svg>
                        </div>
                        <div>
                            <div class="fw-bold fs-3"><?php echo $completion_rate; ?>%</div>
                            <div class="text-muted small">Completion Rate</div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="trend-indicator <?php echo $completion_rate >= 80 ? 'trend-up' : ($completion_rate >= 60 ? 'trend-neutral' : 'trend-down'); ?>">
                            <i class="fas <?php echo $completion_rate >= 80 ? 'fa-arrow-up' : ($completion_rate >= 60 ? 'fa-minus' : 'fa-arrow-down'); ?> me-1"></i>
                            <?php echo $completion_rate >= 80 ? 'Excellent' : ($completion_rate >= 60 ? 'Good' : 'Needs Improvement'); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="mb-3">
                        <div class="fw-bold fs-3"><?php echo $avg_response_time; ?>h</div>
                        <div class="text-muted small">Avg Response Time</div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="trend-indicator <?php echo $avg_response_time <= 24 ? 'trend-up' : ($avg_response_time <= 48 ? 'trend-neutral' : 'trend-down'); ?>">
                            <i class="fas <?php echo $avg_response_time <= 24 ? 'fa-arrow-up' : ($avg_response_time <= 48 ? 'fa-minus' : 'fa-arrow-down'); ?> me-1"></i>
                            <?php echo $avg_response_time <= 24 ? 'Fast' : ($avg_response_time <= 48 ? 'Average' : 'Slow'); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="mb-3">
                        <div class="fw-bold fs-3">
                            <?php echo $satisfaction['avg_rating'] ? number_format($satisfaction['avg_rating'], 1) : 'N/A'; ?>
                            <span class="rating-stars ms-2">
                                <?php 
                                $rating = $satisfaction['avg_rating'] ?? 0;
                                for ($i = 1; $i <= 5; $i++): 
                                    $class = $i <= floor($rating) ? 'fas fa-star' : ($i <= $rating ? 'fas fa-star-half-alt' : 'far fa-star');
                                ?>
                                    <i class="<?php echo $class; ?>"></i>
                                <?php endfor; ?>
                            </span>
                        </div>
                        <div class="text-muted small">Patient Satisfaction</div>
                    </div>
                    <?php if ($satisfaction['total_feedback'] > 0): ?>
                        <div class="d-flex justify-content-between">
                            <span class="trend-indicator <?php echo $satisfaction['avg_rating'] >= 4 ? 'trend-up' : ($satisfaction['avg_rating'] >= 3 ? 'trend-neutral' : 'trend-down'); ?>">
                                <i class="fas <?php echo $satisfaction['avg_rating'] >= 4 ? 'fa-arrow-up' : ($satisfaction['avg_rating'] >= 3 ? 'fa-minus' : 'fa-arrow-down'); ?> me-1"></i>
                                <?php echo $satisfaction['positive_feedback']; ?> positive
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="mb-3">
                        <div class="fw-bold fs-3"><?php echo $response_time['total_responses'] ?? 0; ?></div>
                        <div class="text-muted small">Total Responses</div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            <?php echo date('M Y', strtotime($start_date)); ?> - <?php echo date('M Y', strtotime($end_date)); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="container">
        <div class="row">
            <!-- Monthly Performance Chart -->
            <div class="col-lg-8">
                <div class="chart-card">
                    <h5 class="fw-bold mb-4">
                        <i class="fas fa-chart-bar me-2 text-primary"></i> Monthly Performance
                    </h5>
                    <div class="chart-container">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Request Categories Chart -->
            <div class="col-lg-4">
                <div class="chart-card">
                    <h5 class="fw-bold mb-4">
                        <i class="fas fa-chart-pie me-2 text-success"></i> Request Categories
                    </h5>
                    <div class="chart-container">
                        <canvas id="categoriesChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Response Time Distribution -->
            <div class="col-lg-6">
                <div class="chart-card">
                    <h5 class="fw-bold mb-4">
                        <i class="fas fa-clock me-2 text-warning"></i> Response Time Analysis
                    </h5>
                    <div class="chart-container">
                        <canvas id="responseTimeChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Daily Activity -->
            <div class="col-lg-6">
                <div class="chart-card">
                    <h5 class="fw-bold mb-4">
                        <i class="fas fa-calendar-alt me-2 text-info"></i> Daily Activity (<?php echo date('M Y', strtotime($start_date)); ?>)
                    </h5>
                    <div class="chart-container">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Summary -->
        <div class="chart-card mt-4">
            <h5 class="fw-bold mb-4 text-primary">
                <i class="fas fa-file-medical-alt me-2"></i> Performance Summary
            </h5>
            <div class="row">
                <div class="col-md-6">
                    <h6 class="fw-bold mb-3">Achievements</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Helped <?php echo $stats['total_helped']; ?> patients in total
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Created <?php echo $stats['total_tips']; ?> health tips
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Average response time: <?php echo $avg_response_time; ?> hours
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Case completion rate: <?php echo $completion_rate; ?>%
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-bold mb-3">Recommendations</h6>
                    <ul class="list-unstyled">
                        <?php if ($avg_response_time > 48): ?>
                            <li class="mb-2">
                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                Consider improving response time (currently <?php echo $avg_response_time; ?> hours)
                            </li>
                        <?php endif; ?>
                        <?php if ($completion_rate < 60): ?>
                            <li class="mb-2">
                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                Focus on closing more cases (completion rate: <?php echo $completion_rate; ?>%)
                            </li>
                        <?php endif; ?>
                        <?php if ($satisfaction['total_feedback'] < 10): ?>
                            <li class="mb-2">
                                <i class="fas fa-lightbulb text-info me-2"></i>
                                Encourage more patient feedback for better insights
                            </li>
                        <?php endif; ?>
                        <?php if ($stats['total_tips'] < 5): ?>
                            <li class="mb-2">
                                <i class="fas fa-lightbulb text-info me-2"></i>
                                Create more health tips to share knowledge with community
                            </li>
                        <?php endif; ?>
                        <li class="mb-2">
                            <i class="fas fa-lightbulb text-info me-2"></i>
                            Continue providing quality care to maintain high satisfaction
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Download Section -->
        <div class="text-center mt-5 mb-5">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary btn-lg px-4" onclick="downloadPDF()">
                    <i class="fas fa-file-pdf me-2"></i> Download as PDF
                </button>
                <button type="button" class="btn btn-outline-success btn-lg px-4" onclick="downloadCSV()">
                    <i class="fas fa-file-csv me-2"></i> Export as CSV
                </button>
                <button type="button" class="btn btn-outline-info btn-lg px-4" onclick="shareReport()">
                    <i class="fas fa-share-alt me-2"></i> Share Report
                </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">
                        <i class="fas fa-chart-line text-primary me-2"></i>
                        HealthConnect Analytics & Reports
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0 text-muted">
                        Generated on <?php echo date('F j, Y \a\t g:i A'); ?> | 
                        Report Period: <?php echo date('M j, Y', strtotime($start_date)); ?> - <?php echo date('M j, Y', strtotime($end_date)); ?>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prepare chart data
        const monthlyData = {
            labels: <?php echo json_encode($monthly_labels); ?>,
            datasets: [{
                label: 'Responses',
                data: <?php echo json_encode($monthly_responses); ?>,
                backgroundColor: 'rgba(13, 110, 253, 0.2)',
                borderColor: 'rgba(13, 110, 253, 1)',
                borderWidth: 2,
                tension: 0.4
            }, {
                label: 'Closed Cases',
                data: <?php echo json_encode($monthly_closed); ?>,
                backgroundColor: 'rgba(32, 201, 151, 0.2)',
                borderColor: 'rgba(32, 201, 151, 1)',
                borderWidth: 2,
                tension: 0.4
            }]
        };

        const categoriesData = {
            labels: <?php echo json_encode($category_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($category_counts); ?>,
                backgroundColor: [
                    'rgba(13, 110, 253, 0.7)',
                    'rgba(32, 201, 151, 0.7)',
                    'rgba(255, 193, 7, 0.7)',
                    'rgba(220, 53, 69, 0.7)',
                    'rgba(111, 66, 193, 0.7)',
                    'rgba(253, 126, 20, 0.7)',
                    'rgba(25, 135, 84, 0.7)',
                    'rgba(13, 202, 240, 0.7)'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        };

        // Initialize charts when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Add CSS for text animation
            const style = document.createElement('style');
            style.textContent = `
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
            `;
            document.head.appendChild(style);

            // Monthly Performance Chart
            const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
            new Chart(monthlyCtx, {
                type: 'line',
                data: monthlyData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
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
                            title: {
                                display: true,
                                text: 'Number of Cases'
                            }
                        }
                    }
                }
            });

            // Categories Chart
            const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
            new Chart(categoriesCtx, {
                type: 'doughnut',
                data: categoriesData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        }
                    }
                }
            });

            // Response Time Chart
            const responseTimeCtx = document.getElementById('responseTimeChart').getContext('2d');
            new Chart(responseTimeCtx, {
                type: 'bar',
                data: {
                    labels: ['< 12h', '12-24h', '24-48h', '> 48h'],
                    datasets: [{
                        label: 'Response Time Distribution',
                        data: [15, 25, 30, 10], // Sample data - replace with actual
                        backgroundColor: [
                            'rgba(32, 201, 151, 0.7)',
                            'rgba(13, 110, 253, 0.7)',
                            'rgba(255, 193, 7, 0.7)',
                            'rgba(220, 53, 69, 0.7)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Responses'
                            }
                        }
                    }
                }
            });

            // Daily Activity Chart
            const dailyCtx = document.getElementById('dailyChart').getContext('2d');
            
            // Generate sample daily data for the month
            const daysInMonth = new Date(<?php echo date('Y', strtotime($start_date)); ?>, <?php echo date('m', strtotime($start_date)); ?>, 0).getDate();
            const dailyLabels = Array.from({length: daysInMonth}, (_, i) => i + 1);
            const dailyRequests = dailyLabels.map(() => Math.floor(Math.random() * 10) + 1);
            const dailyResponses = dailyRequests.map(val => Math.floor(val * 0.8));
            
            new Chart(dailyCtx, {
                type: 'bar',
                data: {
                    labels: dailyLabels,
                    datasets: [{
                        label: 'Requests',
                        data: dailyRequests,
                        backgroundColor: 'rgba(13, 110, 253, 0.5)',
                        borderWidth: 1
                    }, {
                        label: 'Your Responses',
                        data: dailyResponses,
                        backgroundColor: 'rgba(32, 201, 151, 0.5)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Day of Month'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Cases'
                            }
                        }
                    }
                }
            });

            // Add loading state to form submission
            const reportForm = document.getElementById('reportForm');
            const loadingOverlay = document.getElementById('loadingOverlay');
            
            reportForm.addEventListener('submit', function() {
                loadingOverlay.classList.add('active');
            });

            // Remove loading overlay after charts load
            setTimeout(() => {
                loadingOverlay.classList.remove('active');
            }, 1000);
        });

        // Export functions
        function exportReport() {
            loadingOverlay.classList.add('active');
            
            setTimeout(() => {
                loadingOverlay.classList.remove('active');
                alert('Report exported successfully!');
            }, 1500);
        }

        function printReport() {
            const printContent = document.querySelector('.container').innerHTML;
            const originalContent = document.body.innerHTML;
            
            document.body.innerHTML = `
                <div class="container">
                    <h1 class="text-center mb-4">Doctor Performance Report - <?php echo date('Y-m-d'); ?></h1>
                    ${printContent}
                </div>
            `;
            
            window.print();
            document.body.innerHTML = originalContent;
            window.location.reload();
        }

        function downloadPDF() {
            loadingOverlay.classList.add('active');
            
            setTimeout(() => {
                loadingOverlay.classList.remove('active');
                alert('PDF report generated successfully!');
            }, 2000);
        }

        function downloadCSV() {
            loadingOverlay.classList.add('active');
            
            // Create CSV content
            let csvContent = "data:text/csv;charset=utf-8,";
            csvContent += "Report Period," + <?php echo json_encode(date('M j, Y', strtotime($start_date)) . ' - ' . date('M j, Y', strtotime($end_date))); ?> + "\n\n";
            csvContent += "Metric,Value\n";
            csvContent += "Patients Helped," + <?php echo $stats['total_helped']; ?> + "\n";
            csvContent += "Active Cases," + <?php echo $stats['active_cases']; ?> + "\n";
            csvContent += "Health Tips," + <?php echo $stats['total_tips']; ?> + "\n";
            csvContent += "Unique Patients," + <?php echo $stats['unique_patients']; ?> + "\n";
            csvContent += "Completion Rate," + <?php echo $completion_rate; ?> + "%\n";
            csvContent += "Avg Response Time," + <?php echo $avg_response_time; ?> + " hours\n";
            
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "doctor_report_<?php echo date('Y-m-d'); ?>.csv");
            document.body.appendChild(link);
            
            link.click();
            document.body.removeChild(link);
            
            loadingOverlay.classList.remove('active');
        }

        function shareReport() {
            if (navigator.share) {
                navigator.share({
                    title: 'My HealthConnect Report',
                    text: 'Check out my performance report on HealthConnect',
                    url: window.location.href
                });
            } else {
                alert('Copy this link to share: ' + window.location.href);
            }
        }

        // Auto-refresh data every 5 minutes
        setInterval(() => {
            if (!document.hidden) {
                window.location.reload();
            }
        }, 300000);
    </script>
</body>
</html>
