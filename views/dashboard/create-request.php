<?php
// healthconnect/views/dashboard/create-request.php - FIXED VERSION
session_start();
require_once '../../app/config/database.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header('Location: ../auth/login.php?error=required');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = cleanInput($_POST['title'] ?? '');
    $description = cleanInput($_POST['description'] ?? '');
    $location = cleanInput($_POST['location'] ?? '');
    
    if (empty($title) || empty($description)) {
        $error = 'Title and description are required';
    } else {
        try {
            // Insert health request
            $sql = "INSERT INTO hc_medical_requests (patient_id, request_title, request_description, patient_location) 
                    VALUES (:patient_id, :title, :description, :location)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':patient_id' => $user_id,
                ':title' => $title,
                ':description' => $description,
                ':location' => $location
            ]);
            
            $request_id = $pdo->lastInsertId();
            
            // Log activity - FIXED SQL SYNTAX
            $sql = "INSERT INTO hc_activity_logs (user_id, activity_type, activity_description, ip_address, user_agent) 
                    VALUES (:user_id, 'request_create', :description, :ip, :agent)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $user_id,
                ':description' => 'Created health request: ' . $title,
                ':ip' => $_SERVER['REMOTE_ADDR'],
                ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            $success = 'Health request submitted successfully! A volunteer or doctor will respond soon.';
            
            // Clear form after successful submission
            $_POST = array();
            
        } catch (Exception $e) {
            $error = 'Failed to submit request: ' . $e->getMessage();
            error_log("Create request error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Health Request - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .form-control, .form-select {
            border-radius: 8px;
            padding: 12px 15px;
            border: 2px solid #dee2e6;
        }
        .form-control:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="../../index.php">
                <i class="fas fa-heartbeat me-2"></i>HealthConnect
            </a>
            <div>
                <span class="me-3">Welcome, <?php echo htmlspecialchars($user_name); ?></span>
                <a href="patient.php" class="btn btn-outline-primary btn-sm me-2">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
                <a href="../auth/logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white py-3">
                        <h4 class="mb-0"><i class="fas fa-file-medical me-2"></i> New Health Request</h4>
                    </div>
                    
                    <div class="card-body p-4 p-md-5">
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                <div class="mt-3">
                                    <a href="patient.php" class="btn btn-success me-2">
                                        <i class="fas fa-tachometer-alt me-1"></i> Go to Dashboard
                                    </a>
                                    <a href="create-request.php" class="btn btn-outline-primary">
                                        <i class="fas fa-plus me-1"></i> Submit Another Request
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (empty($success)): ?>
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                
                                <div class="mb-4">
                                    <label for="title" class="form-label fw-bold">Request Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                                           placeholder="e.g., Headache and fever for 3 days" required>
                                    <small class="text-muted">Brief description of your health concern</small>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="description" class="form-label fw-bold">Detailed Description *</label>
                                    <textarea class="form-control" id="description" name="description" 
                                              rows="6" placeholder="Please describe your symptoms in detail..." required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                    <small class="text-muted">Include: Symptoms, duration, severity, any medications taken</small>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="location" class="form-label fw-bold">Location (Optional)</label>
                                    <input type="text" class="form-control" id="location" name="location" 
                                           value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>"
                                           placeholder="e.g., Rural village, District, State">
                                    <small class="text-muted">Your location helps volunteers provide relevant advice</small>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Important:</strong> This is for general medical advice only. 
                                    In case of emergency, please contact local emergency services immediately.
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg py-3">
                                        <i class="fas fa-paper-plane me-2"></i> Submit Health Request
                                    </button>
                                    <a href="patient.php" class="btn btn-outline-secondary py-3">Cancel</a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Tips Section -->
                <?php if (empty($success)): ?>
                <div class="card shadow-sm border-0 mt-4">
                    <div class="card-header bg-light py-3">
                        <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i> Tips for Better Responses</h6>
                    </div>
                    <div class="card-body">
                        <ul class="mb-0 ps-3">
                            <li class="mb-2">Describe your symptoms clearly and in detail</li>
                            <li class="mb-2">Mention how long you've had the symptoms</li>
                            <li class="mb-2">Include any medications you're currently taking</li>
                            <li class="mb-2">Note any allergies you have</li>
                            <li>Mention if symptoms are getting better or worse</li>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>