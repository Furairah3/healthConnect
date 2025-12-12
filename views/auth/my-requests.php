<?php
// healthconnect/views/auth/my-requests.php
session_start();
require_once '../../app/config/database.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header('Location: login.php?error=required');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Handle request deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_request'])) {
    $request_id = $_POST['request_id'];
    
    // Check if request belongs to this patient
    $check_sql = "SELECT patient_id FROM hc_medical_requests WHERE request_id = :request_id";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([':request_id' => $request_id]);
    $request = $check_stmt->fetch();
    
    if ($request && $request['patient_id'] == $user_id) {
        // Delete the request
        $delete_sql = "DELETE FROM hc_medical_requests WHERE request_id = :request_id";
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->execute([':request_id' => $request_id]);
        
        $_SESSION['success_message'] = 'Request deleted successfully.';
    } else {
        $_SESSION['error_message'] = 'Cannot delete this request.';
    }
    
    header('Location: my-requests.php');
    exit();
}

// Filter handling
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Base query
$sql = "SELECT r.request_id, r.request_title, r.request_description, 
               r.urgency_level, r.category, r.request_status, 
               r.request_date, r.response_date, r.response_text,
               u.full_name as responded_by_name,
               v.full_name as volunteer_name
        FROM hc_medical_requests r
        LEFT JOIN hc_users u ON r.responded_by_user_id = u.user_id
        LEFT JOIN hc_users v ON r.responded_by_user_id = v.user_id AND v.user_role = 'volunteer'
        WHERE r.patient_id = :patient_id";

$params = [':patient_id' => $user_id];

// Apply filters
if ($filter === 'pending') {
    $sql .= " AND r.request_status = 'pending'";
} elseif ($filter === 'responded') {
    $sql .= " AND r.request_status = 'responded'";
} elseif ($filter === 'closed') {
    $sql .= " AND r.request_status = 'closed'";
}

// Apply search
if (!empty($search)) {
    $sql .= " AND (r.request_title LIKE :search OR r.request_description LIKE :search)";
    $params[':search'] = "%$search%";
}

// Order and execute
$sql .= " ORDER BY r.request_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Count by status
$count_sql = "SELECT 
    SUM(CASE WHEN request_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN request_status = 'responded' THEN 1 ELSE 0 END) as responded_count,
    SUM(CASE WHEN request_status = 'closed' THEN 1 ELSE 0 END) as closed_count,
    COUNT(*) as total_count
    FROM hc_medical_requests 
    WHERE patient_id = :patient_id";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute([':patient_id' => $user_id]);
$counts = $count_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Health Requests - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }
        
        .page-header {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        
        .filter-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .request-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .request-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-responded { background: #d4edda; color: #155724; }
        .status-closed { background: #e2e3e5; color: #383d41; }
        
        .urgency-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .urgency-low { background: #d4edda; color: #155724; }
        .urgency-medium { background: #fff3cd; color: #856404; }
        .urgency-high { background: #f8d7da; color: #721c24; }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state-icon {
            font-size: 60px;
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .filter-btn {
            border-radius: 20px;
            padding: 8px 20px;
            margin: 0 5px 10px 5px;
            transition: all 0.3s;
        }
        
        .filter-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .response-box {
            background: #f8f9fa;
            border-left: 4px solid var(--primary-color);
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
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
                        <a class="nav-link" href="patient-dashboard.php">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create-request.php">
                            <i class="fas fa-plus-circle me-1"></i> New Request
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="my-requests.php">
                            <i class="fas fa-file-medical me-1"></i> My Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="health-tips.php">
                            <i class="fas fa-lightbulb me-1"></i> Health Tips
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="profile-avatar me-2" style="width: 35px; height: 35px; background: linear-gradient(135deg, #4361ee, #3a0ca3); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: bold;">
                                <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                            </div>
                            <span><?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="fw-bold mb-3">My Health Requests</h1>
                    <p class="lead mb-0">Track and manage all your medical consultations in one place</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="create-request.php" class="btn btn-light btn-lg px-4 shadow">
                        <i class="fas fa-plus-circle me-2"></i> New Request
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="container mb-4">
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="stat-number text-primary"><?php echo $counts['total_count'] ?? 0; ?></div>
                        <div class="stat-label">Total Requests</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="stat-number text-warning"><?php echo $counts['pending_count'] ?? 0; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="stat-number text-success"><?php echo $counts['responded_count'] ?? 0; ?></div>
                        <div class="stat-label">Responded</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="stat-number text-secondary"><?php echo $counts['closed_count'] ?? 0; ?></div>
                        <div class="stat-label">Closed</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Filters -->
        <div class="filter-card">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-3">Filter Requests</h5>
                    <div class="d-flex flex-wrap">
                        <a href="?filter=all" class="btn btn-outline-primary filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
                            All <span class="badge bg-primary ms-1"><?php echo $counts['total_count'] ?? 0; ?></span>
                        </a>
                        <a href="?filter=pending" class="btn btn-outline-warning filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                            Pending <span class="badge bg-warning ms-1"><?php echo $counts['pending_count'] ?? 0; ?></span>
                        </a>
                        <a href="?filter=responded" class="btn btn-outline-success filter-btn <?php echo $filter === 'responded' ? 'active' : ''; ?>">
                            Responded <span class="badge bg-success ms-1"><?php echo $counts['responded_count'] ?? 0; ?></span>
                        </a>
                        <a href="?filter=closed" class="btn btn-outline-secondary filter-btn <?php echo $filter === 'closed' ? 'active' : ''; ?>">
                            Closed <span class="badge bg-secondary ms-1"><?php echo $counts['closed_count'] ?? 0; ?></span>
                        </a>
                    </div>
                </div>
                <div class="col-md-6">
                    <form method="GET" class="mt-3 mt-md-0">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Search requests..." value="<?php echo htmlspecialchars($search); ?>">
                            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                            <?php if (!empty($search)): ?>
                                <a href="my-requests.php?filter=<?php echo $filter; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Requests List -->
        <?php if (empty($requests)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <h4>No requests found</h4>
                <p class="text-muted mb-4">
                    <?php if (!empty($search)): ?>
                        No requests match your search criteria.
                    <?php elseif ($filter !== 'all'): ?>
                        No <?php echo $filter; ?> requests found.
                    <?php else: ?>
                        You haven't submitted any health requests yet.
                    <?php endif; ?>
                </p>
                <a href="create-request.php" class="btn btn-primary px-4">
                    <i class="fas fa-plus me-2"></i> Submit Your First Request
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($requests as $request): ?>
                <div class="request-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex align-items-start mb-2">
                                    <h5 class="fw-bold mb-0 me-3"><?php echo htmlspecialchars($request['request_title']); ?></h5>
                                    <span class="urgency-badge urgency-<?php echo $request['urgency_level']; ?>">
                                        <?php echo ucfirst($request['urgency_level']); ?> Priority
                                    </span>
                                </div>
                                
                                <p class="text-muted mb-3">
                                    <?php echo substr(htmlspecialchars($request['request_description']), 0, 150); ?>
                                    <?php if (strlen($request['request_description']) > 150): ?>...<?php endif; ?>
                                </p>
                                
                                <div class="d-flex align-items-center">
                                    <span class="status-badge status-<?php echo $request['request_status']; ?> me-3">
                                        <?php echo ucfirst($request['request_status']); ?>
                                    </span>
                                    <small class="text-muted me-3">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo date('M d, Y', strtotime($request['request_date'])); ?>
                                    </small>
                                    <?php if ($request['category']): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-tag me-1"></i>
                                            <?php echo htmlspecialchars($request['category']); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($request['response_text']): ?>
                                    <div class="response-box mt-3">
                                        <small class="text-muted d-block mb-2">
                                            <strong>Response from 
                                                <?php echo $request['responded_by_name'] ? htmlspecialchars($request['responded_by_name']) : 'HealthConnect Volunteer'; ?>:
                                            </strong>
                                        </small>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($request['response_text'])); ?></p>
                                        <?php if ($request['response_date']): ?>
                                            <small class="text-muted mt-2 d-block">
                                                <i class="fas fa-clock me-1"></i>
                                                Responded on <?php echo date('M d, Y', strtotime($request['response_date'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-4 text-end">
                                <div class="btn-group-vertical">
                                    <a href="view-request.php?id=<?php echo $request['request_id']; ?>" 
                                       class="btn btn-outline-primary mb-2">
                                        <i class="fas fa-eye me-1"></i> View Details
                                    </a>
                                    
                                    <?php if ($request['request_status'] === 'pending'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this request?');">
                                            <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                            <input type="hidden" name="delete_request" value="1">
                                            <button type="submit" class="btn btn-outline-danger">
                                                <i class="fas fa-trash me-1"></i> Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Total count -->
            <div class="text-center mt-4">
                <p class="text-muted">
                    Showing <?php echo count($requests); ?> request<?php echo count($requests) !== 1 ? 's' : ''; ?>
                    <?php if ($filter !== 'all'): ?> (filtered)<?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
        
        <!-- Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show mt-4">
                <?php echo $_SESSION['success_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mt-4">
                <?php echo $_SESSION['error_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">
                        <i class="fas fa-heartbeat text-primary me-2"></i>
                        HealthConnect Patient Portal
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0 text-muted">
                        &copy; <?php echo date('Y'); ?> HealthConnect. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>