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
        .admin-nav {
            background: linear-gradient(135deg, #6f42c1 0%, #4a1fb8 100%);
            color: white;
            padding: 15px 0;
        }
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            color: white;
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card.patients { background: linear-gradient(135deg, #0dcaf0, #0d6efd); }
        .stat-card.volunteers { background: linear-gradient(135deg, #20c997, #198754); }
        .stat-card.doctors { background: linear-gradient(135deg, #6f42c1, #d63384); }
        .stat-card.requests { background: linear-gradient(135deg, #ffc107, #fd7e14); }
        .admin-badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
        }
        .action-btn {
            min-width: 100px;
        }
        .quick-action-card {
            transition: all 0.3s;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .quick-action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        .pending-badge {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 12px;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <!-- Simple Navigation -->
    <nav class="admin-nav">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">
                        <i class="fas fa-heartbeat me-2"></i> HealthConnect Admin
                    </h4>
                    <small>Welcome, <?php echo htmlspecialchars($user_name); ?></small>
                </div>
                <div>
                    <span class="admin-badge me-3">
                        <i class="fas fa-user-shield me-1"></i> Administrator
                    </span>
                    <a href="logout.php" class="btn btn-light btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Quick Stats -->
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card patients">
                    <h3><?php echo $stats['patients']; ?></h3>
                    <p class="mb-0">Patients</p>
                    <small>Registered users</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card volunteers">
                    <h3><?php echo $stats['volunteers']; ?></h3>
                    <p class="mb-0">Volunteers</p>
                    <small>Active helpers</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card doctors">
                    <h3><?php echo $stats['doctors']; ?></h3>
                    <p class="mb-0">Doctors</p>
                    <small>Verified</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card requests">
                    <h3><?php echo $stats['total_requests']; ?></h3>
                    <p class="mb-0">Requests</p>
                    <small>Total submissions</small>
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
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="admin-doctors.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-user-md me-2 text-warning"></i>
                                    Approve Doctors
                                </div>
                                <?php if ($stats['pending_doctors'] > 0): ?>
                                    <span class="pending-badge"><?php echo $stats['pending_doctors']; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="admin-users.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-users me-2 text-primary"></i>
                                Manage Users
                            </a>
                            <a href="admin-requests.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-file-medical me-2 text-info"></i>
                                    View Requests
                                </div>
                                <?php if ($stats['pending_requests'] > 0): ?>
                                    <span class="pending-badge"><?php echo $stats['pending_requests']; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="admin-tips.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-lightbulb me-2 text-success"></i>
                                Manage Health Tips
                            </a>
                            <a href="admin-resources.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-book-medical me-2 text-purple"></i>
                                Upload Training Resources
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Middle Column: Pending Doctors -->
            <div class="col-md-4">
                <div class="card shadow-sm mb-4 quick-action-card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-user-clock me-2"></i> 
                            Doctor Applications
                            <?php if (!empty($pending_doctors)): ?>
                                <span class="pending-badge"><?php echo count($pending_doctors); ?></span>
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pending_doctors)): ?>
                            <p class="text-muted text-center mb-0">
                                <i class="fas fa-check-circle text-success"></i> 
                                No pending applications
                            </p>
                        <?php else: ?>
                            <?php foreach ($pending_doctors as $doctor): ?>
                                <div class="border-bottom pb-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="fw-bold mb-1">Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></h6>
                                            <small class="text-muted d-block">
                                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($doctor['email_address']); ?>
                                            </small>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar"></i> Applied: <?php echo date('M d, Y', strtotime($doctor['date_created'])); ?>
                                            </small>
                                        </div>
                                        <a href="admin-doctors.php?highlight=<?php echo $doctor['user_id']; ?>" 
                                           class="btn btn-sm btn-warning action-btn">
                                            <i class="fas fa-eye me-1"></i> Review
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-3">
                                <a href="admin-doctors.php" class="btn btn-warning btn-sm">
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
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i> Recent Health Tips</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_tips)): ?>
                            <p class="text-muted text-center mb-3">No health tips yet</p>
                            <div class="text-center">
                                <a href="admin-tips.php?action=create" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus me-1"></i> Create First Tip
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_tips as $tip): ?>
                                <div class="border-bottom pb-2 mb-2">
                                    <h6 class="fw-bold mb-1">
                                        <i class="fas fa-sticky-note text-success me-1"></i>
                                        <?php echo htmlspecialchars($tip['tip_title']); ?>
                                    </h6>
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i> 
                                        <?php echo date('M d, Y', strtotime($tip['tip_date'])); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-3">
                                <a href="admin-tips.php" class="btn btn-success btn-sm">
                                    <i class="fas fa-cogs me-1"></i> Manage All Tips
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>