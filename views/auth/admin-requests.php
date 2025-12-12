<?php
// healthconnect/views/auth/admin-requests.php
session_start();
require_once '../../app/config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php?error=required');
    exit();
}

$user_name = $_SESSION['user_name'];

// Handle request actions (assign, close, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];
    $admin_notes = $_POST['admin_notes'] ?? '';
    
    if ($action === 'assign') {
        $assign_to = $_POST['assign_to'];
        $sql = "UPDATE hc_medical_requests 
                SET responded_by_user_id = :user_id,
                    admin_assigned = 1,
                    admin_notes = :notes,
                    assigned_at = NOW()
                WHERE request_id = :request_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $assign_to,
            ':notes' => $admin_notes,
            ':request_id' => $request_id
        ]);
        $_SESSION['message'] = 'Request assigned successfully!';
        
    } elseif ($action === 'close') {
        $sql = "UPDATE hc_medical_requests 
                SET request_status = 'closed',
                    admin_notes = CONCAT(IFNULL(admin_notes, ''), '\n[Admin closed: ', :notes, ']'),
                    closed_at = NOW()
                WHERE request_id = :request_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':notes' => $admin_notes,
            ':request_id' => $request_id
        ]);
        $_SESSION['message'] = 'Request closed.';
        
    } elseif ($action === 'reopen') {
        $sql = "UPDATE hc_medical_requests 
                SET request_status = 'pending',
                    admin_notes = CONCAT(IFNULL(admin_notes, ''), '\n[Admin reopened: ', :notes, ']')
                WHERE request_id = :request_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':notes' => $admin_notes,
            ':request_id' => $request_id
        ]);
        $_SESSION['message'] = 'Request reopened.';
    }
    
    header('Location: admin-requests.php');
    exit();
}

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$urgency = $_GET['urgency'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build query with filters
$sql = "SELECT r.*, 
               p.full_name as patient_name, p.email_address as patient_email,
               v.full_name as volunteer_name,
               a.full_name as assigned_by_name
        FROM hc_medical_requests r
        LEFT JOIN hc_users p ON r.patient_id = p.user_id
        LEFT JOIN hc_users v ON r.responded_by_user_id = v.user_id
        LEFT JOIN hc_users a ON r.admin_assigned_by = a.user_id
        WHERE 1=1";
        
$params = [];

if ($status !== 'all') {
    $sql .= " AND r.request_status = :status";
    $params[':status'] = $status;
}

if ($urgency !== 'all') {
    $sql .= " AND r.urgency_level = :urgency";
    $params[':urgency'] = $urgency;
}

if ($date_from) {
    $sql .= " AND DATE(r.request_date) >= :date_from";
    $params[':date_from'] = $date_from;
}

if ($date_to) {
    $sql .= " AND DATE(r.request_date) <= :date_to";
    $params[':date_to'] = $date_to;
}

if ($search) {
    $sql .= " AND (r.request_title LIKE :search OR r.request_description LIKE :search OR p.full_name LIKE :search)";
    $params[':search'] = "%$search%";
}

$sql .= " ORDER BY r.request_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Get volunteers for assignment
$volunteers_sql = "SELECT user_id, full_name FROM hc_users WHERE user_role = 'volunteer' AND is_active = 1";
$volunteers_stmt = $pdo->query($volunteers_sql);
$volunteers = $volunteers_stmt->fetchAll();

// Get request statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN request_status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN request_status = 'responded' THEN 1 ELSE 0 END) as responded,
    SUM(CASE WHEN request_status = 'closed' THEN 1 ELSE 0 END) as closed,
    SUM(CASE WHEN urgency_level = 'high' THEN 1 ELSE 0 END) as high_urgency,
    SUM(CASE WHEN admin_assigned = 1 THEN 1 ELSE 0 END) as admin_assigned
    FROM hc_medical_requests";
$stats_stmt = $pdo->query($stats_sql);
$request_stats = $stats_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Requests - HealthConnect Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .request-card {
            border-left: 4px solid;
            margin-bottom: 15px;
            border-radius: 8px;
        }
        .request-pending { border-left-color: #ffc107; }
        .request-responded { border-left-color: #0dcaf0; }
        .request-closed { border-left-color: #198754; }
        .urgency-high { background: #f8d7da; }
        .urgency-medium { background: #fff3cd; }
        .urgency-low { background: #d1ecf1; }
        .filter-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
        .stats-card {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            color: white;
            margin-bottom: 15px;
        }
        .stats-total { background: linear-gradient(135deg, #6c757d, #495057); }
        .stats-pending { background: linear-gradient(135deg, #ffc107, #fd7e14); }
        .stats-responded { background: linear-gradient(135deg, #0dcaf0, #0d6efd); }
        .stats-closed { background: linear-gradient(135deg, #198754, #146c43); }
    </style>
</head>
<body>
    <div class="container-fluid mt-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-file-medical text-primary"></i> Manage Medical Requests</h2>
            <div>
                <a href="admin-dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#exportModal">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card stats-total">
                    <h3><?php echo $request_stats['total']; ?></h3>
                    <p class="mb-0">Total Requests</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-pending">
                    <h3><?php echo $request_stats['pending']; ?></h3>
                    <p class="mb-0">Pending</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-responded">
                    <h3><?php echo $request_stats['responded']; ?></h3>
                    <p class="mb-0">Responded</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-closed">
                    <h3><?php echo $request_stats['closed']; ?></h3>
                    <p class="mb-0">Closed</p>
                </div>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-card mb-4">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="responded" <?php echo $status === 'responded' ? 'selected' : ''; ?>>Responded</option>
                        <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Urgency</label>
                    <select name="urgency" class="form-select">
                        <option value="all" <?php echo $urgency === 'all' ? 'selected' : ''; ?>>All Urgency</option>
                        <option value="high" <?php echo $urgency === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="medium" <?php echo $urgency === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="low" <?php echo $urgency === 'low' ? 'selected' : ''; ?>>Low</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-12 mt-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="admin-requests.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Requests List -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i> Medical Requests
                            <span class="badge bg-secondary"><?php echo count($requests); ?> found</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($requests)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h4>No requests found</h4>
                                <p class="text-muted">Try changing your filters</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Request</th>
                                            <th>Patient</th>
                                            <th>Status</th>
                                            <th>Urgency</th>
                                            <th>Date</th>
                                            <th>Volunteer</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($requests as $request): ?>
                                            <tr class="urgency-<?php echo $request['urgency_level']; ?>">
                                                <td>
                                                    <strong><?php echo htmlspecialchars($request['request_title']); ?></strong><br>
                                                    <small class="text-muted">
                                                        <?php echo substr(htmlspecialchars($request['request_description']), 0, 80); ?>...
                                                    </small>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($request['patient_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($request['patient_email']); ?></small>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $status_badge = [
                                                        'pending' => 'warning',
                                                        'responded' => 'info',
                                                        'closed' => 'success'
                                                    ];
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_badge[$request['request_status']] ?? 'secondary'; ?>">
                                                        <?php echo ucfirst($request['request_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $urgency_badge = [
                                                        'high' => 'danger',
                                                        'medium' => 'warning',
                                                        'low' => 'info'
                                                    ];
                                                    ?>
                                                    <span class="badge bg-<?php echo $urgency_badge[$request['urgency_level']] ?? 'secondary'; ?>">
                                                        <i class="fas fa-exclamation-circle"></i>
                                                        <?php echo ucfirst($request['urgency_level']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?php echo date('M d, Y', strtotime($request['request_date'])); ?></small><br>
                                                    <small class="text-muted"><?php echo date('h:i A', strtotime($request['request_date'])); ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($request['volunteer_name']): ?>
                                                        <small><?php echo htmlspecialchars($request['volunteer_name']); ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not assigned</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-info" data-bs-toggle="modal" 
                                                                data-bs-target="#viewModal<?php echo $request['request_id']; ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if ($request['request_status'] === 'pending'): ?>
                                                            <button class="btn btn-outline-primary" data-bs-toggle="modal" 
                                                                    data-bs-target="#assignModal<?php echo $request['request_id']; ?>">
                                                                <i class="fas fa-user-plus"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if ($request['request_status'] !== 'closed'): ?>
                                                            <button class="btn btn-outline-success" data-bs-toggle="modal" 
                                                                    data-bs-target="#closeModal<?php echo $request['request_id']; ?>">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button class="btn btn-outline-warning" data-bs-toggle="modal" 
                                                                    data-bs-target="#reopenModal<?php echo $request['request_id']; ?>">
                                                                <i class="fas fa-redo"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            
                                            <!-- View Modal -->
                                            <div class="modal fade" id="viewModal<?php echo $request['request_id']; ?>" tabindex="-1">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Request Details</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <h5><?php echo htmlspecialchars($request['request_title']); ?></h5>
                                                            <p><strong>Patient:</strong> <?php echo htmlspecialchars($request['patient_name']); ?> (<?php echo htmlspecialchars($request['patient_email']); ?>)</p>
                                                            <p><strong>Description:</strong></p>
                                                            <div class="bg-light p-3 rounded">
                                                                <?php echo nl2br(htmlspecialchars($request['request_description'])); ?>
                                                            </div>
                                                            <?php if ($request['response_text']): ?>
                                                                <p class="mt-3"><strong>Response:</strong></p>
                                                                <div class="bg-info bg-opacity-10 p-3 rounded">
                                                                    <?php echo nl2br(htmlspecialchars($request['response_text'])); ?>
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php if ($request['admin_notes']): ?>
                                                                <p class="mt-3"><strong>Admin Notes:</strong></p>
                                                                <div class="bg-warning bg-opacity-10 p-3 rounded">
                                                                    <?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Assign Modal -->
                                            <div class="modal fade" id="assignModal<?php echo $request['request_id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Assign to Volunteer</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                                                <input type="hidden" name="action" value="assign">
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">Select Volunteer</label>
                                                                    <select name="assign_to" class="form-select" required>
                                                                        <option value="">-- Choose Volunteer --</option>
                                                                        <?php foreach ($volunteers as $volunteer): ?>
                                                                            <option value="<?php echo $volunteer['user_id']; ?>">
                                                                                <?php echo htmlspecialchars($volunteer['full_name']); ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">Admin Notes (Optional)</label>
                                                                    <textarea name="admin_notes" class="form-control" rows="3" 
                                                                              placeholder="Add any notes for the volunteer..."></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="submit" class="btn btn-primary">Assign</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Close Modal -->
                                            <div class="modal fade" id="closeModal<?php echo $request['request_id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Close Request</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                                                <input type="hidden" name="action" value="close">
                                                                
                                                                <p>Are you sure you want to close this request?</p>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">Closing Notes</label>
                                                                    <textarea name="admin_notes" class="form-control" rows="3" required
                                                                              placeholder="Why are you closing this request?"></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="submit" class="btn btn-success">Close Request</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Reopen Modal -->
                                            <div class="modal fade" id="reopenModal<?php echo $request['request_id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Reopen Request</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                                                <input type="hidden" name="action" value="reopen">
                                                                
                                                                <p>Reopen this request for further assistance?</p>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">Reopening Reason</label>
                                                                    <textarea name="admin_notes" class="form-control" rows="3" required
                                                                              placeholder="Why are you reopening this request?"></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="submit" class="btn btn-warning">Reopen Request</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Export Requests</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Export all filtered requests to:</p>
                    <div class="d-grid gap-2">
                        <a href="admin-export.php?type=csv&<?php echo http_build_query($_GET); ?>" 
                           class="btn btn-outline-primary">
                            <i class="fas fa-file-csv"></i> CSV Format
                        </a>
                        <a href="admin-export.php?type=pdf&<?php echo http_build_query($_GET); ?>" 
                           class="btn btn-outline-danger">
                            <i class="fas fa-file-pdf"></i> PDF Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>