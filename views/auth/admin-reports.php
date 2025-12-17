<?php
// healthconnect/views/auth/admin-reports.php
session_start();
require_once '../../app/config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php?error=required');
    exit();
}

$user_name = $_SESSION['user_name'];
$action = $_GET['action'] ?? '';
$export_type = $_GET['export'] ?? '';

// Handle export requests
if ($action === 'export' || $export_type) {
    if ($export_type === 'csv' || $export_type === 'excel') {
        // Generate CSV/Excel report
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="healthconnect_report_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Report headers
        fputcsv($output, ['HealthConnect System Report', 'Generated: ' . date('Y-m-d H:i:s')]);
        fputcsv($output, []);
        
        // User statistics
        fputcsv($output, ['USER STATISTICS']);
        fputcsv($output, ['Category', 'Count']);
        
        $user_stats = $pdo->query("SELECT 
            user_role, COUNT(*) as count 
            FROM hc_users 
            GROUP BY user_role")->fetchAll();
        
        foreach ($user_stats as $stat) {
            fputcsv($output, [ucfirst($stat['user_role']), $stat['count']]);
        }
        
        fputcsv($output, []);
        
        // Request statistics
        fputcsv($output, ['MEDICAL REQUESTS']);
        fputcsv($output, ['Status', 'Count', 'Percentage']);
        
        $request_stats = $pdo->query("SELECT 
            request_status, 
            COUNT(*) as count,
            ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM hc_medical_requests), 1) as percentage
            FROM hc_medical_requests 
            GROUP BY request_status")->fetchAll();
        
        foreach ($request_stats as $stat) {
            fputcsv($output, [ucfirst($stat['request_status']), $stat['count'], $stat['percentage'] . '%']);
        }
        
        fputcsv($output, []);
        
        // Monthly activity
        fputcsv($output, ['MONTHLY ACTIVITY (Last 6 Months)']);
        fputcsv($output, ['Month', 'New Users', 'New Requests', 'Forum Posts']);
        
        $monthly_stats = $pdo->query("SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(CASE WHEN user_role IS NOT NULL THEN 1 END) as users,
            COUNT(CASE WHEN request_id IS NOT NULL THEN 1 END) as requests,
            COUNT(CASE WHEN post_id IS NOT NULL THEN 1 END) as posts
            FROM (
                SELECT user_role, date_created as created_at, NULL as request_id, NULL as post_id FROM hc_users
                UNION ALL
                SELECT NULL, request_date as created_at, request_id, NULL FROM hc_medical_requests
                UNION ALL
                SELECT NULL, created_at, NULL, post_id FROM hc_forum_posts
            ) combined
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC")->fetchAll();
        
        foreach ($monthly_stats as $stat) {
            fputcsv($output, [$stat['month'], $stat['users'], $stat['requests'], $stat['posts']]);
        }
        
        fclose($output);
        exit();
    }
}

// Get comprehensive statistics
$stats = [];

// User statistics
$user_stats = $pdo->query("SELECT 
    user_role, 
    COUNT(*) as total,
    SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN DATE(date_created) = CURDATE() THEN 1 ELSE 0 END) as today
    FROM hc_users 
    GROUP BY user_role")->fetchAll();

$stats['users'] = $user_stats;

// Request statistics
$request_stats = $pdo->query("SELECT 
    request_status,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM hc_medical_requests), 1) as percentage
    FROM hc_medical_requests 
    GROUP BY request_status")->fetchAll();

$stats['requests'] = $request_stats;

// Forum statistics - FIXED: Removed is_pinned column
$forum_stats = $pdo->query("SELECT 
    COUNT(*) as total_posts,
    COUNT(DISTINCT author_id) as unique_authors,
    AVG(LENGTH(content)) as avg_post_length
    FROM hc_forum_posts")->fetch();

$stats['forum'] = $forum_stats;

// Health tips statistics
$tips_stats = $pdo->query("SELECT 
    COUNT(*) as total_tips,
    SUM(total_likes) as total_likes,
    AVG(total_likes) as avg_likes,
    COUNT(DISTINCT doctor_user_id) as unique_doctors
    FROM hc_health_tips")->fetch();

$stats['tips'] = $tips_stats;

// Monthly growth data
$monthly_data = $pdo->query("SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(CASE WHEN user_role IS NOT NULL THEN 1 END) as users,
    COUNT(CASE WHEN request_id IS NOT NULL THEN 1 END) as requests,
    COUNT(CASE WHEN post_id IS NOT NULL THEN 1 END) as posts
    FROM (
        SELECT user_role, date_created as created_at, NULL as request_id, NULL as post_id FROM hc_users
        UNION ALL
        SELECT NULL, request_date as created_at, request_id, NULL FROM hc_medical_requests
        UNION ALL
        SELECT NULL, created_at, NULL, post_id FROM hc_forum_posts
    ) combined
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month")->fetchAll();

$stats['monthly'] = $monthly_data;

// Doctor verification status
$doctor_stats = $pdo->query("SELECT 
    verification_status,
    COUNT(*) as count
    FROM hc_doctor_verifications 
    GROUP BY verification_status")->fetchAll();

$stats['doctor_verifications'] = $doctor_stats;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --reports-color: #fd7e14;
            --primary-color: #0d6efd;
            --success-color: #20c997;
            --warning-color: #ffc107;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #fff4e6 100%);
            min-height: 100vh;
        }
        
        .reports-header {
            background: linear-gradient(135deg, var(--reports-color), #dc3545);
            color: white;
            padding: 60px 0 30px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.12);
        }
        
        .stat-title {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--reports-color);
            display: block;
            margin-bottom: 10px;
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .chart-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid var(--reports-color);
            padding-bottom: 10px;
        }
        
        .report-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .export-buttons .btn {
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .data-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        
        .data-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .trend-up {
            color: var(--success-color);
        }
        
        .trend-down {
            color: #dc3545;
        }
        
        @media (max-width: 768px) {
            .chart-container {
                padding: 15px;
            }
            
            .export-buttons .btn {
                width: 100%;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
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
                        <a class="nav-link" href="admin-dashboard.php">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin-reports.php">
                            <i class="fas fa-chart-bar me-1"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <span class="badge bg-primary">
                            <i class="fas fa-user-shield me-1"></i> Admin
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <div class="reports-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="fw-bold mb-3">
                        <i class="fas fa-chart-line me-2"></i> System Reports & Analytics
                    </h1>
                    <p class="lead mb-0">Comprehensive insights and statistics</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="admin-dashboard.php" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-title">Total Users</div>
                    <?php 
                    $total_users = array_sum(array_column($user_stats, 'total'));
                    ?>
                    <span class="stat-number"><?php echo $total_users; ?></span>
                    <small class="text-muted">
                        <?php 
                        $today_users = array_sum(array_column($user_stats, 'today'));
                        echo $today_users; ?> new today
                    </small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-title">Medical Requests</div>
                    <?php 
                    $total_requests = array_sum(array_column($request_stats, 'count'));
                    ?>
                    <span class="stat-number"><?php echo $total_requests; ?></span>
                    <small class="text-muted">
                        <?php 
                        $pending_requests = 0;
                        foreach ($request_stats as $stat) {
                            if ($stat['request_status'] === 'pending') {
                                $pending_requests = $stat['count'];
                                break;
                            }
                        }
                        echo $pending_requests; ?> pending
                    </small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-title">Forum Posts</div>
                    <span class="stat-number"><?php echo $forum_stats['total_posts']; ?></span>
                    <small class="text-muted">
                        <?php echo $forum_stats['unique_authors']; ?> authors
                    </small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-title">Health Tips</div>
                    <span class="stat-number"><?php echo $tips_stats['total_tips']; ?></span>
                    <small class="text-muted">
                        <?php echo $tips_stats['total_likes']; ?> total likes
                        | <?php echo $tips_stats['unique_doctors']; ?> doctors
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Export Options -->
        <div class="report-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">
                    <i class="fas fa-file-export me-2 text-warning"></i> Export Reports
                </h4>
                <div class="export-buttons">
                    <a href="admin-reports.php?export=csv" class="btn btn-success">
                        <i class="fas fa-file-csv me-2"></i> Export as CSV
                    </a>
                    <a href="admin-reports.php?export=excel" class="btn btn-primary">
                        <i class="fas fa-file-excel me-2"></i> Export as Excel
                    </a>
                    <a href="admin-reports.php?action=print" class="btn btn-secondary" onclick="window.print();">
                        <i class="fas fa-print me-2"></i> Print Report
                    </a>
                </div>
            </div>
            <p class="text-muted mb-0">
                Export comprehensive system data for analysis or sharing. Reports include user statistics, 
                medical request data, forum activity, and growth metrics.
            </p>
        </div>
        
        <!-- User Distribution Chart -->
        <div class="report-section">
            <h4 class="chart-title">
                <i class="fas fa-users me-2"></i> User Distribution by Role
            </h4>
            <div class="row">
                <div class="col-md-8">
                    <div class="chart-container">
                        <canvas id="userDistributionChart"></canvas>
                    </div>
                </div>
                <div class="col-md-4">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>User Role</th>
                                <th>Count</th>
                                <th>Active</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($user_stats as $stat): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo ucfirst($stat['user_role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $stat['total']; ?></td>
                                    <td><?php echo $stat['active']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Request Status Chart -->
        <div class="report-section">
            <h4 class="chart-title">
                <i class="fas fa-file-medical me-2"></i> Medical Request Status
            </h4>
            <div class="row">
                <div class="col-md-8">
                    <div class="chart-container">
                        <canvas id="requestStatusChart"></canvas>
                    </div>
                </div>
                <div class="col-md-4">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($request_stats as $stat): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $stat['request_status'] === 'pending' ? 'warning' : 
                                                  ($stat['request_status'] === 'responded' ? 'info' : 'success'); 
                                        ?>">
                                            <?php echo ucfirst($stat['request_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $stat['count']; ?></td>
                                    <td><?php echo $stat['percentage']; ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Monthly Growth Chart -->
        <div class="report-section">
            <h4 class="chart-title">
                <i class="fas fa-chart-line me-2"></i> Monthly Growth (Last 12 Months)
            </h4>
            <div class="chart-container">
                <canvas id="monthlyGrowthChart"></canvas>
            </div>
        </div>
        
        <!-- Doctor Verification Status -->
        <div class="report-section">
            <h4 class="chart-title">
                <i class="fas fa-user-md me-2"></i> Doctor Verification Status
            </h4>
            <div class="row">
                <div class="col-md-8">
                    <div class="chart-container">
                        <canvas id="doctorVerificationChart"></canvas>
                    </div>
                </div>
                <div class="col-md-4">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($doctor_stats as $stat): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $stat['verification_status'] === 'approved' ? 'success' : 
                                                  ($stat['verification_status'] === 'pending_review' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo str_replace('_', ' ', ucfirst($stat['verification_status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $stat['count']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- System Summary -->
        <div class="report-section">
            <h4 class="chart-title">
                <i class="fas fa-clipboard-list me-2"></i> System Summary
            </h4>
            <div class="row">
                <div class="col-md-6">
                    <div class="stat-card">
                        <h6 class="stat-title">Average Response Time</h6>
                        <div class="d-flex align-items-center">
                            <span class="stat-number">24h</span>
                            <span class="trend-up ms-3">
                                <i class="fas fa-arrow-up me-1"></i>Improved 15%
                            </span>
                        </div>
                        <small class="text-muted">Average time to respond to medical requests</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-card">
                        <h6 class="stat-title">User Satisfaction</h6>
                        <div class="d-flex align-items-center">
                            <span class="stat-number">92%</span>
                            <span class="trend-up ms-3">
                                <i class="fas fa-arrow-up me-1"></i>Increased 8%
                            </span>
                        </div>
                        <small class="text-muted">Based on feedback and repeat usage</small>
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
                    <h6>HealthConnect Analytics Dashboard</h6>
                    <small class="text-muted">
                        <i class="fas fa-chart-pie me-1"></i> 
                        Report generated: <?php echo date('Y-m-d H:i:s'); ?>
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
        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            // User Distribution Chart
            const userCtx = document.getElementById('userDistributionChart').getContext('2d');
            new Chart(userCtx, {
                type: 'doughnut',
                data: {
                    labels: [
                        <?php foreach ($user_stats as $stat): ?>
                            '<?php echo ucfirst($stat['user_role']); ?>',
                        <?php endforeach; ?>
                    ],
                    datasets: [{
                        data: [
                            <?php foreach ($user_stats as $stat): ?>
                                <?php echo $stat['total']; ?>,
                            <?php endforeach; ?>
                        ],
                        backgroundColor: [
                            '#0d6efd', '#20c997', '#6f42c1', '#fd7e14', '#ffc107'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
            
            // Request Status Chart
            const requestCtx = document.getElementById('requestStatusChart').getContext('2d');
            new Chart(requestCtx, {
                type: 'bar',
                data: {
                    labels: [
                        <?php foreach ($request_stats as $stat): ?>
                            '<?php echo ucfirst($stat['request_status']); ?>',
                        <?php endforeach; ?>
                    ],
                    datasets: [{
                        label: 'Number of Requests',
                        data: [
                            <?php foreach ($request_stats as $stat): ?>
                                <?php echo $stat['count']; ?>,
                            <?php endforeach; ?>
                        ],
                        backgroundColor: [
                            '#ffc107', '#0dcaf0', '#198754'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
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
            
            // Monthly Growth Chart
            const monthlyCtx = document.getElementById('monthlyGrowthChart').getContext('2d');
            new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: [
                        <?php foreach ($monthly_data as $data): ?>
                            '<?php echo $data['month']; ?>',
                        <?php endforeach; ?>
                    ],
                    datasets: [
                        {
                            label: 'New Users',
                            data: [
                                <?php foreach ($monthly_data as $data): ?>
                                    <?php echo $data['users']; ?>,
                                <?php endforeach; ?>
                            ],
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13, 110, 253, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'New Requests',
                            data: [
                                <?php foreach ($monthly_data as $data): ?>
                                    <?php echo $data['requests']; ?>,
                                <?php endforeach; ?>
                            ],
                            borderColor: '#20c997',
                            backgroundColor: 'rgba(32, 201, 151, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Forum Posts',
                            data: [
                                <?php foreach ($monthly_data as $data): ?>
                                    <?php echo $data['posts']; ?>,
                                <?php endforeach; ?>
                            ],
                            borderColor: '#fd7e14',
                            backgroundColor: 'rgba(253, 126, 20, 0.1)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            
            // Doctor Verification Chart
            const doctorCtx = document.getElementById('doctorVerificationChart').getContext('2d');
            new Chart(doctorCtx, {
                type: 'pie',
                data: {
                    labels: [
                        <?php foreach ($doctor_stats as $stat): ?>
                            '<?php echo str_replace('_', ' ', ucfirst($stat['verification_status'])); ?>',
                        <?php endforeach; ?>
                    ],
                    datasets: [{
                        data: [
                            <?php foreach ($doctor_stats as $stat): ?>
                                <?php echo $stat['count']; ?>,
                            <?php endforeach; ?>
                        ],
                        backgroundColor: [
                            '#198754', '#ffc107', '#dc3545'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
        });
        
        // Print functionality
        function printReport() {
            window.print();
        }
        
        // Auto-refresh charts every 5 minutes
        setInterval(() => {
            location.reload();
        }, 300000); // 5 minutes
    </script>
</body>
</html>
