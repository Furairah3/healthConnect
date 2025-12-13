<?php
// healthconnect/views/auth/view-request.php
session_start();
require_once '../../app/config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?error=required');
    exit();
}

// Get request ID from URL
$request_id = $_GET['id'] ?? 0;
if (!$request_id) {
    header('Location: patient-dashboard.php?error=invalid_request');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];

// Check if user has permission to view this request
try {
    // Get request details
    $sql = "SELECT mr.*, 
                   u.full_name as patient_name, 
                   u.email_address as patient_email,
                   r.full_name as responder_name,
                   a.full_name as admin_name
            FROM hc_medical_requests mr
            LEFT JOIN hc_users u ON mr.patient_id = u.user_id
            LEFT JOIN hc_users r ON mr.responded_by_user_id = r.user_id
            LEFT JOIN hc_users a ON mr.admin_assigned_by = a.user_id
            WHERE mr.request_id = :request_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':request_id' => $request_id]);
    $request = $stmt->fetch();
    
    if (!$request) {
        header('Location: patient-dashboard.php?error=request_not_found');
        exit();
    }
    
    // Check permissions
    $can_view = false;
    
    if ($user_role === 'admin') {
        $can_view = true; // Admin can view all requests
    } elseif ($user_role === 'doctor' || $user_role === 'volunteer') {
        // Doctors and volunteers can view all pending requests or those they responded to
        if ($request['request_status'] === 'pending' || $request['responded_by_user_id'] == $user_id) {
            $can_view = true;
        }
    } elseif ($user_role === 'patient') {
        // Patients can only view their own requests
        if ($request['patient_id'] == $user_id) {
            $can_view = true;
        }
    }
    
    if (!$can_view) {
        header('Location: dashboard.php?error=no_permission');
        exit();
    }
    
    // Get similar requests for context
    $similar_sql = "SELECT request_id, request_title, request_status, request_date 
                    FROM hc_medical_requests 
                    WHERE patient_id = :patient_id 
                    AND request_id != :request_id
                    ORDER BY request_date DESC 
                    LIMIT 5";
    $similar_stmt = $pdo->prepare($similar_sql);
    $similar_stmt->execute([
        ':patient_id' => $request['patient_id'],
        ':request_id' => $request_id
    ]);
    $similar_requests = $similar_stmt->fetchAll();
    
    // Get activity logs for this request
    $activity_sql = "SELECT * FROM hc_activity_logs 
                     WHERE activity_description LIKE :request_desc
                     ORDER BY activity_date DESC";
    $activity_stmt = $pdo->prepare($activity_sql);
    $activity_stmt->execute([':request_desc' => '%Request ' . $request_id . '%']);
    $activities = $activity_stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Request view error: " . $e->getMessage());
    header('Location: dashboard.php?error=server_error');
    exit();
}

// Helper function to format date
function formatDate($date) {
    if (!$date) return 'Not yet';
    $dateTime = new DateTime($date);
    return $dateTime->format('F j, Y \a\t g:i A');
}

// Helper function to get status badge
function getStatusBadge($status) {
    $badges = [
        'pending' => 'warning',
        'responded' => 'success',
        'closed' => 'secondary'
    ];
    return $badges[$status] ?? 'secondary';
}

// Helper function to get urgency badge
function getUrgencyBadge($urgency) {
    $badges = [
        'low' => 'success',
        'medium' => 'warning',
        'high' => 'danger'
    ];
    return $badges[$urgency] ?? 'secondary';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Request - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --success-color: #4cc9f0;
            --warning-color: #f8961e;
            --danger-color: #f72585;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .request-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: -20px;
            position: relative;
            overflow: hidden;
        }
        
        .request-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .request-content {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            padding: 30px;
            margin-top: 30px;
        }
        
        .info-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-color);
        }
        
        .response-card {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-radius: 12px;
            padding: 25px;
            border-left: 4px solid var(--success-color);
        }
        
        .activity-timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .activity-timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--primary-color);
        }
        
        .activity-item {
            position: relative;
            margin-bottom: 25px;
        }
        
        .activity-item::before {
            content: '';
            position: absolute;
            left: -35px;
            top: 5px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary-color);
            border: 3px solid white;
        }
        
        .badge-custom {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .detail-label {
            color: #6c757d;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-size: 1.1rem;
            color: var(--dark-color);
            font-weight: 500;
        }
        
        .description-box {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            white-space: pre-wrap;
            line-height: 1.6;
        }
        
        .similar-request-card {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 3px solid var(--primary-color);
            transition: all 0.3s ease;
            background: white;
        }
        
        .similar-request-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .action-buttons .btn {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .action-buttons .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .back-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: white;
        }
        
        .print-btn {
            background: var(--warning-color);
            border-color: var(--warning-color);
            color: white;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            .request-content {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-light bg-white shadow-sm py-3 no-print">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="../../index.php">
                <i class="fas fa-heartbeat me-2"></i>HealthConnect
            </a>
            <div class="d-flex align-items-center">
                <span class="me-3 text-muted">
                    <i class="fas fa-user-circle me-1"></i>
                    <?php echo htmlspecialchars($user_name); ?> (<?php echo ucfirst($user_role); ?>)
                </span>
                <a href="dashboard.php" class="btn btn-outline-primary btn-sm me-2">
                    <i class="fas fa-home me-1"></i> Dashboard
                </a>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Request Header -->
        <div class="request-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center mb-3">
                        <button onclick="window.history.back()" class="btn back-btn me-3">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <div>
                            <h1 class="h2 fw-bold mb-2">
                                <i class="fas fa-file-medical me-2"></i>
                                <?php echo htmlspecialchars($request['request_title']); ?>
                            </h1>
                            <p class="lead mb-0 opacity-75">
                                Request ID: HC-<?php echo str_pad($request['request_id'], 6, '0', STR_PAD_LEFT); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-flex flex-column flex-md-row justify-content-end gap-2">
                        <span class="badge bg-light text-dark badge-custom">
                            <i class="fas fa-clock me-1"></i>
                            Created: <?php echo formatDate($request['request_date']); ?>
                        </span>
                        <span class="badge bg-<?php echo getStatusBadge($request['request_status']); ?> badge-custom">
                            <?php echo ucfirst($request['request_status']); ?>
                        </span>
                        <span class="badge bg-<?php echo getUrgencyBadge($request['urgency_level']); ?> badge-custom">
                            <i class="fas fa-exclamation-circle me-1"></i>
                            <?php echo ucfirst($request['urgency_level']); ?> Priority
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row mt-4">
            <!-- Left Column: Request Details -->
            <div class="col-lg-8">
                <div class="request-content">
                    <!-- Action Buttons -->
                    <div class="action-buttons d-flex justify-content-end mb-4 gap-2">
                        <?php if ($user_role === 'admin' && $request['request_status'] === 'pending'): ?>
                            <a href="respond-requests.php?id=<?php echo $request_id; ?>" class="btn btn-primary">
                                <i class="fas fa-reply me-1"></i> Respond
                            </a>
                            <a href="#" class="btn btn-warning text-white">
                                <i class="fas fa-user-md me-1"></i> Assign
                            </a>
                        <?php elseif (($user_role === 'doctor' || $user_role === 'volunteer') && $request['request_status'] === 'pending'): ?>
                            <a href="respond-requests.php?id=<?php echo $request_id; ?>" class="btn btn-success">
                                <i class="fas fa-comment-medical me-1"></i> Provide Advice
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($user_role === 'patient' && $request['request_status'] === 'responded'): ?>
                            <button class="btn btn-success">
                                <i class="fas fa-check-circle me-1"></i> Mark as Resolved
                            </button>
                        <?php endif; ?>
                        
                        <button onclick="window.print()" class="btn print-btn">
                            <i class="fas fa-print me-1"></i> Print
                        </button>
                    </div>

                    <!-- Description Section -->
                    <div class="mb-5">
                        <h4 class="fw-bold mb-3 text-primary">
                            <i class="fas fa-align-left me-2"></i>Detailed Description
                        </h4>
                        <div class="description-box">
                            <?php echo nl2br(htmlspecialchars($request['request_description'])); ?>
                        </div>
                    </div>

                    <!-- Response Section (if available) -->
                    <?php if ($request['request_status'] === 'responded' || $request['request_status'] === 'closed'): ?>
                        <div class="mb-5">
                            <h4 class="fw-bold mb-3 text-success">
                                <i class="fas fa-comment-medical me-2"></i>Medical Response
                            </h4>
                            <div class="response-card">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="detail-label">Responded By</div>
                                        <div class="detail-value">
                                            <?php echo $request['responder_name'] ? htmlspecialchars($request['responder_name']) : 'Volunteer/Doctor'; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="detail-label">Response Date</div>
                                        <div class="detail-value">
                                            <?php echo formatDate($request['response_date']); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="detail-label mb-2">Professional Advice</div>
                                <div class="bg-white p-3 rounded">
                                    <?php echo nl2br(htmlspecialchars($request['response_text'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Activity Timeline -->
                    <?php if (!empty($activities)): ?>
                        <div class="mb-4">
                            <h4 class="fw-bold mb-3 text-primary">
                                <i class="fas fa-history me-2"></i>Activity Timeline
                            </h4>
                            <div class="activity-timeline">
                                <?php foreach ($activities as $activity): ?>
                                    <div class="activity-item">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="fw-bold mb-1">
                                                            <?php echo htmlspecialchars($activity['activity_description']); ?>
                                                        </h6>
                                                        <small class="text-muted">
                                                            <i class="fas fa-calendar-alt me-1"></i>
                                                            <?php echo formatDate($activity['activity_date']); ?>
                                                        </small>
                                                    </div>
                                                    <span class="badge bg-light text-dark">
                                                        <?php echo htmlspecialchars($activity['activity_type']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Information & Similar Requests -->
            <div class="col-lg-4">
                <!-- Patient Information -->
                <div class="info-card">
                    <h5 class="fw-bold mb-3 text-primary">
                        <i class="fas fa-user-injured me-2"></i>Patient Information
                    </h5>
                    <div class="row">
                        <div class="col-12 mb-3">
                            <div class="detail-label">Name</div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($request['patient_name']); ?>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <div class="detail-label">Request Date</div>
                            <div class="detail-value">
                                <?php echo formatDate($request['request_date']); ?>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <div class="detail-label">Location</div>
                            <div class="detail-value">
                                <?php echo $request['patient_location'] ? htmlspecialchars($request['patient_location']) : 'Not specified'; ?>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <div class="detail-label">Category</div>
                            <div class="detail-value">
                                <?php echo $request['category'] ? htmlspecialchars($request['category']) : 'General'; ?>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">Last Updated</div>
                            <div class="detail-value">
                                <?php 
                                $latest_date = max(
                                    $request['request_date'],
                                    $request['response_date'] ?? '',
                                    $request['closed_at'] ?? ''
                                );
                                echo formatDate($latest_date);
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin Information (if assigned) -->
                <?php if ($request['admin_assigned']): ?>
                    <div class="info-card">
                        <h5 class="fw-bold mb-3 text-warning">
                            <i class="fas fa-user-shield me-2"></i>Admin Assignment
                        </h5>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <div class="detail-label">Assigned By</div>
                                <div class="detail-value">
                                    <?php echo htmlspecialchars($request['admin_name']); ?>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="detail-label">Assigned On</div>
                                <div class="detail-value">
                                    <?php echo formatDate($request['assigned_at']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Similar Requests -->
                <?php if (!empty($similar_requests)): ?>
                    <div class="info-card">
                        <h5 class="fw-bold mb-3 text-primary">
                            <i class="fas fa-clone me-2"></i>Patient's Other Requests
                        </h5>
                        <?php foreach ($similar_requests as $similar): ?>
                            <a href="view-request.php?id=<?php echo $similar['request_id']; ?>" 
                               class="similar-request-card d-block text-decoration-none">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="fw-bold mb-1 text-dark">
                                            <?php echo htmlspecialchars($similar['request_title']); ?>
                                        </h6>
                                        <small class="text-muted">
                                            <?php echo formatDate($similar['request_date']); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-<?php echo getStatusBadge($similar['request_status']); ?>">
                                        <?php echo ucfirst($similar['request_status']); ?>
                                    </span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Emergency Notice -->
                <div class="alert alert-danger">
                    <div class="d-flex">
                        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-2">Emergency Notice</h6>
                            <p class="small mb-0">
                                If this is a medical emergency, please contact local emergency services immediately.
                                <strong>Do not wait for online responses.</strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5 no-print">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-heartbeat me-2 text-primary"></i>HealthConnect
                    </h5>
                    <p class="small mb-0">
                        Bridging healthcare gaps through community support and volunteerism.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="small mb-0">
                        Request ID: HC-<?php echo str_pad($request_id, 6, '0', STR_PAD_LEFT); ?> | 
                        Viewed on: <?php echo date('F j, Y \a\t g:i A'); ?>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        // Print functionality enhancement
        function printPage() {
            window.print();
        }
        
        // Copy request ID to clipboard
        function copyRequestId() {
            const requestId = 'HC-<?php echo str_pad($request_id, 6, '0', STR_PAD_LEFT); ?>';
            navigator.clipboard.writeText(requestId).then(() => {
                alert('Request ID copied to clipboard: ' + requestId);
            });
        }
        
        // Emergency contact information toggle
        function showEmergencyContacts() {
            const contacts = `
                <div class="alert alert-danger mt-3">
                    <h6>Local Emergency Contacts:</h6>
                    <ul class="mb-0">
                        <li>National Emergency: 112 or 999</li>
                        <li>Ambulance Services: 193</li>
                        <li>Fire Service: 192</li>
                        <li>Police: 191</li>
                    </ul>
                </div>
            `;
            document.getElementById('emergencyContacts').innerHTML = contacts;
        }
        
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>