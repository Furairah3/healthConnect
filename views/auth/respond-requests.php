<?php
// healthconnect/views/auth/respond-requests.php
session_start();
require_once '../../app/config/database.php';

// Check if user is logged in and is a doctor/volunteer/admin
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?error=required');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];

// Check if user can respond
if (!in_array($user_role, ['doctor', 'volunteer', 'admin'])) {
    header('Location: ' . $user_role . '-dashboard.php?error=no_permission');
    exit();
}

// Get all pending requests with filters
$search = $_GET['search'] ?? '';
$urgency = $_GET['urgency'] ?? '';
$category = $_GET['category'] ?? '';

$sql = "SELECT r.request_id, r.request_title, r.request_description, 
               r.request_date, r.urgency_level, r.category,
               u.full_name as patient_name, u.location,
               TIMESTAMPDIFF(HOUR, r.request_date, NOW()) as hours_old
        FROM hc_medical_requests r
        JOIN hc_users u ON r.patient_id = u.user_id
        WHERE r.request_status = 'pending'";

$params = [];

if ($search) {
    $sql .= " AND (r.request_title LIKE :search OR r.request_description LIKE :search OR u.full_name LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($urgency && in_array($urgency, ['low', 'medium', 'high'])) {
    $sql .= " AND r.urgency_level = :urgency";
    $params[':urgency'] = $urgency;
}

if ($category) {
    $sql .= " AND r.category = :category";
    $params[':category'] = $category;
}

$sql .= " ORDER BY 
          CASE WHEN r.urgency_level = 'high' THEN 1
               WHEN r.urgency_level = 'medium' THEN 2
               ELSE 3
          END,
          r.request_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Get categories for filter
$category_sql = "SELECT DISTINCT category FROM hc_medical_requests WHERE category IS NOT NULL AND category != ''";
$category_stmt = $pdo->query($category_sql);
$categories = $category_stmt->fetchAll(PDO::FETCH_COLUMN);

// Get statistics
$stats_sql = "SELECT 
              SUM(CASE WHEN urgency_level = 'high' THEN 1 ELSE 0 END) as high_count,
              SUM(CASE WHEN urgency_level = 'medium' THEN 1 ELSE 0 END) as medium_count,
              SUM(CASE WHEN urgency_level = 'low' THEN 1 ELSE 0 END) as low_count,
              COUNT(*) as total_count
              FROM hc_medical_requests WHERE request_status = 'pending'";
$stats_stmt = $pdo->query($stats_sql);
$stats = $stats_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respond to Requests - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #052c65;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --success-color: #198754;
        }
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e3f2fd 100%);
            min-height: 100vh;
        }
        
        .requests-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 60px 0 30px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            border-radius: 15px;
            padding: 20px;
            color: white;
            text-align: center;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .stat-high { background: linear-gradient(135deg, var(--danger-color), #fd7e14); }
        .stat-medium { background: linear-gradient(135deg, var(--warning-color), #fd7e14); }
        .stat-low { background: linear-gradient(135deg, var(--success-color), #20c997); }
        .stat-total { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); }
        
        .request-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        
        .request-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .urgency-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .urgency-high { background: var(--danger-color); color: white; }
        .urgency-medium { background: var(--warning-color); color: black; }
        .urgency-low { background: var(--success-color); color: white; }
        
        .time-badge {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 10px;
        }
        
        .filter-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 20px;
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
                        <a class="nav-link" href="doctor-dashboard.php">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="respond-requests.php">
                            <i class="fas fa-hands-helping me-1"></i> Respond to Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <span class="badge bg-primary">
                            <?php echo ucfirst($user_role); ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <div class="requests-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="fw-bold mb-3">
                        <i class="fas fa-hands-helping me-2"></i> Help Patients
                    </h1>
                    <p class="lead mb-0">Provide medical advice to patients in need</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="doctor-dashboard.php" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="container mb-4">
        <div class="row g-3">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card stat-total">
                    <div class="h2 fw-bold"><?php echo $stats['total_count'] ?? 0; ?></div>
                    <div class="small">Total Requests</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card stat-high">
                    <div class="h2 fw-bold"><?php echo $stats['high_count'] ?? 0; ?></div>
                    <div class="small">High Priority</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card stat-medium">
                    <div class="h2 fw-bold"><?php echo $stats['medium_count'] ?? 0; ?></div>
                    <div class="small">Medium Priority</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card stat-low">
                    <div class="h2 fw-bold"><?php echo $stats['low_count'] ?? 0; ?></div>
                    <div class="small">Low Priority</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Filters -->
        <div class="filter-card">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search requests..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select name="urgency" class="form-control">
                        <option value="">All Urgency Levels</option>
                        <option value="high" <?php echo $urgency === 'high' ? 'selected' : ''; ?>>High Priority</option>
                        <option value="medium" <?php echo $urgency === 'medium' ? 'selected' : ''; ?>>Medium Priority</option>
                        <option value="low" <?php echo $urgency === 'low' ? 'selected' : ''; ?>>Low Priority</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-control">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" 
                                <?php echo $category === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($cat)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i> Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Requests List -->
        <?php if (empty($requests)): ?>
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <h3>No Pending Requests</h3>
                <p class="text-muted">All current requests have been responded to. Check back later!</p>
                <a href="doctor-dashboard.php" class="btn btn-primary mt-3">
                    <i class="fas fa-home me-2"></i> Go to Dashboard
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($requests as $request): ?>
                    <div class="col-lg-6">
                        <div class="request-card p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($request['request_title']); ?></h5>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="urgency-badge urgency-<?php echo $request['urgency_level']; ?>">
                                            <?php echo ucfirst($request['urgency_level']); ?>
                                        </span>
                                        <?php if ($request['hours_old'] < 1): ?>
                                            <span class="badge bg-info time-badge">Just now</span>
                                        <?php elseif ($request['hours_old'] < 24): ?>
                                            <span class="badge bg-info time-badge"><?php echo $request['hours_old']; ?> hours ago</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary time-badge">
                                                <?php echo floor($request['hours_old'] / 24); ?> days ago
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <p class="text-muted mb-3">
                                <?php echo substr(htmlspecialchars($request['request_description']), 0, 120); ?>...
                            </p>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i>
                                        <?php echo htmlspecialchars($request['patient_name']); ?>
                                    </small>
                                    <?php if ($request['location']): ?>
                                        <small class="text-muted ms-3">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($request['location']); ?>
                                        </small>
                                    <?php endif; ?>
                                    <?php if ($request['category']): ?>
                                        <small class="text-muted ms-3">
                                            <i class="fas fa-tag me-1"></i>
                                            <?php echo htmlspecialchars(ucfirst($request['category'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <a href="view-request.php?id=<?php echo $request['request_id']; ?>" 
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="respond-request.php?id=<?php echo $request['request_id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-comment-medical me-1"></i> Respond
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">
                        <i class="fas fa-user-md text-primary me-2"></i>
                        HealthConnect Response System
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0 text-muted">
                        Showing <?php echo count($requests); ?> of <?php echo $stats['total_count'] ?? 0; ?> pending requests
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh page every 30 seconds to check for new requests
        setTimeout(() => {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>
