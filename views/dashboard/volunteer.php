<?php
// healthconnect/views/dashboard/volunteer.php
session_start();
require_once '../../app/config/database.php';

// Check if user is logged in and is a volunteer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'volunteer') {
    header('Location: ../auth/login.php?error=required');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

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

// Get volunteer's responded requests
$sql = "SELECT r.request_id, r.request_title, r.request_status, 
               r.request_date, r.response_date,
               u.full_name as patient_name
        FROM hc_medical_requests r
        JOIN hc_users u ON r.patient_id = u.user_id
        WHERE r.responded_by_user_id = :volunteer_id
        ORDER BY r.response_date DESC
        LIMIT 10";
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
        .dashboard-header {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }
        .urgent {
            border-left: 4px solid #e74c3c;
        }
        .new {
            border-left: 4px solid #3498db;
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
                    <li class="nav-item"><a class="nav-link active" href="volunteer.php"><i class="fas fa-home me-1"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="pending-requests.php"><i class="fas fa-inbox me-1"></i> Pending Requests</a></li>
                    <li class="nav-item"><a class="nav-link" href="medical-tips.php"><i class="fas fa-lightbulb me-1"></i> Health Tips</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($user_name); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
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
                    <h1 class="fw-bold mb-3">Welcome, Volunteer <?php echo htmlspecialchars($user_name); ?>!</h1>
                    <p class="lead mb-0">Thank you for helping rural communities access healthcare.</p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-light text-dark fs-6">
                        <i class="fas fa-hands-helping me-2"></i> Volunteer
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
                <div class="card text-center p-4 bg-success text-white">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <h3><?php echo count($pending_requests); ?></h3>
                    <p class="mb-0">Pending Requests</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card text-center p-4 bg-primary text-white">
                    <i class="fas fa-check-circle fa-3x mb-3"></i>
                    <h3><?php echo count($my_responses); ?></h3>
                    <p class="mb-0">My Responses</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card text-center p-4 bg-warning text-dark">
                    <i class="fas fa-clock fa-3x mb-3"></i>
                    <h3>0</h3>
                    <p class="mb-0">Urgent Cases</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card text-center p-4 bg-info text-white">
                    <i class="fas fa-users fa-3x mb-3"></i>
                    <h3><?php echo count($pending_requests); ?></h3>
                    <p class="mb-0">Patients Waiting</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Pending Requests -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-clock me-2"></i> Recent Pending Requests</h5>
                        <a href="pending-requests.php" class="btn btn-success btn-sm">
                            <i class="fas fa-eye me-1"></i> View All
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pending_requests)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h5>No pending requests!</h5>
                                <p class="text-muted">All requests have been responded to.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($pending_requests, 0, 3) as $request): ?>
                                <div class="card mb-3 new">
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
                                                <a href="respond-request.php?id=<?php echo $request['request_id']; ?>" class="btn btn-success btn-sm">
                                                    <i class="fas fa-reply me-1"></i> Respond
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center">
                                <a href="pending-requests.php" class="btn btn-outline-success">View All Pending Requests (<?php echo count($pending_requests); ?>)</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- My Recent Responses -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i> My Recent Responses</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($my_responses)): ?>
                            <p class="text-muted text-center py-3">You haven't responded to any requests yet.</p>
                        <?php else: ?>
                            <?php foreach ($my_responses as $response): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($response['request_title']); ?></h6>
                                        <small class="text-muted">
                                            Patient: <?php echo htmlspecialchars($response['patient_name']); ?>
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            Status: <span class="badge bg-success"><?php echo ucfirst($response['request_status']); ?></span>
                                        </small>
                                    </div>
                                    <a href="view-response.php?id=<?php echo $response['request_id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center">
                                <a href="my-responses.php" class="btn btn-outline-primary btn-sm">View All Responses</a>
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
                            <a href="pending-requests.php" class="btn btn-success">
                                <i class="fas fa-inbox me-2"></i> View Pending Requests
                            </a>
                            <a href="medical-tips.php" class="btn btn-outline-success">
                                <i class="fas fa-lightbulb me-2"></i> Browse Health Tips
                            </a>
                            <a href="profile.php" class="btn btn-outline-primary">
                                <i class="fas fa-user me-2"></i> Update Profile
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
                    <p class="mb-0">Volunteer Dashboard</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>