<?php
// healthconnect/views/auth/training.php
session_start();
require_once '../../app/config/database.php';  // ← CHANGED from ../../../ to ../../

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];

// Only volunteers and doctors can access training
if (!in_array($user_role, ['volunteer', 'doctor'])) {
    header("Location: {$user_role}-dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .training-module {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .training-module:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .module-completed {
            border-left: 4px solid #198754;
        }
        .module-in-progress {
            border-left: 4px solid #0dcaf0;
        }
        .module-pending {
            border-left: 4px solid #ffc107;
        }
        .progress-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Simple Navigation -->
    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">
                <i class="fas fa-heartbeat text-primary"></i> HealthConnect
            </a>
            <div>
                <a href="<?php echo $user_role; ?>-dashboard.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-graduation-cap"></i> Essential Training
                        </h4>
                        <p class="mb-0 mt-2 small">Complete these modules to become a certified volunteer</p>
                    </div>
                    
                    <div class="card-body">
                        <!-- Training Progress -->
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Progress:</strong> 3 of 5 modules completed (60%)
                            <div class="progress mt-2" style="height: 10px;">
                                <div class="progress-bar bg-success" style="width: 60%"></div>
                            </div>
                        </div>

                        <!-- Training Modules -->
                        <div class="training-module module-completed">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="fw-bold">1. Introduction to HealthConnect</h5>
                                    <p class="text-muted mb-2">Learn about our mission and your role</p>
                                    <small><i class="fas fa-clock"></i> 15 min • <i class="fas fa-check-circle text-success"></i> Completed</small>
                                </div>
                                <div class="progress-circle bg-success">✓</div>
                            </div>
                        </div>

                        <div class="training-module module-completed">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="fw-bold">2. Medical Ethics & Confidentiality</h5>
                                    <p class="text-muted mb-2">Understand patient privacy and professional conduct</p>
                                    <small><i class="fas fa-clock"></i> 20 min • <i class="fas fa-check-circle text-success"></i> Completed</small>
                                </div>
                                <div class="progress-circle bg-success">✓</div>
                            </div>
                        </div>

                        <div class="training-module module-completed">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="fw-bold">3. Basic First Aid Training</h5>
                                    <p class="text-muted mb-2">Essential first aid procedures</p>
                                    <small><i class="fas fa-clock"></i> 30 min • <i class="fas fa-check-circle text-success"></i> Completed</small>
                                </div>
                                <div class="progress-circle bg-success">✓</div>
                            </div>
                        </div>

                        <div class="training-module module-in-progress">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="fw-bold">4. Common Conditions Guide</h5>
                                    <p class="text-muted mb-2">Recognize and advise on common health issues</p>
                                    <small><i class="fas fa-clock"></i> 25 min • <i class="fas fa-play-circle text-info"></i> In Progress</small>
                                </div>
                                <a href="#" class="btn btn-info btn-sm">
                                    <i class="fas fa-play"></i> Continue
                                </a>
                            </div>
                        </div>

                        <div class="training-module module-pending">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="fw-bold">5. Effective Communication</h5>
                                    <p class="text-muted mb-2">How to communicate with patients remotely</p>
                                    <small><i class="fas fa-clock"></i> 20 min • <i class="fas fa-lock text-warning"></i> Locked</small>
                                </div>
                                <button class="btn btn-outline-secondary btn-sm" disabled>
                                    <i class="fas fa-lock"></i> Locked
                                </button>
                            </div>
                        </div>

                        <!-- Certificate Info -->
                        <div class="alert alert-success mt-4">
                            <h5><i class="fas fa-award"></i> Certification</h5>
                            <p class="mb-2">Complete all 5 modules to earn your HealthConnect Volunteer Certificate.</p>
                            <small>Certificate valid for 1 year, renewable with refresher courses.</small>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="d-grid gap-2 mt-4">
                            <a href="resources.php" class="btn btn-success">
                                <i class="fas fa-book-medical me-2"></i> View Medical Resources
                            </a>
                            <a href="profile.php" class="btn btn-outline-primary">
                                <i class="fas fa-user-edit me-2"></i> Update Your Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>