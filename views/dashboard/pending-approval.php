<?php
// healthconnect/views/dashboard/pending-approval.php
session_start();
require_once '../../app/config/database.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header('Location: ../auth/login.php?error=required');
    exit();
}

// If doctor is already approved, redirect to dashboard
if ($_SESSION['is_approved']) {
    header('Location: doctor.php');
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
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow-lg">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0"><i class="fas fa-clock me-2"></i> Account Pending Approval</h4>
                    </div>
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="fas fa-user-md fa-4x text-warning mb-3"></i>
                            <h3>Welcome, Dr. <?php echo htmlspecialchars($user_name); ?>!</h3>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Your account is under review</strong>
                            <p class="mb-0 mt-2">
                                Thank you for registering as a medical professional. Our admin team is reviewing 
                                your credentials. This process usually takes 24-48 hours.
                            </p>
                        </div>
                        
                        <?php if ($verification): ?>
                            <div class="mt-4">
                                <h5>Verification Status</h5>
                                <div class="d-flex justify-content-center mb-3">
                                    <div class="text-center mx-4">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto" 
                                             style="width: 60px; height: 60px;">
                                            <i class="fas fa-paper-plane"></i>
                                        </div>
                                        <p class="mt-2 mb-0">Submitted</p>
                                        <small><?php echo date('M d, Y', strtotime($verification['submission_date'])); ?></small>
                                    </div>
                                    
                                    <div class="text-center mx-4">
                                        <div class="rounded-circle bg-warning text-white d-flex align-items-center justify-content-center mx-auto" 
                                             style="width: 60px; height: 60px;">
                                            <i class="fas fa-search"></i>
                                        </div>
                                        <p class="mt-2 mb-0">Under Review</p>
                                        <small>In Progress</small>
                                    </div>
                                    
                                    <div class="text-center mx-4">
                                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto" 
                                             style="width: 60px; height: 60px;">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <p class="mt-2 mb-0">Approved</p>
                                        <small>Pending</small>
                                    </div>
                                </div>
                                
                                <div class="alert alert-light">
                                    <p class="mb-2"><strong>Current Status:</strong> 
                                        <span class="text-uppercase badge bg-warning">
                                            <?php echo str_replace('_', ' ', $verification['verification_status']); ?>
                                        </span>
                                    </p>
                                    <?php if ($verification['admin_notes']): ?>
                                        <p class="mb-0"><strong>Admin Note:</strong> <?php echo htmlspecialchars($verification['admin_notes']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <h5>What happens next?</h5>
                            <ul class="list-unstyled text-start">
                                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Our team reviews your medical credentials</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> You'll receive an email notification once approved</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Upon approval, you can access all doctor features</li>
                            </ul>
                        </div>
                        
                        <div class="mt-4">
                            <a href="../auth/logout.php" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-sign-out-alt me-1"></i> Logout
                            </a>
                            <a href="../../index.php" class="btn btn-primary">
                                <i class="fas fa-home me-1"></i> Return to Homepage
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <p class="text-muted">
                        <i class="fas fa-envelope me-1"></i>
                        Questions? Contact us at <a href="mailto:support@healthconnect.org">support@healthconnect.org</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>