<?php
// healthconnect/views/auth/pending-approval.php
session_start();
require_once '../../app/config/database.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header('Location: login.php?error=required');
    exit();
}

// If doctor is already approved, redirect to dashboard
if ($_SESSION['is_approved']) {
    header('Location: doctor-dashboard.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get verification status
$sql = "SELECT verification_status, submission_date, admin_notes 
        FROM hc_doctor_verifications 
        WHERE doctor_user_id = :doctor_id 
        ORDER BY submission_date DESC LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([':doctor_id' => $user_id]);
$verification = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Approval - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .approval-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #f5f7ff 0%, #e3e9ff 100%);
            padding: 40px 0;
        }
        
        .approval-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .approval-header {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
        }
        
        .logout-btn-top {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .logout-btn-top:hover {
            background: rgba(255,255,255,0.1);
            border-color: white;
        }
        
        .status-step {
            display: flex;
            align-items: center;
            margin: 30px 0;
            position: relative;
        }
        
        .status-step::before {
            content: '';
            position: absolute;
            top: 25px;
            left: 25px;
            right: 25px;
            height: 2px;
            background: #e9ecef;
            z-index: 1;
        }
        
        .step-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            position: relative;
            z-index: 2;
            margin-right: 20px;
        }
        
        .step-active {
            background: #ffc107;
            color: white;
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
        }
        
        .step-completed {
            background: #28a745;
            color: white;
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        
        .step-pending {
            background: #e9ecef;
            color: #6c757d;
        }
        
        .status-info {
            flex: 1;
        }
        
        .timeline {
            position: relative;
            padding: 20px 0;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 30px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }
        
        .timeline-item {
            position: relative;
            padding-left: 60px;
            margin-bottom: 30px;
        }
        
        .timeline-icon {
            position: absolute;
            left: 0;
            top: 0;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border: 2px solid #e9ecef;
            font-size: 24px;
        }
        
        .status-badge {
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-pending {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        
        .logout-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
            text-align: center;
        }
        
        .logout-options {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }
        
        .logout-btn {
            min-width: 150px;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="../../index.php">
                <i class="fas fa-heartbeat me-2"></i>HealthConnect
            </a>
            <div>
                <span class="text-muted me-3">
                    <i class="fas fa-user-md me-1"></i>Dr. <?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?>
                </span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>
    
    <div class="approval-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="approval-card">
                        <div class="approval-header">
                            <!-- Logout Button Top Right -->
                            <a href="logout.php" class="logout-btn-top">
                                <i class="fas fa-sign-out-alt me-1"></i>Logout
                            </a>
                            
                            <div class="mb-4">
                                <i class="fas fa-user-md fa-4x text-white mb-3"></i>
                                <h2 class="fw-bold mb-3">Account Under Review</h2>
                                <p class="lead mb-0">Welcome, Dr. <?php echo htmlspecialchars($user_name); ?></p>
                            </div>
                        </div>
                        
                        <div class="card-body p-4 p-md-5">
                            <div class="alert alert-info mb-4">
                                <div class="d-flex">
                                    <i class="fas fa-info-circle fa-2x me-3 text-info"></i>
                                    <div>
                                        <h5 class="mb-2">Your account is being reviewed</h5>
                                        <p class="mb-0">
                                            Thank you for registering as a medical professional. Our admin team is reviewing 
                                            your credentials to ensure the highest quality of healthcare services.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Status Steps -->
                            <div class="status-step">
                                <div class="step-circle step-completed">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="status-info">
                                    <h5 class="fw-bold mb-1">Registration Completed</h5>
                                    <p class="text-muted mb-0">Your account has been created successfully</p>
                                    <small class="text-success">Completed on <?php echo date('M d, Y'); ?></small>
                                </div>
                            </div>
                            
                            <div class="status-step">
                                <div class="step-circle step-active">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </div>
                                <div class="status-info">
                                    <h5 class="fw-bold mb-1">Verification in Progress</h5>
                                    <p class="text-muted mb-0">Our team is reviewing your medical credentials</p>
                                    <small class="text-warning">Currently in review</small>
                                </div>
                            </div>
                            
                            <div class="status-step">
                                <div class="step-circle step-pending">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="status-info">
                                    <h5 class="fw-bold mb-1">Approval Notification</h5>
                                    <p class="text-muted mb-0">You will receive an email once approved</p>
                                </div>
                            </div>
                            
                            <!-- Current Status -->
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="fw-bold mb-0">Current Status</h5>
                                        <span class="status-badge badge-pending">
                                            <i class="fas fa-clock me-1"></i> Pending Review
                                        </span>
                                    </div>
                                    
                                    <?php if ($verification): ?>
                                        <div class="timeline">
                                            <div class="timeline-item">
                                                <div class="timeline-icon bg-primary text-white">
                                                    <i class="fas fa-file-upload"></i>
                                                </div>
                                                <h6 class="fw-bold">Document Submitted</h6>
                                                <p class="text-muted small mb-0">
                                                    Your medical certificate has been uploaded for verification
                                                </p>
                                                <small class="text-muted">
                                                    <?php echo date('M d, Y H:i', strtotime($verification['submission_date'])); ?>
                                                </small>
                                            </div>
                                            
                                            <?php if ($verification['admin_notes']): ?>
                                                <div class="timeline-item">
                                                    <div class="timeline-icon bg-info text-white">
                                                        <i class="fas fa-comment"></i>
                                                    </div>
                                                    <h6 class="fw-bold">Admin Notes</h6>
                                                    <p class="text-muted mb-2"><?php echo htmlspecialchars($verification['admin_notes']); ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="alert alert-light mt-3">
                                        <i class="fas fa-clock text-warning me-2"></i>
                                        <strong>Typical review time:</strong> 24-48 hours during business days
                                    </div>
                                </div>
                            </div>
                            
                            <!-- What's Next -->
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <h5 class="fw-bold mb-3"><i class="fas fa-road me-2"></i>What Happens Next?</h5>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="text-center p-3">
                                                <div class="mb-3">
                                                    <i class="fas fa-check-circle fa-2x text-success"></i>
                                                </div>
                                                <h6 class="fw-bold">Document Verification</h6>
                                                <p class="text-muted small">Our team reviews your medical credentials</p>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-center p-3">
                                                <div class="mb-3">
                                                    <i class="fas fa-envelope fa-2x text-primary"></i>
                                                </div>
                                                <h6 class="fw-bold">Approval Email</h6>
                                                <p class="text-muted small">You'll receive notification once approved</p>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-center p-3">
                                                <div class="mb-3">
                                                    <i class="fas fa-tachometer-alt fa-2x text-warning"></i>
                                                </div>
                                                <h6 class="fw-bold">Full Access</h6>
                                                <p class="text-muted small">Access the full doctor dashboard</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Contact Support -->
                            <div class="alert alert-light mt-4">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-question-circle fa-2x text-primary me-3"></i>
                                    <div>
                                        <h6 class="fw-bold mb-1">Need Help?</h6>
                                        <p class="mb-0">
                                            If you have questions about the verification process, 
                                            please contact our support team at 
                                            <a href="mailto:support@healthconnect.org" class="text-primary">support@healthconnect.org</a>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Logout Section -->
                            <div class="logout-section">
                                <h5 class="fw-bold mb-3"><i class="fas fa-sign-out-alt me-2"></i>Want to logout?</h5>
                                <p class="text-muted mb-3">
                                    You can safely logout and come back later. Your approval status will be saved.
                                </p>
                                <div class="logout-options">
                                    <a href="logout.php" class="btn btn-danger logout-btn">
                                        <i class="fas fa-sign-out-alt me-2"></i> Logout Now
                                    </a>
                                    <a href="mailto:support@healthconnect.org" class="btn btn-outline-primary logout-btn">
                                        <i class="fas fa-envelope me-2"></i> Contact Support
                                    </a>
                                    <button class="btn btn-outline-secondary logout-btn" onclick="location.reload()">
                                        <i class="fas fa-sync-alt me-2"></i> Refresh Status
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Auto-refresh notice -->
                    <div class="text-center mt-4 text-muted small">
                        <i class="fas fa-sync-alt me-1"></i>
                        This page will automatically update when your status changes
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh status every 30 seconds
        function checkApprovalStatus() {
            fetch('../../api/auth.php?action=check_approval')
                .then(response => response.json())
                .then(data => {
                    if (data.is_approved) {
                        // Redirect to dashboard if approved
                        window.location.href = 'doctor-dashboard.php?approved=true';
                    }
                })
                .catch(error => console.error('Status check error:', error));
        }
        
        // Check status every 30 seconds
        setInterval(checkApprovalStatus, 30000);
        
        // Initial check
        checkApprovalStatus();
        
        // Auto-logout warning
        let idleTime = 0;
        const idleInterval = setInterval(timerIncrement, 60000); // 1 minute
        
        function timerIncrement() {
            idleTime++;
            if (idleTime > 29) { // 30 minutes
                if (confirm('You have been inactive for 30 minutes. Would you like to stay logged in?')) {
                    idleTime = 0;
                } else {
                    window.location.href = 'logout.php?timeout=true';
                }
            }
        }
        
        // Reset idle time on user activity
        document.addEventListener('mousemove', () => idleTime = 0);
        document.addEventListener('keypress', () => idleTime = 0);
        document.addEventListener('click', () => idleTime = 0);
    </script>
</body>
</html>