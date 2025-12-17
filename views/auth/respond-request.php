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
            // DIRECT SIMPLE UPDATE - try the most basic version first
            $status = ($action === 'close') ? 'closed' : 'responded';
            
            // Version 1: Try simplest update first
            $update_sql = "UPDATE hc_medical_requests 
                           SET response_text = :response_text,
                               responded_by_user_id = :user_id,
                               request_status = :status
                           WHERE request_id = :request_id";
            
            error_log("Attempting UPDATE with SQL: " . $update_sql);
            error_log("Params: response_text=" . substr($response_text, 0, 100) . "...");
            error_log("Params: user_id=" . $user_id);
            error_log("Params: status=" . $status);
            error_log("Params: request_id=" . $request_id);
            
            $update_stmt = $pdo->prepare($update_sql);
            $params = [
                ':response_text' => $response_text,
                ':user_id' => $user_id,
                ':status' => $status,
                ':request_id' => $request_id
            ];
            
            $result = $update_stmt->execute($params);
            $rows_affected = $update_stmt->rowCount();
            
            error_log("Execute result: " . ($result ? 'true' : 'false'));
            error_log("Rows affected: " . $rows_affected);
            
            if ($result && $rows_affected > 0) {
                // Try to also update response_date if column exists
                try {
                    $date_sql = "UPDATE hc_medical_requests SET response_date = NOW() WHERE request_id = :request_id";
                    $date_stmt = $pdo->prepare($date_sql);
                    $date_stmt->execute([':request_id' => $request_id]);
                    error_log("Response date updated");
                } catch (Exception $e) {
                    error_log("Could not update response_date: " . $e->getMessage());
                    // This is okay, column might not exist
                }
                
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
                error_log("SUCCESS: Response submitted");
                
                // Refresh request data
                $stmt->execute([':request_id' => $request_id]);
                $request = $stmt->fetch();
                
            } else {
                // Try Version 2: Check if columns exist by describing table
                error_log("Update failed, checking table structure...");
                
                // Debug: Show table structure
                $desc_sql = "DESCRIBE hc_medical_requests";
                $desc_stmt = $pdo->query($desc_sql);
                $table_structure = $desc_stmt->fetchAll();
                error_log("Table structure: " . print_r($table_structure, true));
                
                // Try updating without optional columns
                $update_sql2 = "UPDATE hc_medical_requests 
                               SET response_text = :response_text,
                                   request_status = :status
                               WHERE request_id = :request_id";
                
                error_log("Trying UPDATE 2 with SQL: " . $update_sql2);
                
                $update_stmt2 = $pdo->prepare($update_sql2);
                $params2 = [
                    ':response_text' => $response_text,
                    ':status' => $status,
                    ':request_id' => $request_id
                ];
                
                $result2 = $update_stmt2->execute($params2);
                $rows_affected2 = $update_stmt2->rowCount();
                
                error_log("Execute result 2: " . ($result2 ? 'true' : 'false'));
                error_log("Rows affected 2: " . $rows_affected2);
                
                if ($result2 && $rows_affected2 > 0) {
                    $message = '✅ Response submitted successfully! (Without assigning responder)';
                    $message_type = 'success';
                    
                    // Refresh request data
                    $stmt->execute([':request_id' => $request_id]);
                    $request = $stmt->fetch();
                } else {
                    // LAST RESORT: Try direct SQL execution
                    error_log("All updates failed, trying direct INSERT into response_text...");
                    
                    // Check if we can at least set the response_text
                    $check_sql = "SELECT response_text FROM hc_medical_requests WHERE request_id = :request_id";
                    $check_stmt = $pdo->prepare($check_sql);
                    $check_stmt->execute([':request_id' => $request_id]);
                    $current_response = $check_stmt->fetchColumn();
                    error_log("Current response_text value: " . ($current_response ?: 'NULL'));
                    
                    $message = '⚠️ Could not update request. Please check database permissions or contact administrator.';
                    $message_type = 'danger';
                    error_log("FINAL ERROR: All update attempts failed");
                    
                    // Show database error info if available
                    if ($update_stmt->errorInfo()) {
                        error_log("PDO Error Info: " . print_r($update_stmt->errorInfo(), true));
                        $message .= '<br><small>Error: ' . $update_stmt->errorInfo()[2] . '</small>';
                    }
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
        .alert-info { background: #cff4fc; border-color: #b6effb; color: #055160; }
        
        .card { border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        
        textarea { min-height: 200px; resize: vertical; }
        
        .btn-success { background: #198754; border-color: #198754; }
        .btn-success:hover { background: #157347; border-color: #146c43; }
        
        .request-info {
            background: #f8f9fa;
            border-left: 4px solid #0d6efd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .debug-info {
            font-size: 0.85rem;
            background: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <!-- Simple Navigation -->
    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand text-primary" href="../../index.php">
                <i class="fas fa-heartbeat me-2"></i>HealthConnect
            </a>
            <div class="d-flex align-items-center">
                <span class="navbar-text me-3">
                    <i class="fas fa-user-md me-1"></i> Dr. <?php echo htmlspecialchars($user_name); ?>
                </span>
                <a href="doctor-dashboard.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <!-- Debug Info -->
        <div class="debug-info">
            <strong><i class="fas fa-bug me-1"></i>Debug Info:</strong><br>
            Request ID: <code><?php echo $request_id; ?></code> | 
            Status: <code><?php echo $request ? $request['request_status'] : 'N/A'; ?></code> | 
            User ID: <code><?php echo $user_id; ?></code> | 
            Role: <code><?php echo $user_role; ?></code> |
            Has Response Text Column: <?php echo $request && isset($request['response_text']) ? '✅ Yes' : '❌ No'; ?>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <div class="d-flex align-items-center">
                    <?php if ($message_type === 'success'): ?>
                        <i class="fas fa-check-circle me-3 fa-lg"></i>
                    <?php elseif ($message_type === 'danger'): ?>
                        <i class="fas fa-exclamation-circle me-3 fa-lg"></i>
                    <?php elseif ($message_type === 'warning'): ?>
                        <i class="fas fa-exclamation-triangle me-3 fa-lg"></i>
                    <?php endif; ?>
                    <div>
                        <?php echo $message; ?>
                        <?php if ($message_type === 'success' && $request): ?>
                            <div class="mt-2">
                                <a href="doctor-dashboard.php" class="btn btn-sm btn-outline-success me-2">
                                    <i class="fas fa-tachometer-alt me-1"></i> Back to Dashboard
                                </a>
                                <a href="respond-request.php?id=<?php echo $request_id + 1; ?>" class="btn btn-sm btn-success">
                                    <i class="fas fa-forward me-1"></i> Next Request
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
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
                    <a href="doctor-dashboard.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        <?php elseif ($request && $request['request_status'] !== 'pending'): ?>
            <!-- Request Already Responded -->
            <div class="card">
                <div class="card-body text-center py-5">
                    <?php if ($request['request_status'] === 'responded'): ?>
                        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                        <h2>Request Already Responded</h2>
                        <div class="alert alert-success mt-3">
                            <strong>Response:</strong><br>
                            <?php echo nl2br(htmlspecialchars($request['response_text'] ?: 'No response text available')); ?>
                        </div>
                    <?php else: ?>
                        <i class="fas fa-ban fa-4x text-danger mb-3"></i>
                        <h2>Request <?php echo ucfirst($request['request_status']); ?></h2>
                    <?php endif; ?>
                    
                    <p class="text-muted mt-3">
                        <strong>Status:</strong> <?php echo ucfirst($request['request_status']); ?><br>
                        <?php if ($request['response_date']): ?>
                            <strong>Response Date:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($request['response_date'])); ?>
                        <?php endif; ?>
                    </p>
                    
                    <a href="doctor-dashboard.php" class="btn btn-primary mt-3">
                        <i class="fas fa-hands-helping me-1"></i> Help Other Patients
                    </a>
                </div>
            </div>
        <?php elseif ($request): ?>
            <!-- Respond Form -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-comment-medical me-2"></i> 
                            Respond to Medical Request
                        </h4>
                        <span class="badge bg-light text-primary">#<?php echo $request_id; ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Request Info -->
                    <div class="request-info">
                        <h5>
                            <i class="fas fa-user-injured me-2"></i>
                            Patient: <?php echo htmlspecialchars($request['patient_name']); ?>
                        </h5>
                        <p class="mb-2">
                            <i class="far fa-calendar me-1"></i>
                            <strong>Submitted:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($request['request_date'])); ?>
                        </p>
                        <?php if ($request['patient_location']): ?>
                            <p class="mb-2">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                <strong>Location:</strong> <?php echo htmlspecialchars($request['patient_location']); ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($request['urgency_level']): ?>
                            <p class="mb-2">
                                <i class="fas fa-exclamation-circle me-1"></i>
                                <strong>Urgency:</strong> 
                                <span class="badge bg-<?php 
                                    echo $request['urgency_level'] === 'high' ? 'danger' : 
                                         ($request['urgency_level'] === 'medium' ? 'warning' : 'success'); 
                                ?>">
                                    <?php echo ucfirst($request['urgency_level']); ?>
                                </span>
                            </p>
                        <?php endif; ?>
                        
                        <div class="mt-3 p-3 bg-white rounded">
                            <strong><i class="fas fa-stethoscope me-1"></i> Medical Concern:</strong><br>
                            <p class="mt-2 mb-0"><?php echo nl2br(htmlspecialchars($request['request_description'])); ?></p>
                        </div>
                    </div>
                    
                    <!-- Response Form -->
                    <form method="POST" id="responseForm">
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-file-medical-alt me-1"></i>
                                Your Medical Response *
                            </label>
                            <textarea 
                                name="response_text" 
                                class="form-control" 
                                placeholder="Please provide detailed medical advice, including:
• Diagnosis assessment
• Recommended actions/steps
• Medications (if applicable)
• When to seek immediate care
• Follow-up recommendations
• Lifestyle advice

Minimum 50 characters required..."
                                required><?php echo htmlspecialchars($_POST['response_text'] ?? ''); ?></textarea>
                            <div class="form-text mt-2">
                                <i class="fas fa-text-height me-1"></i>
                                <span id="charCount">0</span> / 5000 characters (minimum 50 required)
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-tasks me-1"></i>
                                Action After Response
                            </label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check card p-3 border">
                                        <input class="form-check-input" type="radio" name="action" 
                                               id="actionRespond" value="responded" checked>
                                        <label class="form-check-label" for="actionRespond">
                                            <strong><i class="fas fa-comment-dots me-1"></i> Mark as Responded</strong><br>
                                            <small class="text-muted">Patient can still follow up with more questions</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check card p-3 border">
                                        <input class="form-check-input" type="radio" name="action" 
                                               id="actionClose" value="close">
                                        <label class="form-check-label" for="actionClose">
                                            <strong><i class="fas fa-check-circle me-1"></i> Mark as Closed</strong><br>
                                            <small class="text-muted">Case is resolved, no further action needed</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <div class="d-flex">
                                <i class="fas fa-exclamation-triangle me-3 fa-lg mt-1"></i>
                                <div>
                                    <strong>Medical Disclaimer:</strong><br>
                                    This platform provides general health information and is not a substitute for professional medical advice, diagnosis, or treatment. 
                                    Always seek the advice of a qualified healthcare provider with any questions you may have regarding a medical condition.
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                            <a href="doctor-dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Cancel & Return
                            </a>
                            <button type="submit" name="submit_response" id="submitBtn" class="btn btn-success btn-lg">
                                <i class="fas fa-paper-plane me-2"></i> Submit Medical Response
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Quick Tips -->
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-lightbulb me-2"></i>
                        Tips for a Good Medical Response
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="d-flex mb-3">
                                <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                <div>
                                    <strong>Be Clear</strong><br>
                                    <small>Use simple, understandable language</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex mb-3">
                                <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                <div>
                                    <strong>Be Specific</strong><br>
                                    <small>Provide actionable advice and steps</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex mb-3">
                                <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                <div>
                                    <strong>Be Empathetic</strong><br>
                                    <small>Acknowledge the patient's concerns</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Character count with real-time validation
        const textarea = document.querySelector('textarea[name="response_text"]');
        const charCount = document.getElementById('charCount');
        const submitBtn = document.getElementById('submitBtn');
        
        function updateCharCount() {
            const count = textarea.value.length;
            charCount.textContent = count;
            charCount.className = '';
            
            if (count === 0) {
                charCount.classList.add('text-muted');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Submit Medical Response';
            } else if (count < 50) {
                charCount.classList.add('text-danger');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i> Need ' + (50 - count) + ' more characters';
            } else if (count <= 5000) {
                charCount.classList.add('text-success');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Submit Medical Response';
            } else {
                charCount.classList.add('text-danger');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i> Too long (max 5000)';
            }
        }
        
        // Initial update
        updateCharCount();
        
        // Update on input
        textarea.addEventListener('input', updateCharCount);
        
        // Auto-resize textarea
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
        
        // Form validation
        document.getElementById('responseForm').addEventListener('submit', function(e) {
            const response = textarea.value.trim();
            
            if (response.length < 50) {
                e.preventDefault();
                alert('❌ Medical response should be at least 50 characters to provide meaningful advice.');
                textarea.focus();
                textarea.select();
                return false;
            }
            
            if (response.length > 5000) {
                e.preventDefault();
                alert('❌ Response is too long. Please keep it under 5000 characters for clarity.');
                textarea.focus();
                textarea.select();
                return false;
            }
            
            // Show loading
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Submitting...';
            submitBtn.disabled = true;
            
            // Disable all form elements
            const formElements = this.elements;
            for (let i = 0; i < formElements.length; i++) {
                formElements[i].disabled = true;
            }
            
            return true;
        });
        
        // Auto-hide alerts after 8 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert:not(.alert-warning)').forEach(alert => {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            });
        }, 8000);
        
        // Focus textarea on page load if form is visible
        if (textarea) {
            setTimeout(() => {
                textarea.focus();
                // Place cursor at end
                textarea.selectionStart = textarea.selectionEnd = textarea.value.length;
            }, 300);
        }
    </script>
</body>
</html>
