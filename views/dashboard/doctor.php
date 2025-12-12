<?php
// healthconnect/views/dashboard/doctor.php
session_start();
require_once '../../app/config/database.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header('Location: ../auth/login.php?error=required');
    exit();
}

// Check if doctor is approved
if (!$_SESSION['is_approved']) {
    header('Location: pending-approval.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get doctor's verification status
$sql = "SELECT verification_status FROM hc_doctor_verifications 
        WHERE doctor_user_id = :doctor_id 
        ORDER BY submission_date DESC LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([':doctor_id' => $user_id]);
$verification = $stmt->fetch();

// Get pending requests
$sql = "SELECT r.request_id, r.request_title, r.request_description, 
               r.request_date, r.patient_location,
               u.full_name as patient_name
        FROM hc_medical_requests r
        JOIN hc_users u ON r.patient_id = u.user_id
        WHERE r.request_status = 'pending'
        ORDER BY r.request_date ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$pending_requests = $stmt->fetchAll();

// Get doctor's responses
$sql = "SELECT r.request_id, r.request_title, r.request_status, 
               r.request_date, r.response_date,
               u.full_name as patient_name
        FROM hc_medical_requests r
        JOIN hc_users u ON r.patient_id = u.user_id
        WHERE r.responded_by_user_id = :doctor_id
        ORDER BY r.response_date DESC
        LIMIT 10";
$stmt = $pdo->prepare($sql);
$stmt->execute([':doctor_id' => $user_id]);
$my_responses = $stmt->fetchAll();

// Get doctor's health tips
$sql = "SELECT tip_id, tip_title, total_likes, tip_date 
        FROM hc_health_tips 
        WHERE doctor_user_id = :doctor_id
        ORDER BY tip_date DESC
        LIMIT 5";
$stmt = $pdo->prepare($sql);
$stmt->execute([':doctor_id' => $user_id]);
$my_tips = $stmt->fetchAll();
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
        .dashboard-header {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }
        .verified-badge {
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
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
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="doctor.php"><i class="fas fa-home me-1"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="pending-requests.php"><i class="fas fa-inbox me-1"></i> Medical Requests</a></li>
                    <li class="nav-item"><a class="nav-link" href="health-tips.php"><i class="fas fa-lightbulb me-1"></i> My Health Tips</a></li>
                    <li class="nav-item"><a class="nav-link" href="create-tip.php"><i class="fas fa-plus-circle me-1"></i> Create Tip</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-md me-1"></i> Dr. <?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="verification.php"><i class="fas fa-id-card me-2"></i> Verification</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
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
                <div class="col-md-8">
                    <h1 class="fw-bold mb-2">Welcome, Dr. <?php echo htmlspecialchars($user_name); ?>!</h1>
                    <p class="lead mb-0">Thank you for providing medical expertise to rural communities.</p>
                    <?php if ($verification && $verification['verification_status'] === 'approved'): ?>
                        <span class="verified-badge mt-2 d-inline-block">
                            <i class="fas fa-check-circle me-1"></i> Verified Medical Professional
                        </span>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-light text-dark fs-6">
                        <i class="fas fa-user-md me-2"></i> Medical Doctor
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Statistics -->
        <div class="row mb-5">
            <div class="col-md-3 mb-4">
                <div class="card text-center p-4 bg-primary text-white">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <h3><?php echo count($pending_requests); ?></h3>
                    <p class="mb-0">Pending Requests</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card text-center p-4 bg-success text-white">
                    <i class="fas fa-comment-medical fa-3x mb-3"></i>
                    <h3><?php echo count($my_responses); ?></h3>
                    <p class="mb-0">My Responses</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card text-center p-4 bg-info text-white">
                    <i class="fas fa-lightbulb fa-3x mb-3"></i>
                    <h3><?php echo count($my_tips); ?></h3>
                    <p class="mb-0">Health Tips</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card text-center p-4 bg-warning text-dark">
                    <i class="fas fa-heart fa-3x mb-3"></i>
                    <h3>
                        <?php 
                        $total_likes = array_sum(array_column($my_tips, 'total_likes'));
                        echo $total_likes;
                        ?>
                    </h3>
                    <p class="mb-0">Tip Likes</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Pending Medical Requests -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-stethoscope me-2"></i> Medical Requests Needing Attention</h5>
                        <a href="pending-requests.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-eye me-1"></i> View All
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pending_requests)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h5>No pending medical requests!</h5>
                                <p class="text-muted">All requests have been addressed.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($pending_requests, 0, 3) as $request): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h6 class="card-title"><?php echo htmlspecialchars($request['request_title']); ?></h6>
                                        <p class="card-text small text-muted">
                                            <?php echo substr(htmlspecialchars($request['request_description']), 0, 150); ?>...
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <small class="text-muted">
                                                    <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($request['patient_name']); ?>
                                                    <?php if ($request['patient_location']): ?>
                                                        <i class="fas fa-map-marker-alt ms-3 me-1"></i> <?php echo htmlspecialchars($request['patient_location']); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <div>
                                                <a href="respond-request.php?id=<?php echo $request['request_id']; ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-comment-medical me-1"></i> Provide Medical Advice
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center">
                                <a href="pending-requests.php" class="btn btn-outline-primary">View All Medical Requests (<?php echo count($pending_requests); ?>)</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Doctor's Sidebar -->
            <div class="col-lg-4 mb-4">
                <!-- My Health Tips -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i> My Recent Health Tips</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($my_tips)): ?>
                            <p class="text-muted text-center py-3">You haven't created any health tips yet.</p>
                            <div class="text-center">
                                <a href="create-tip.php" class="btn btn-outline-success btn-sm">Create Your First Tip</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($my_tips as $tip): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($tip['tip_title']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y', strtotime($tip['tip_date'])); ?>
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-heart text-danger me-1"></i> <?php echo $tip['total_likes']; ?> likes
                                        </small>
                                    </div>
                                    <div>
                                        <a href="edit-tip.php?id=<?php echo $tip['tip_id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="../medical-tips/view.php?id=<?php echo $tip['tip_id']; ?>" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center">
                                <a href="health-tips.php" class="btn btn-outline-success btn-sm">View All Tips</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="create-tip.php" class="btn btn-success">
                                <i class="fas fa-plus-circle me-2"></i> Create Health Tip
                            </a>
                            <a href="pending-requests.php" class="btn btn-primary">
                                <i class="fas fa-stethoscope me-2"></i> Review Medical Requests
                            </a>
                            <a href="profile.php" class="btn btn-outline-primary">
                                <i class="fas fa-user-md me-2"></i> Doctor Profile
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
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2025 HealthConnect. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0">Doctor Dashboard</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>