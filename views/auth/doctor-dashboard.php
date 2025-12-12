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
if (!$_SESSION['is_approved']) {
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
               r.request_date, u.full_name as patient_name, u.location
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        :root {
            --doctor-primary: #0d6efd;
            --doctor-secondary: #052c65;
            --doctor-accent: #20c997;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, var(--doctor-primary) 0%, var(--doctor-secondary) 100%);
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
        
        .stat-card.patients::before { background: linear-gradient(90deg, #0d6efd, #0dcaf0); }
        .stat-card.pending::before { background: linear-gradient(90deg, #ffc107, #ff9800); }
        .stat-card.tips::before { background: linear-gradient(90deg, #20c997, #198754); }
        .stat-card.active::before { background: linear-gradient(90deg, #6f42c1, #d63384); }
        
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
        
        .stat-card.patients .stat-icon { background: rgba(13, 110, 253, 0.1); color: #0d6efd; }
        .stat-card.pending .stat-icon { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .stat-card.tips .stat-icon { background: rgba(32, 201, 151, 0.1); color: #20c997; }
        .stat-card.active .stat-icon { background: rgba(111, 66, 193, 0.1); color: #6f42c1; }
        
        .request-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border-left: 4px solid var(--doctor-primary);
        }
        
        .request-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .urgency-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .urgency-high { background: #f8d7da; color: #842029; }
        .urgency-medium { background: #fff3cd; color: #664d03; }
        .urgency-low { background: #d1e7dd; color: #0f5132; }
        
        .doctor-badge {
            background: linear-gradient(135deg, var(--doctor-primary), var(--doctor-secondary));
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .action-btn {
            min-width: 120px;
        }
        
        .profile-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--doctor-primary), var(--doctor-secondary));
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
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a></li>
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
                        <h1 class="fw-bold mb-3">Welcome, Dr. <?php echo htmlspecialchars($user_name); ?>! 👨‍⚕️👩‍⚕️</h1>
                        <p class="lead mb-0">Thank you for helping bridge the healthcare gap in rural communities.</p>
                    </div>
                </div>
                <div class="col-lg-4 text-end">
                    <span class="doctor-badge me-3">
                        <i class="fas fa-badge-check me-1"></i> Verified Doctor
                    </span>
                    <a href="respond-requests.php" class="btn btn-light btn-lg px-4 shadow">
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
                    <h2 class="fw-bold mb-2"><?php echo $stats['total_helped'] ?? 0; ?></h2>
                    <p class="text-muted mb-0">Patients Helped</p>
                    <small class="text-primary">Lifetime assistance</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card pending p-4 text-center">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h2 class="fw-bold mb-2"><?php echo $stats['pending_requests'] ?? 0; ?></h2>
                    <p class="text-muted mb-0">Pending Requests</p>
                    <small class="text-warning">Need your help</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card tips p-4 text-center">
                    <div class="stat-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h2 class="fw-bold mb-2"><?php echo $stats['total_tips'] ?? 0; ?></h2>
                    <p class="text-muted mb-0">Health Tips</p>
                    <small class="text-success">Shared with community</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card active p-4 text-center">
                    <div class="stat-icon">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                    <h2 class="fw-bold mb-2"><?php echo $stats['active_cases'] ?? 0; ?></h2>
                    <p class="text-muted mb-0">Active Cases</p>
                    <small class="text-purple">Currently assisting</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="row">
            <!-- Pending Requests -->
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0 py-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-0"><i class="fas fa-hourglass-half text-warning me-2"></i> Pending Requests</h4>
                                <p class="text-muted mb-0 mt-1">Patients waiting for assistance</p>
                            </div>
                            <a href="respond-requests.php" class="btn btn-warning">
                                <i class="fas fa-hands-helping me-1"></i> View All
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <?php if (empty($pending_requests)): ?>
                            <div class="text-center py-4">
                                <div class="mb-4">
                                    <i class="fas fa-check-circle fa-3x text-success"></i>
                                </div>
                                <h5>No pending requests!</h5>
                                <p class="text-muted mb-0">All current requests have been responded to.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($pending_requests as $request): ?>
                                <div class="request-card mb-3 p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($request['request_title']); ?></h6>
                                        <span class="urgency-badge urgency-medium">Medium</span>
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
                                        <a href="view-request.php?id=<?php echo $request['request_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary action-btn">
                                            <i class="fas fa-comment-medical me-1"></i> Respond
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- My Recent Responses -->
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 mb-4">
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
                                <a href="respond-requests.php" class="btn btn-primary mt-3">
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
                                                    <small><?php echo date('M d', strtotime($response['response_date'])); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $response['request_status'] === 'closed' ? 'success' : 'info'; ?>">
                                                        <?php echo ucfirst($response['request_status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <a href="my-responses.php" class="btn btn-outline-primary btn-sm">
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
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0 py-4">
                        <h4 class="mb-0"><i class="fas fa-bolt text-success me-2"></i> Quick Actions</h4>
                        <p class="text-muted mb-0 mt-1">Frequently used features</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6">
                                <a href="respond-requests.php" class="card action-card text-decoration-none">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-comments-medical fa-3x text-primary"></i>
                                        </div>
                                        <h6 class="fw-bold mb-2">Respond to Requests</h6>
                                        <p class="text-muted small mb-0">Help patients with medical advice</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="create-tip.php" class="card action-card text-decoration-none">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-lightbulb fa-3x text-warning"></i>
                                        </div>
                                        <h6 class="fw-bold mb-2">Create Health Tip</h6>
                                        <p class="text-muted small mb-0">Share medical knowledge</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="patient-directory.php" class="card action-card text-decoration-none">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-users fa-3x text-info"></i>
                                        </div>
                                        <h6 class="fw-bold mb-2">Patient Directory</h6>
                                        <p class="text-muted small mb-0">View your patients</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="reports.php" class="card action-card text-decoration-none">
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
        // Action card hover effect
        document.querySelectorAll('.action-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 10px 25px rgba(0,0,0,0.1)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });
        
        // Auto-update pending requests count
        function updatePendingCount() {
            fetch('../../api/doctor.php?action=get_pending_count')
                .then(response => response.json())
                .then(data => {
                    if (data.count > 0) {
                        document.querySelector('.stat-card.pending h2').textContent = data.count;
                    }
                })
                .catch(error => console.error('Update error:', error));
        }
        
        // Update every 60 seconds
        setInterval(updatePendingCount, 60000);
    </script>
</body>
</html>