<?php
// healthconnect/views/auth/view-request.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?error=required');
    exit();
}

require_once '../../app/config/database.php';

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];
$request_id = $_GET['id'] ?? 0;

// Handle form submissions
$message = '';
$message_type = '';

// Handle Delete Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_request'])) {
    if ($request_id) {
        try {
            // Check if request belongs to user (for patients) or if admin/doctor
            $check_sql = "SELECT patient_id FROM hc_medical_requests WHERE request_id = :request_id";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([':request_id' => $request_id]);
            $request_data = $check_stmt->fetch();
            
            if ($request_data) {
                // Check permissions
                $can_delete = false;
                if ($user_role === 'admin') {
                    $can_delete = true;
                } elseif ($user_role === 'patient' && $request_data['patient_id'] == $user_id) {
                    $can_delete = true;
                }
                
                if ($can_delete) {
                    $pdo->beginTransaction();
                    
                    // Delete activity logs
                    $sql1 = "DELETE FROM hc_activity_logs WHERE activity_description LIKE :desc";
                    $stmt1 = $pdo->prepare($sql1);
                    $stmt1->execute([':desc' => '%Request ' . $request_id . '%']);
                    
                    // Delete the request
                    $sql2 = "DELETE FROM hc_medical_requests WHERE request_id = :request_id";
                    $stmt2 = $pdo->prepare($sql2);
                    $stmt2->execute([':request_id' => $request_id]);
                    
                    $pdo->commit();
                    
                    // Redirect to dashboard
                    header('Location: ' . $user_role . '-dashboard.php?success=request_deleted');
                    exit();
                } else {
                    $message = 'You do not have permission to delete this request';
                    $message_type = 'danger';
                }
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Error deleting request: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'] ?? '';
    
    if ($request_id && in_array($new_status, ['pending', 'responded', 'closed'])) {
        try {
            $check_sql = "SELECT patient_id FROM hc_medical_requests WHERE request_id = :request_id";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([':request_id' => $request_id]);
            $request_data = $check_stmt->fetch();
            
            if ($request_data) {
                $can_update = false;
                
                if ($user_role === 'admin' || $user_role === 'doctor' || $user_role === 'volunteer') {
                    $can_update = true;
                } elseif ($user_role === 'patient' && $request_data['patient_id'] == $user_id && $new_status === 'closed') {
                    // Patients can only close their own requests
                    $can_update = true;
                }
                
                if ($can_update) {
                    $update_data = [':status' => $new_status, ':request_id' => $request_id];
                    
                    if ($new_status === 'closed') {
                        $sql = "UPDATE hc_medical_requests SET request_status = :status, closed_at = NOW() WHERE request_id = :request_id";
                    } else {
                        $sql = "UPDATE hc_medical_requests SET request_status = :status WHERE request_id = :request_id";
                    }
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($update_data);
                    
                    // Log activity
                    $log_sql = "INSERT INTO hc_activity_logs (user_id, activity_type, activity_description) 
                                VALUES (:user_id, 'status_update', :description)";
                    $log_stmt = $pdo->prepare($log_sql);
                    $log_stmt->execute([
                        ':user_id' => $user_id,
                        ':description' => 'Changed request ' . $request_id . ' status to ' . $new_status
                    ]);
                    
                    $message = 'Status updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'You do not have permission to update this status';
                    $message_type = 'danger';
                }
            }
        } catch (Exception $e) {
            $message = 'Error updating status: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Get request details
$request = null;
$similar_requests = [];
$activities = [];

try {
    if ($request_id) {
        // Get main request details
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
        
        if ($request) {
            // Check permissions (simplified for now)
            $can_view = true; // Allow everyone for testing
            
            if (!$can_view) {
                header('Location: ' . $user_role . '-dashboard.php?error=no_permission');
                exit();
            }
            
            // Get similar requests
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
            
            // Get activity logs
            $activity_sql = "SELECT * FROM hc_activity_logs 
                             WHERE activity_description LIKE :request_desc
                             ORDER BY activity_date DESC";
            $activity_stmt = $pdo->prepare($activity_sql);
            $activity_stmt->execute([':request_desc' => '%Request ' . $request_id . '%']);
            $activities = $activity_stmt->fetchAll();
        }
    }
} catch (Exception $e) {
    $message = 'Database error: ' . $e->getMessage();
    $message_type = 'danger';
}

// Helper functions
function formatDate($date) {
    if (!$date) return 'Not yet';
    $dateTime = new DateTime($date);
    return $dateTime->format('F j, Y \a\t g:i A');
}

function getStatusBadge($status) {
    $badges = [
        'pending' => ['warning', 'Pending'],
        'responded' => ['success', 'Responded'],
        'closed' => ['secondary', 'Closed']
    ];
    return $badges[$status] ?? ['secondary', 'Unknown'];
}

function getUrgencyBadge($urgency) {
    $badges = [
        'low' => ['success', 'Low'],
        'medium' => ['warning', 'Medium'],
        'high' => ['danger', 'High']
    ];
    return $badges[$urgency] ?? ['secondary', 'Unknown'];
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
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --success-color: #4cc9f0;
            --warning-color: #f8961e;
            --danger-color: #f72585;
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
        
        .request-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            padding: 30px;
            margin-top: 30px;
        }
        
        .info-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-color);
        }
        
        .response-box {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-radius: 12px;
            padding: 25px;
            border-left: 4px solid var(--success-color);
        }
        
        .action-btn {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .description-box {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            white-space: pre-wrap;
            line-height: 1.6;
        }
        
        .activity-item {
            border-left: 3px solid var(--primary-color);
            padding-left: 15px;
            margin-bottom: 20px;
            position: relative;
        }
        
        .activity-item::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary-color);
        }
        
        .similar-request {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 3px solid var(--primary-color);
            background: white;
            transition: all 0.3s ease;
        }
        
        .similar-request:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .modal-backdrop {
            z-index: 1040;
        }
        
        .modal {
            z-index: 1050;
        }
        
        .btn-action-group .btn {
            margin-right: 5px;
            margin-bottom: 5px;
        }
        
        @media (max-width: 768px) {
            .request-header {
                padding: 20px;
            }
            
            .request-card {
                padding: 20px;
            }
            
            .btn-action-group .btn {
                width: 100%;
                margin-right: 0;
            }
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
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $user_role . '-dashboard.php'; ?>">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create-request.php">
                            <i class="fas fa-plus-circle me-1"></i> New Request
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my-requests.php">
                            <i class="fas fa-list me-1"></i> My Requests
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
    <div class="container py-4">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!$request): ?>
            <!-- Request Not Found -->
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-exclamation-triangle fa-4x text-warning"></i>
                </div>
                <h2 class="mb-3">Request Not Found</h2>
                <p class="text-muted mb-4">The request you're looking for doesn't exist or has been removed.</p>
                <a href="<?php echo $user_role . '-dashboard.php'; ?>" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                </a>
            </div>
        <?php else: ?>
            <!-- Request Header -->
            <div class="request-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center mb-3">
                            <a href="<?php echo $user_role . '-dashboard.php'; ?>" class="btn btn-light me-3">
                                <i class="fas fa-arrow-left"></i>
                            </a>
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
                            <?php 
                            $status_badge = getStatusBadge($request['request_status']);
                            $urgency_badge = getUrgencyBadge($request['urgency_level']);
                            ?>
                            <span class="badge bg-light text-dark">
                                <i class="fas fa-clock me-1"></i>
                                <?php echo formatDate($request['request_date']); ?>
                            </span>
                            <span class="badge bg-<?php echo $status_badge[0]; ?>">
                                <?php echo $status_badge[1]; ?>
                            </span>
                            <span class="badge bg-<?php echo $urgency_badge[0]; ?>">
                                <i class="fas fa-exclamation-circle me-1"></i>
                                <?php echo $urgency_badge[1]; ?> Priority
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Request Details -->
            <div class="row mt-4">
                <!-- Left Column -->
                <div class="col-lg-8">
                    <div class="request-card">
                        <!-- Action Buttons -->
                        <div class="btn-action-group d-flex flex-wrap justify-content-end mb-4 gap-2">
                            <!-- Status Update Form -->
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="update_status" value="1">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                        <i class="fas fa-exchange-alt me-1"></i> Change Status
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><button type="submit" name="status" value="pending" class="dropdown-item <?php echo $request['request_status'] == 'pending' ? 'active' : ''; ?>">Mark as Pending</button></li>
                                        <li><button type="submit" name="status" value="responded" class="dropdown-item <?php echo $request['request_status'] == 'responded' ? 'active' : ''; ?>">Mark as Responded</button></li>
                                        <li><button type="submit" name="status" value="closed" class="dropdown-item <?php echo $request['request_status'] == 'closed' ? 'active' : ''; ?>">Mark as Closed</button></li>
                                    </ul>
                                </div>
                            </form>
                            
                            <?php if ($user_role === 'patient' && $request['patient_id'] == $user_id): ?>
                                <!-- Edit Button (for patients only on their own pending requests) -->
                                <?php if ($request['request_status'] === 'pending'): ?>
                                    <a href="edit-request.php?id=<?php echo $request_id; ?>" class="btn btn-warning">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </a>
                                <?php endif; ?>
                                
                                <!-- Delete Button -->
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                    <i class="fas fa-trash me-1"></i> Delete
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($user_role === 'admin' || $user_role === 'doctor' || $user_role === 'volunteer'): ?>
                                <!-- Respond Button for staff -->
                                <?php if ($request['request_status'] === 'pending'): ?>
                                    <a href="respond-requests.php?id=<?php echo $request_id; ?>" class="btn btn-success">
                                        <i class="fas fa-comment-medical me-1"></i> Provide Response
                                    </a>
                                <?php endif; ?>
                                
                                <!-- Admin Actions -->
                                <?php if ($user_role === 'admin'): ?>
                                    <button type="button" class="btn btn-info">
                                        <i class="fas fa-user-md me-1"></i> Assign Doctor
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <button onclick="window.print()" class="btn btn-secondary">
                                <i class="fas fa-print me-1"></i> Print
                            </button>
                        </div>

                        <!-- Description -->
                        <div class="mb-5">
                            <h4 class="fw-bold mb-3 text-primary">
                                <i class="fas fa-align-left me-2"></i> Description
                            </h4>
                            <div class="description-box">
                                <?php echo nl2br(htmlspecialchars($request['request_description'])); ?>
                            </div>
                        </div>

                        <!-- Response (if available) -->
                        <?php if ($request['request_status'] === 'responded' || $request['request_status'] === 'closed'): ?>
                            <div class="mb-5">
                                <h4 class="fw-bold mb-3 text-success">
                                    <i class="fas fa-comment-medical me-2"></i> Medical Response
                                </h4>
                                <div class="response-box">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="text-muted small">Responded By</div>
                                            <div class="fw-bold">
                                                <?php echo $request['responder_name'] ? htmlspecialchars($request['responder_name']) : 'Medical Professional'; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-muted small">Response Date</div>
                                            <div class="fw-bold">
                                                <?php echo formatDate($request['response_date']); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-muted small mb-2">Professional Advice</div>
                                    <div class="bg-white p-3 rounded">
                                        <?php echo nl2br(htmlspecialchars($request['response_text'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Activity Log -->
                        <?php if (!empty($activities)): ?>
                            <div class="mb-4">
                                <h4 class="fw-bold mb-3 text-primary">
                                    <i class="fas fa-history me-2"></i> Activity History
                                </h4>
                                <?php foreach ($activities as $activity): ?>
                                    <div class="activity-item">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($activity['activity_description']); ?></div>
                                                <small class="text-muted">
                                                    <?php echo formatDate($activity['activity_date']); ?>
                                                </small>
                                            </div>
                                            <span class="badge bg-light text-dark">
                                                <?php echo htmlspecialchars($activity['activity_type']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-lg-4">
                    <!-- Patient Info -->
                    <div class="info-box">
                        <h5 class="fw-bold mb-3 text-primary">
                            <i class="fas fa-user-injured me-2"></i> Patient Information
                        </h5>
                        <div class="row">
                            <div class="col-12 mb-2">
                                <div class="text-muted small">Name</div>
                                <div class="fw-bold"><?php echo htmlspecialchars($request['patient_name']); ?></div>
                            </div>
                            <div class="col-12 mb-2">
                                <div class="text-muted small">Request Date</div>
                                <div class="fw-bold"><?php echo formatDate($request['request_date']); ?></div>
                            </div>
                            <div class="col-12 mb-2">
                                <div class="text-muted small">Location</div>
                                <div class="fw-bold"><?php echo $request['patient_location'] ? htmlspecialchars($request['patient_location']) : 'Not specified'; ?></div>
                            </div>
                            <div class="col-12 mb-2">
                                <div class="text-muted small">Category</div>
                                <div class="fw-bold"><?php echo $request['category'] ? htmlspecialchars($request['category']) : 'General'; ?></div>
                            </div>
                            <?php if ($request['request_status'] === 'closed'): ?>
                                <div class="col-12">
                                    <div class="text-muted small">Closed Date</div>
                                    <div class="fw-bold"><?php echo formatDate($request['closed_at']); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Similar Requests -->
                    <?php if (!empty($similar_requests)): ?>
                        <div class="info-box">
                            <h5 class="fw-bold mb-3 text-primary">
                                <i class="fas fa-clone me-2"></i> Other Requests
                            </h5>
                            <?php foreach ($similar_requests as $similar): ?>
                                <a href="view-request.php?id=<?php echo $similar['request_id']; ?>" 
                                   class="similar-request d-block text-decoration-none">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="fw-bold text-dark">
                                                <?php echo htmlspecialchars($similar['request_title']); ?>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo formatDate($similar['request_date']); ?>
                                            </small>
                                        </div>
                                        <?php $status = getStatusBadge($similar['request_status']); ?>
                                        <span class="badge bg-<?php echo $status[0]; ?>">
                                            <?php echo $status[1]; ?>
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

                    <!-- Quick Stats -->
                    <div class="info-box">
                        <h5 class="fw-bold mb-3 text-primary">
                            <i class="fas fa-chart-bar me-2"></i> Quick Stats
                        </h5>
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="fw-bold fs-4"><?php echo count($similar_requests); ?></div>
                                <div class="text-muted small">Other Requests</div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="fw-bold fs-4"><?php echo count($activities); ?></div>
                                <div class="text-muted small">Activities</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i> Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this request?</p>
                    <p class="fw-bold">"<?php echo htmlspecialchars($request['request_title']); ?>"</p>
                    <p class="text-danger"><small>This action cannot be undone. All associated data will be permanently deleted.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="delete_request" value="1">
                        <button type="submit" class="btn btn-danger">Delete Permanently</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-heartbeat me-2 text-primary"></i>HealthConnect
                    </h6>
                    <p class="small mb-0">Bridging healthcare gaps through community support.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="small mb-0">
                        Request ID: HC-<?php echo str_pad($request_id, 6, '0', STR_PAD_LEFT); ?> | 
                        Viewed: <?php echo date('M j, Y g:i A'); ?>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Print functionality
        function printRequest() {
            window.print();
        }
        
        // Confirm before important actions
        document.addEventListener('DOMContentLoaded', function() {
            // Status change confirmation
            const statusButtons = document.querySelectorAll('button[name="status"]');
            statusButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const newStatus = this.value;
                    const statusNames = {
                        'pending': 'Pending',
                        'responded': 'Responded', 
                        'closed': 'Closed'
                    };
                    
                    if (!confirm(`Change status to "${statusNames[newStatus]}"?`)) {
                        e.preventDefault();
                    }
                });
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
            
            // Copy request ID
            document.getElementById('copyRequestId')?.addEventListener('click', function() {
                const requestId = 'HC-<?php echo str_pad($request_id, 6, '0', STR_PAD_LEFT); ?>';
                navigator.clipboard.writeText(requestId).then(() => {
                    alert('Request ID copied: ' + requestId);
                });
            });
        });
    </script>
</body>
</html>