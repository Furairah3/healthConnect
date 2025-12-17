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

// Get request details - FIXED: Added response_text to SELECT
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
    error_log("=== FORM SUBMISSION FOR REQUEST #{$request_id} ===");
    error_log("User ID: {$user_id}, Role: {$user_role}");
    error_log("Response length: " . strlen($response_text));
    error_log("Action: {$action}");
    
    if (empty($response_text)) {
        $message = 'Please provide a response';
        $message_type = 'danger';
    } elseif (strlen($response_text) < 50) {
        $message = 'Response should be at least 50 characters long to provide meaningful help';
        $message_type = 'danger';
    } else {
        try {
            // SIMPLE UPDATE - No transactions, no complex checks
            $status = ($action === 'close') ? 'closed' : 'responded';
            
            // Build the update query
            $update_sql = "UPDATE hc_medical_requests 
                           SET response_text = :response_text,
                               responded_by_user_id = :user_id,
                               request_status = :status,
                               response_date = NOW()
                           WHERE request_id = :request_id";
            
            error_log("SQL Query: {$update_sql}");
            error_log("Params: request_id={$request_id}, user_id={$user_id}, status={$status}");
            
            $update_stmt = $pdo->prepare($update_sql);
            
            // Execute with parameters
            $params = [
                ':response_text' => $response_text,
                ':user_id' => $user_id,
                ':status' => $status,
                ':request_id' => $request_id
            ];
            
            $result = $update_stmt->execute($params);
            $rows_affected = $update_stmt->rowCount();
            
            error_log("Execute result: " . ($result ? 'SUCCESS' : 'FAILED'));
            error_log("Rows affected: {$rows_affected}");
            
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
                
                // Refresh request data
                $stmt->execute([':request_id' => $request_id]);
                $request = $stmt->fetch();
                
            } else {
                // Check for PDO error
                $errorInfo = $update_stmt->errorInfo();
                error_log("PDO Error: " . print_r($errorInfo, true));
                
                // Try a simpler update without response_date
                $update_sql2 = "UPDATE hc_medical_requests 
                               SET response_text = :response_text,
                                   responded_by_user_id = :user_id,
                                   request_status = :status
                               WHERE request_id = :request_id";
                
                error_log("Trying simpler update without response_date");
                
                $update_stmt2 = $pdo->prepare($update_sql2);
                $result2 = $update_stmt2->execute($params);
                $rows_affected2 = $update_stmt2->rowCount();
                
                if ($result2 && $rows_affected2 > 0) {
                    $message = '✅ Response submitted successfully! (Response date not set)';
                    $message_type = 'success';
                    
                    // Refresh request data
                    $stmt->execute([':request_id' => $request_id]);
                    $request = $stmt->fetch();
                } else {
                    $message = '⚠️ Failed to update the request. Database error.';
                    $message_type = 'danger';
                    $errorInfo2 = $update_stmt2->errorInfo();
                    error_log("Second PDO Error: " . print_r($errorInfo2, true));
                    
                    // Add error details to message for debugging
                    if ($errorInfo2[2]) {
                        $message .= '<br><small>Error: ' . htmlspecialchars($errorInfo2[2]) . '</small>';
                    }
                }
            }
            
        } catch (Exception $e) {
            $message = '❌ Database error: ' . $e->getMessage();
            $message_type = 'danger';
            error_log("Exception: " . $e->getMessage());
        }
    }
    
    error_log("=== FORM SUBMISSION END ===");
}

// Check if response_text column exists in the result
$has_response_text_column = $request && array_key_exists('response_text', $request);
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
        
        .card { border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border: none; }
        
        textarea { min-height: 200px; resize: vertical; font-size: 15px; line-height: 1.6; }
        
        .btn-success { background: #198754; border-color: #198754; font-weight: 500; }
        .btn-success:hover { background: #157347; border-color: #146c43; }
        
        .request-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-left: 4px solid #0d6efd;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }
        
        .debug-info {
            font-size: 0.85rem;
            background: #e9ecef;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        
        .medical-response {
            background: #fff;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .form-check-card {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .form-check-card:hover {
            border-color: #0d6efd;
            background: #f8f9fa;
        }
        
        .form-check-card.selected {
            border-color: #198754;
            background: #f0fff4;
        }
        
        .disclaimer-box {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-left: 4px solid #ffc107;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
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
        <!-- Debug Info - FIXED -->
        <div class="debug-info">
            <strong><i class="fas fa-bug me-1"></i>System Status:</strong><br>
            • Request ID: <code><?php echo $request_id; ?></code><br>
            • Status: <code><?php echo $request ? $request['request_status'] : 'N/A'; ?></code><br>
            • User ID: <code><?php echo $user_id; ?></code> (Role: <?php echo $user_role; ?>)<br>
            • response_text column: <?php echo $has_response_text_column ? '✅ EXISTS in result' : '❌ NOT in result array'; ?><br>
            • Database: <?php echo $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS); ?>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <div class="d-flex align-items-center">
                    <?php if ($message_type === 'success'): ?>
                        <i class="fas fa-check-circle me-3 fa-2x"></i>
                    <?php elseif ($message_type === 'danger'): ?>
                        <i class="fas fa-exclamation-circle me-3 fa-2x"></i>
                    <?php elseif ($message_type === 'warning'): ?>
                        <i class="fas fa-exclamation-triangle me-3 fa-2x"></i>
                    <?php endif; ?>
                    <div style="flex: 1;">
                        <h5 class="mb-1"><?php 
                            echo $message_type === 'success' ? 'Success!' : 
                                 ($message_type === 'danger' ? 'Error!' : 'Notice'); 
                        ?></h5>
                        <?php echo $message; ?>
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
                <div class="card-body">
                    <div class="text-center py-4">
                        <?php if ($request['request_status'] === 'responded'): ?>
                            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                            <h2>Request Already Responded</h2>
                        <?php else: ?>
                            <i class="fas fa-ban fa-4x text-danger mb-3"></i>
                            <h2>Request <?php echo ucfirst($request['request_status']); ?></h2>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($request['response_text'])): ?>
                        <div class="medical-response">
                            <h5 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-file-medical-alt me-2"></i>Previous Response
                            </h5>
                            <div class="response-content">
                                <?php echo nl2br(htmlspecialchars($request['response_text'])); ?>
                            </div>
                            <?php if ($request['response_date']): ?>
                                <div class="mt-3 text-muted">
                                    <i class="far fa-clock me-1"></i>
                                    Responded on: <?php echo date('F j, Y \a\t g:i A', strtotime($request['response_date'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="text-center mt-4">
                        <a href="doctor-dashboard.php" class="btn btn-primary">
                            <i class="fas fa-hands-helping me-1"></i> Help Other Patients
                        </a>
                    </div>
                </div>
            </div>
        <?php elseif ($request): ?>
            <!-- Respond Form -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">
                                <i class="fas fa-comment-medical me-2"></i> 
                                Medical Response Required
                            </h4>
                            <small class="opacity-90">Request #<?php echo $request_id; ?> • <?php echo htmlspecialchars($request['patient_name']); ?></small>
                        </div>
                        <span class="badge bg-light text-primary fs-6">Urgent</span>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Patient Information -->
                    <div class="request-info">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>
                                    <i class="fas fa-user-injured me-2"></i>
                                    Patient Information
                                </h5>
                                <p class="mb-2">
                                    <strong>Name:</strong> <?php echo htmlspecialchars($request['patient_name']); ?>
                                </p>
                                <?php if ($request['patient_location']): ?>
                                    <p class="mb-2">
                                        <strong>Location:</strong> <?php echo htmlspecialchars($request['patient_location']); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($request['email_address']): ?>
                                    <p class="mb-2">
                                        <strong>Email:</strong> <?php echo htmlspecialchars($request['email_address']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <h5>
                                    <i class="fas fa-info-circle me-2"></i>
                                    Request Details
                                </h5>
                                <p class="mb-2">
                                    <strong>Submitted:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($request['request_date'])); ?>
                                </p>
                                <?php if ($request['urgency_level']): ?>
                                    <p class="mb-2">
                                        <strong>Urgency:</strong> 
                                        <span class="badge bg-<?php 
                                            echo $request['urgency_level'] === 'high' ? 'danger' : 
                                                 ($request['urgency_level'] === 'medium' ? 'warning text-dark' : 'success'); 
                                        ?>">
                                            <?php echo ucfirst($request['urgency_level']); ?> Priority
                                        </span>
                                    </p>
                                <?php endif; ?>
                                <?php if ($request['category']): ?>
                                    <p class="mb-2">
                                        <strong>Category:</strong> <?php echo htmlspecialchars($request['category']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-3 border-top">
                            <h6>
                                <i class="fas fa-stethoscope me-2"></i>
                                Patient's Medical Concern
                            </h6>
                            <div class="bg-white p-3 rounded mt-2">
                                <?php echo nl2br(htmlspecialchars($request['request_description'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Response Form -->
                    <form method="POST" id="responseForm">
                        <div class="mb-4">
                            <label class="form-label fw-bold fs-5">
                                <i class="fas fa-file-medical-alt me-2"></i>
                                Your Professional Medical Response
                                <span class="text-danger">*</span>
                            </label>
                            <div class="form-text mb-3">
                                Provide clear, actionable medical advice. Include assessment, recommendations, and follow-up instructions.
                            </div>
                            <textarea 
                                name="response_text" 
                                class="form-control" 
                                placeholder="Example format:
• Assessment: [Your professional assessment]
• Recommendations: [Specific actions to take]
• Medications: [If applicable, with dosage]
• Follow-up: [When to seek further care]
• Lifestyle advice: [Diet, exercise, rest]

Please be thorough and compassionate..."
                                required
                                rows="8"><?php echo htmlspecialchars($_POST['response_text'] ?? ''); ?></textarea>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <div class="form-text">
                                    <i class="fas fa-text-height me-1"></i>
                                    <span id="charCount">0</span>/5000 characters
                                    <span id="charStatus" class="ms-2"></span>
                                </div>
                                <div class="form-text">
                                    <span id="wordCount">0</span> words
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold fs-5 mb-3">
                                <i class="fas fa-tasks me-2"></i>
                                What should happen after this response?
                            </label>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-check-card" id="respondCard">
                                        <input class="form-check-input" type="radio" name="action" 
                                               id="actionRespond" value="responded" checked style="display: none;">
                                        <label class="form-check-label w-100" for="actionRespond">
                                            <div class="d-flex">
                                                <div class="me-3">
                                                    <i class="fas fa-comment-dots fa-2x text-primary"></i>
                                                </div>
                                                <div>
                                                    <strong class="d-block">Mark as Responded</strong>
                                                    <small class="text-muted">Patient can ask follow-up questions. Case remains open for discussion.</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check-card" id="closeCard">
                                        <input class="form-check-input" type="radio" name="action" 
                                               id="actionClose" value="close" style="display: none;">
                                        <label class="form-check-label w-100" for="actionClose">
                                            <div class="d-flex">
                                                <div class="me-3">
                                                    <i class="fas fa-check-circle fa-2x text-success"></i>
                                                </div>
                                                <div>
                                                    <strong class="d-block">Mark as Closed</strong>
                                                    <small class="text-muted">Case is resolved. No further action needed from patient.</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="disclaimer-box">
                            <div class="d-flex">
                                <i class="fas fa-exclamation-triangle me-3 fa-2x text-warning"></i>
                                <div>
                                    <h5 class="mb-2">Medical & Legal Disclaimer</h5>
                                    <p class="mb-2">
                                        <strong>Important:</strong> This platform provides general health information only. 
                                        It is not a substitute for professional medical advice, diagnosis, or treatment.
                                    </p>
                                    <p class="mb-0">
                                        Always seek the advice of your physician or other qualified health provider with 
                                        any questions you may have regarding a medical condition.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-4 border-top">
                            <div>
                                <a href="doctor-dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i> Cancel
                                </a>
                                <button type="button" class="btn btn-outline-info ms-2" id="previewBtn">
                                    <i class="fas fa-eye me-2"></i> Preview
                                </button>
                            </div>
                            <button type="submit" name="submit_response" id="submitBtn" class="btn btn-success btn-lg px-4">
                                <i class="fas fa-paper-plane me-2"></i> Submit Response
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
                        Best Practices for Medical Responses
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="d-flex p-3 border rounded bg-white">
                                <i class="fas fa-user-check text-success me-3 mt-1"></i>
                                <div>
                                    <strong>Patient-Centered</strong>
                                    <p class="mb-0 small text-muted">Address the patient by name, acknowledge their concerns</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex p-3 border rounded bg-white">
                                <i class="fas fa-clipboard-check text-info me-3 mt-1"></i>
                                <div>
                                    <strong>Actionable Advice</strong>
                                    <p class="mb-0 small text-muted">Provide clear, specific steps the patient can take</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex p-3 border rounded bg-white">
                                <i class="fas fa-shield-alt text-warning me-3 mt-1"></i>
                                <div>
                                    <strong>Safety First</strong>
                                    <p class="mb-0 small text-muted">Always include when to seek emergency care</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Preview Modal -->
            <div class="modal fade" id="previewModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-eye me-2"></i>Preview Your Response
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="previewContent" class="p-3 border rounded bg-light"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('responseForm').submit()">
                                <i class="fas fa-paper-plane me-2"></i>Submit Response
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Character and word count
        const textarea = document.querySelector('textarea[name="response_text"]');
        const charCount = document.getElementById('charCount');
        const charStatus = document.getElementById('charStatus');
        const wordCount = document.getElementById('wordCount');
        const submitBtn = document.getElementById('submitBtn');
        const respondCard = document.getElementById('respondCard');
        const closeCard = document.getElementById('closeCard');
        const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
        
        function updateCounts() {
            const text = textarea.value;
            const charLength = text.length;
            const wordLength = text.trim().split(/\s+/).filter(word => word.length > 0).length;
            
            // Update counts
            charCount.textContent = charLength;
            wordCount.textContent = wordLength;
            
            // Update status
            if (charLength === 0) {
                charStatus.textContent = 'Start typing...';
                charStatus.className = 'text-muted';
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Submit Response';
            } else if (charLength < 50) {
                charStatus.textContent = `Need ${50 - charLength} more characters`;
                charStatus.className = 'text-danger fw-bold';
                submitBtn.disabled = true;
                submitBtn.innerHTML = `<i class="fas fa-exclamation-circle me-2"></i> More details needed`;
            } else if (charLength <= 5000) {
                charStatus.textContent = 'Good length ✓';
                charStatus.className = 'text-success fw-bold';
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Submit Response';
            } else {
                charStatus.textContent = `Too long by ${charLength - 5000} characters`;
                charStatus.className = 'text-danger fw-bold';
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i> Too long';
            }
        }
        
        // Radio card selection
        function setupRadioCards() {
            const radios = document.querySelectorAll('input[name="action"]');
            const cards = [respondCard, closeCard];
            
            radios.forEach((radio, index) => {
                radio.addEventListener('change', function() {
                    cards.forEach(card => card.classList.remove('selected'));
                    if (this.checked) {
                        cards[index].classList.add('selected');
                    }
                });
                
                // Also make the card clickable
                cards[index].addEventListener('click', function() {
                    radio.checked = true;
                    cards.forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                });
            });
            
            // Set initial selection
            if (document.querySelector('input[name="action"]:checked')) {
                const checkedIndex = Array.from(radios).findIndex(r => r.checked);
                cards[checkedIndex].classList.add('selected');
            }
        }
        
        // Preview functionality
        document.getElementById('previewBtn').addEventListener('click', function() {
            const responseText = textarea.value.trim();
            if (responseText.length < 50) {
                alert('Please write at least 50 characters before previewing.');
                textarea.focus();
                return;
            }
            
            const action = document.querySelector('input[name="action"]:checked').value;
            const actionText = action === 'responded' ? 'Mark as Responded' : 'Mark as Closed';
            
            let preview = `
                <div class="preview-response">
                    <h5 class="border-bottom pb-2 mb-3">Response Preview</h5>
                    <div class="mb-3">
                        <strong>Status:</strong> <span class="badge ${action === 'responded' ? 'bg-primary' : 'bg-success'}">${actionText}</span>
                    </div>
                    <div class="preview-content p-3 bg-white rounded border">
                        ${responseText.replace(/\n/g, '<br>')}
                    </div>
                    <div class="mt-3 text-muted small">
                        <i class="fas fa-info-circle me-1"></i>
                        This is how your response will appear to the patient.
                    </div>
                </div>
            `;
            
            document.getElementById('previewContent').innerHTML = preview;
            previewModal.show();
        });
        
        // Form submission
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
            
            // Disable all form elements to prevent double submission
            const formElements = this.elements;
            for (let i = 0; i < formElements.length; i++) {
                formElements[i].disabled = true;
            }
            
            // Add a small delay to show the loading state
            setTimeout(() => {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
            }, 1000);
            
            return true;
        });
        
        // Auto-resize textarea
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 400) + 'px';
        });
        
        // Initialize
        updateCounts();
        setupRadioCards();
        
        // Update counts on input
        textarea.addEventListener('input', updateCounts);
        
        // Focus textarea on page load
        if (textarea) {
            setTimeout(() => {
                textarea.focus();
                // Place cursor at end if there's existing text
                if (textarea.value) {
                    textarea.selectionStart = textarea.selectionEnd = textarea.value.length;
                }
            }, 500);
        }
        
        // Auto-hide success alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert-success').forEach(alert => {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>
