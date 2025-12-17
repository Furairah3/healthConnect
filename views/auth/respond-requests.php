<?php
// healthconnect/views/auth/respond-request.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is a doctor/volunteer/admin
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?error=required');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];

// Check if user can respond (doctor, volunteer, or admin)
if (!in_array($user_role, ['doctor', 'volunteer', 'admin'])) {
    header('Location: ' . $user_role . '-dashboard.php?error=no_permission');
    exit();
}

require_once '../../app/config/database.php';

$request_id = $_GET['id'] ?? 0;
$message = '';
$message_type = '';

// Get request details
$request = null;
if ($request_id) {
    $sql = "SELECT mr.*, u.full_name as patient_name, u.email_address, u.location as patient_location
            FROM hc_medical_requests mr
            JOIN hc_users u ON mr.patient_id = u.user_id
            WHERE mr.request_id = :request_id AND mr.request_status = 'pending'";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':request_id' => $request_id]);
    $request = $stmt->fetch();
    
    if (!$request) {
        $message = 'Request not found or already responded to';
        $message_type = 'warning';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_response'])) {
    $response_text = $_POST['response_text'] ?? '';
    
    if (empty($response_text)) {
        $message = 'Please provide a response';
        $message_type = 'danger';
    } elseif (strlen($response_text) < 10) {
        $message = 'Response should be at least 10 characters long';
        $message_type = 'danger';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Update request with response
            $update_sql = "UPDATE hc_medical_requests 
                           SET responded_by_user_id = :user_id, 
                               response_text = :response_text,
                               request_status = 'responded',
                               response_date = NOW()
                           WHERE request_id = :request_id";
            
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([
                ':user_id' => $user_id,
                ':response_text' => $response_text,
                ':request_id' => $request_id
            ]);
            
            // Log activity
            $log_sql = "INSERT INTO hc_activity_logs (user_id, activity_type, activity_description) 
                        VALUES (:user_id, 'response_added', :description)";
            $log_stmt = $pdo->prepare($log_sql);
            $log_stmt->execute([
                ':user_id' => $user_id,
                ':description' => 'Responded to request ' . $request_id . ' as ' . $user_role
            ]);
            
            $pdo->commit();
            
            $message = 'Response submitted successfully! The patient has been notified.';
            $message_type = 'success';
            
            // Clear form
            $_POST = [];
            
            // Refresh request data
            $stmt->execute([':request_id' => $request_id]);
            $request = $stmt->fetch();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Error submitting response: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respond to Request - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #052c65;
            --success-color: #20c997;
        }
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e3f2fd 100%);
            min-height: 100vh;
        }
        
        .respond-container {
            max-width: 900px;
            margin: 30px auto;
            opacity: 0;
            animation: fadeIn 0.8s ease forwards;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            transition: all 0.5s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 20px 20px 0 0 !important;
            padding: 25px 30px;
            position: relative;
            overflow: hidden;
        }
        
        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,160L48,165.3C96,171,192,181,288,181.3C384,181,480,171,576,165.3C672,160,768,160,864,170.7C960,181,1056,203,1152,202.7C1248,203,1344,181,1392,170.7L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            animation: float 20s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .request-info {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid var(--primary-color);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--secondary-color);
        }
        
        textarea {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            transition: all 0.3s;
            min-height: 200px;
            resize: vertical;
        }
        
        textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            transform: translateY(-2px);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--success-color), #198754);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(32, 201, 151, 0.3);
        }
        
        .btn-submit:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .char-count {
            font-size: 12px;
            color: #6c757d;
        }
        
        .urgency-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .urgency-high { background: #dc3545; color: white; }
        .urgency-medium { background: #ffc107; color: black; }
        .urgency-low { background: #198754; color: white; }
        
        .response-guidelines {
            background: #e8f4fc;
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid var(--success-color);
        }
        
        .response-guidelines h6 {
            color: var(--secondary-color);
        }
        
        .floating-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .particle {
            position: absolute;
            background: rgba(13, 110, 253, 0.05);
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
        
        @media (max-width: 768px) {
            .respond-container {
                margin: 15px;
            }
            
            .card-header {
                padding: 20px;
            }
            
            textarea {
                min-height: 150px;
            }
        }
    </style>
</head>
<body>
    <!-- Floating Background Particles -->
    <div class="floating-particles">
        <?php for ($i = 0; $i < 10; $i++): ?>
            <div class="particle" 
                 style="width: <?php echo rand(2, 6); ?>px; 
                        height: <?php echo rand(2, 6); ?>px;
                        left: <?php echo rand(0, 100); ?>%;
                        animation-delay: <?php echo rand(0, 20); ?>s;
                        animation-duration: <?php echo rand(15, 30); ?>s;"></div>
        <?php endfor; ?>
    </div>
    
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
                        <a class="nav-link" href="<?php echo $user_role . '-dashboard.php'; ?>">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="respond-requests.php">
                            <i class="fas fa-hands-helping me-1"></i> Respond to Requests
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?>
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

    <!-- Main Content -->
    <div class="container respond-container">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> me-2 fa-lg"></i>
                    <div><?php echo htmlspecialchars($message); ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!$request && empty($message)): ?>
            <!-- Request Not Found -->
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-exclamation-triangle fa-4x text-warning"></i>
                    </div>
                    <h2 class="mb-3">Request Not Found</h2>
                    <p class="text-muted mb-4">The request you're trying to respond to doesn't exist or has been closed.</p>
                    <a href="respond-requests.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i> Back to Requests
                    </a>
                </div>
            </div>
        <?php elseif ($request && $request['request_status'] !== 'pending'): ?>
            <!-- Request Already Responded -->
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle fa-4x text-success"></i>
                    </div>
                    <h2 class="mb-3">Request Already Responded</h2>
                    <p class="text-muted mb-4">This request has already been addressed by another healthcare professional.</p>
                    <a href="respond-requests.php" class="btn btn-primary">
                        <i class="fas fa-hands-helping me-2"></i> Help Other Patients
                    </a>
                </div>
            </div>
        <?php elseif ($request): ?>
            <!-- Respond Form -->
            <div class="card">
                <div class="card-header position-relative">
                    <h4 class="mb-0 fw-bold">
                        <i class="fas fa-comment-medical me-2"></i> Provide Medical Response
                    </h4>
                    <p class="mb-0 opacity-75 mt-2">Share your professional medical advice</p>
                </div>
                <div class="card-body p-4">
                    <!-- Request Information -->
                    <div class="request-info">
                        <h5 class="fw-bold mb-3 text-primary">
                            <i class="fas fa-file-medical me-2"></i> Request Details
                        </h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="text-muted small">Patient</div>
                                <div class="fw-bold"><?php echo htmlspecialchars($request['patient_name']); ?></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="text-muted small">Location</div>
                                <div class="fw-bold"><?php echo htmlspecialchars($request['patient_location'] ?? 'Not specified'); ?></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="text-muted small">Urgency Level</div>
                                <div class="fw-bold">
                                    <span class="urgency-badge urgency-<?php echo strtolower($request['urgency_level'] ?? 'medium'); ?>">
                                        <?php echo $request['urgency_level'] ? htmlspecialchars($request['urgency_level']) : 'Medium'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="text-muted small">Request Date</div>
                                <div class="fw-bold">
                                    <?php echo date('F j, Y \a\t g:i A', strtotime($request['request_date'])); ?>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="text-muted small">Patient's Concern</div>
                                <div class="bg-white p-3 rounded mt-2">
                                    <?php echo nl2br(htmlspecialchars($request['request_description'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Response Guidelines -->
                    <div class="response-guidelines mb-4">
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-graduation-cap me-2"></i> Response Guidelines
                        </h6>
                        <ul class="small mb-0">
                            <li>Provide clear, professional medical advice</li>
                            <li>Use simple language that patients can understand</li>
                            <li>Include recommendations for next steps if needed</li>
                            <li>Mention if the patient should seek in-person medical attention</li>
                            <li>Avoid prescribing specific medications without proper consultation</li>
                            <li>Always emphasize when to seek emergency care</li>
                        </ul>
                    </div>
                    
                    <!-- Response Form -->
                    <form method="POST">
                        <div class="mb-4">
                            <label for="response_text" class="form-label fw-bold">
                                Your Professional Response
                            </label>
                            <textarea 
                                name="response_text" 
                                id="response_text" 
                                class="form-control" 
                                placeholder="Type your medical response here. Be professional, clear, and helpful..."
                                required><?php echo htmlspecialchars($_POST['response_text'] ?? ''); ?></textarea>
                            <div class="char-count mt-1 text-end">
                                <span id="charCount">0</span> characters
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-info-circle fa-lg me-3 mt-1"></i>
                                <div>
                                    <strong>Important:</strong> Your response will be sent to the patient and saved in their medical records. 
                                    Always recommend professional medical consultation for serious conditions.
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="view-request.php?id=<?php echo $request_id; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Back to Request
                            </a>
                            <button type="submit" name="submit_response" class="btn-submit">
                                <i class="fas fa-paper-plane me-2"></i> Submit Response
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Character count
        const responseText = document.getElementById('response_text');
        const charCount = document.getElementById('charCount');
        
        responseText.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
        
        // Initialize count
        charCount.textContent = responseText.value.length;
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const response = responseText.value.trim();
            
            if (response.length < 10) {
                e.preventDefault();
                alert('Response should be at least 10 characters long');
                return false;
            }
            
            if (response.length > 5000) {
                e.preventDefault();
                alert('Response is too long. Please keep it under 5000 characters');
                return false;
            }
            
            // Show loading animation
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Submitting...';
            submitBtn.disabled = true;
        });
        
        // Add animations
        document.addEventListener('DOMContentLoaded', function() {
            // Fade in content
            const container = document.querySelector('.respond-container');
            setTimeout(() => {
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
            
            // Auto-hide alerts after 5 seconds
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
</body>
</html>
