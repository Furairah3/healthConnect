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
            WHERE mr.request_id = :request_id";
    
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
    $action = $_POST['action'] ?? 'responded'; // 'responded' or 'closed'
    
    // Log form data
    error_log("=== FORM SUBMISSION START ===");
    error_log("Request ID: " . $request_id);
    error_log("User ID: " . $user_id);
    error_log("Response length: " . strlen($response_text));
    error_log("Action: " . $action);
    
    if (empty($response_text)) {
        $message = 'Please provide a response';
        $message_type = 'danger';
        error_log("Error: Empty response");
    } elseif (strlen($response_text) < 50) {
        $message = 'Response should be at least 50 characters long to provide meaningful help';
        $message_type = 'danger';
        error_log("Error: Response too short");
    } else {
        try {
            // First, check current status WITHOUT transaction
            $check_sql = "SELECT request_status, responded_by_user_id FROM hc_medical_requests WHERE request_id = :request_id";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([':request_id' => $request_id]);
            $request_data = $check_stmt->fetch();
            
            error_log("Current request data: " . print_r($request_data, true));
            
            if (!$request_data) {
                $message = 'Request not found in database';
                $message_type = 'danger';
                error_log("Error: Request not found in database");
            } elseif ($request_data['request_status'] !== 'pending') {
                $message = 'This request has already been responded to. Current status: ' . $request_data['request_status'];
                $message_type = 'warning';
                error_log("Error: Request status is not pending: " . $request_data['request_status']);
            } elseif ($request_data['responded_by_user_id'] !== null) {
                $message = 'This request already has a responder assigned: User ID ' . $request_data['responded_by_user_id'];
                $message_type = 'warning';
                error_log("Error: Responder already assigned: " . $request_data['responded_by_user_id']);
            } else {
                // All checks passed, proceed with update
                $status = ($action === 'close') ? 'closed' : 'responded';
                
                // Use simple UPDATE without transaction
                $update_sql = "UPDATE hc_medical_requests 
                               SET responded_by_user_id = :user_id, 
                                   response_text = :response_text,
                                   request_status = :status,
                                   response_date = NOW()
                               WHERE request_id = :request_id 
                               AND request_status = 'pending'";
                
                error_log("SQL: " . $update_sql);
                
                $update_stmt = $pdo->prepare($update_sql);
                $params = [
                    ':user_id' => $user_id,
                    ':response_text' => $response_text,
                    ':status' => $status,
                    ':request_id' => $request_id
                ];
                
                error_log("Parameters: " . print_r($params, true));
                
                $result = $update_stmt->execute($params);
                $rows_affected = $update_stmt->rowCount();
                
                error_log("Update result: " . ($result ? 'true' : 'false'));
                error_log("Rows affected: " . $rows_affected);
                
                if ($result && $rows_affected > 0) {
                    // Log activity
                    $log_sql = "INSERT INTO hc_activity_logs (user_id, activity_type, activity_description) 
                                VALUES (:user_id, 'response_added', :description)";
                    $log_stmt = $pdo->prepare($log_sql);
                    $log_stmt->execute([
                        ':user_id' => $user_id,
                        ':description' => 'Responded to request ' . $request_id . ' as ' . $user_role
                    ]);
                    
                    $message = '✅ Response submitted successfully! The patient has been notified.';
                    $message_type = 'success';
                    error_log("Success: Response submitted");
                    
                    // Refresh request data
                    $stmt->execute([':request_id' => $request_id]);
                    $request = $stmt->fetch();
                    
                } else {
                    $message = '⚠️ Failed to update request. It may have been updated by another user.';
                    $message_type = 'warning';
                    error_log("Error: Update failed or no rows affected");
                }
            }
            
        } catch (Exception $e) {
            $message = '❌ Database error: ' . $e->getMessage();
            $message_type = 'danger';
            error_log("Exception: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
        }
    }
    
    error_log("=== FORM SUBMISSION END ===");
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
        
        * { font-family: 'Poppins', sans-serif; }
        
        body { background: #f8f9fa; min-height: 100vh; }
        
        .container { max-width: 900px; margin: 30px auto; }
        
        .alert-success { background: #d1e7dd; border-color: #badbcc; color: #0f5132; }
        .alert-danger { background: #f8d7da; border-color: #f5c2c7; color: #842029; }
        .alert-warning { background: #fff3cd; border-color: #ffecb5; color: #664d03; }
        
        .card { border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        
        textarea { min-height: 200px; resize: vertical; }
        
        .btn-success { background: #198754; border-color: #198754; }
        .btn-success:hover { background: #157347; border-color: #146c43; }
    </style>
</head>
<body>
    <!-- Simple Navigation -->
    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand text-primary" href="../../index.php">
                <i class="fas fa-heartbeat me-2"></i>HealthConnect
            </a>
            <span class="navbar-text">
                Dr. <?php echo htmlspecialchars($user_name); ?>
            </span>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <!-- Debug Info -->
        <div class="alert alert-info mb-3">
            <strong>Debug Info:</strong> 
            Request ID: <?php echo $request_id; ?> | 
            Status: <?php echo $request ? $request['request_status'] : 'N/A'; ?> | 
            User ID: <?php echo $user_id; ?> | 
            Role: <?php echo $user_role; ?>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!$request && empty($message)): ?>
            <!-- Request Not Found -->
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-4x text-warning mb-3"></i>
                    <h2>Request Not Found</h2>
                    <p class="text-muted">Request #<?php echo $request_id; ?> not found.</p>
                    <a href="doctor-dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                </div>
            </div>
        <?php elseif ($request && $request['request_status'] !== 'pending'): ?>
            <!-- Request Already Responded -->
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                    <h2>Request Already Responded</h2>
                    <p class="text-muted">
                        Status: <strong><?php echo ucfirst($request['request_status']); ?></strong><br>
                        <?php if ($request['response_date']): ?>
                            Date: <?php echo date('F j, Y', strtotime($request['response_date'])); ?>
                        <?php endif; ?>
                    </p>
                    <a href="doctor-dashboard.php" class="btn btn-primary">Help Other Patients</a>
                </div>
            </div>
        <?php elseif ($request): ?>
            <!-- Respond Form -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-comment-medical me-2"></i> 
                        Respond to Request #<?php echo $request_id; ?>
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Request Info -->
                    <div class="mb-4">
                        <h5>Patient: <?php echo htmlspecialchars($request['patient_name']); ?></h5>
                        <p class="text-muted">
                            <i class="far fa-calendar me-1"></i>
                            <?php echo date('F j, Y \a\t g:i A', strtotime($request['request_date'])); ?>
                        </p>
                        <div class="bg-light p-3 rounded">
                            <strong>Concern:</strong><br>
                            <?php echo nl2br(htmlspecialchars($request['request_description'])); ?>
                        </div>
                    </div>
                    
                    <!-- Response Form -->
                    <form method="POST" id="responseForm">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Your Response *</label>
                            <textarea 
                                name="response_text" 
                                class="form-control" 
                                placeholder="Type your medical advice here (minimum 50 characters)..."
                                required><?php echo htmlspecialchars($_POST['response_text'] ?? ''); ?></textarea>
                            <div class="form-text">
                                <span id="charCount">0</span> / 5000 characters (minimum 50)
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Action</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="action" 
                                       id="actionRespond" value="responded" checked>
                                <label class="form-check-label" for="actionRespond">
                                    Mark as Responded
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="action" 
                                       id="actionClose" value="close">
                                <label class="form-check-label" for="actionClose">
                                    Mark as Closed
                                </label>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Disclaimer:</strong> This is for informational purposes only. 
                            Always recommend professional medical consultation for serious conditions.
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="doctor-dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Cancel
                            </a>
                            <button type="submit" name="submit_response" id="submitBtn" class="btn btn-success">
                                <i class="fas fa-paper-plane me-2"></i> Submit Response
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Character count
        const textarea = document.querySelector('textarea[name="response_text"]');
        const charCount = document.getElementById('charCount');
        const submitBtn = document.getElementById('submitBtn');
        
        function updateCharCount() {
            const count = textarea.value.length;
            charCount.textContent = count;
            
            // Update color
            if (count < 50) {
                charCount.style.color = '#dc3545';
                submitBtn.disabled = true;
            } else if (count <= 5000) {
                charCount.style.color = '#198754';
                submitBtn.disabled = false;
            } else {
                charCount.style.color = '#dc3545';
                submitBtn.disabled = true;
            }
        }
        
        // Initial update
        updateCharCount();
        
        // Update on input
        textarea.addEventListener('input', updateCharCount);
        
        // Form validation
        document.getElementById('responseForm').addEventListener('submit', function(e) {
            const response = textarea.value.trim();
            
            if (response.length < 50) {
                e.preventDefault();
                alert('❌ Please write at least 50 characters.');
                textarea.focus();
                return false;
            }
            
            if (response.length > 5000) {
                e.preventDefault();
                alert('❌ Response is too long (max 5000 characters).');
                textarea.focus();
                return false;
            }
            
            // Show loading
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Submitting...';
            submitBtn.disabled = true;
            
            return true;
        });
        
        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>
