<?php
// healthconnect/views/dashboard/patient.php
session_start();
require_once '../../app/config/database.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header('Location: ../auth/login.php?error=required');
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get patient's health requests
$sql = "SELECT r.request_id, r.request_title, r.request_description, 
               r.request_status, r.request_date, r.response_date,
               u.full_name as responded_by_name
        FROM hc_medical_requests r
        LEFT JOIN hc_users u ON r.responded_by_user_id = u.user_id
        WHERE r.patient_id = :patient_id
        ORDER BY r.request_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':patient_id' => $user_id]);
$requests = $stmt->fetchAll();

// Get medical tips
$sql = "SELECT t.tip_id, t.tip_title, t.tip_content, t.tip_date,
               u.full_name as doctor_name, t.total_likes
        FROM hc_health_tips t
        JOIN hc_users u ON t.doctor_user_id = u.user_id
        WHERE t.is_published = TRUE
        ORDER BY t.tip_date DESC
        LIMIT 5";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$medical_tips = $stmt->fetchAll();

// Check if user has liked tips
$liked_tips = [];
if ($medical_tips) {
    $tip_ids = array_column($medical_tips, 'tip_id');
    $placeholders = implode(',', array_fill(0, count($tip_ids), '?'));
    $sql = "SELECT tip_id FROM hc_tip_likes WHERE user_who_liked_id = ? AND tip_id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge([$user_id], $tip_ids));
    $liked_tips = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }
        .stat-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .request-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-responded {
            background: #d1ecf1;
            color: #0c5460;
        }
        .status-closed {
            background: #d4edda;
            color: #155724;
        }
        .tip-card {
            border-left: 4px solid #28a745;
        }
        .like-btn {
            cursor: pointer;
            transition: color 0.3s;
        }
        .like-btn:hover {
            color: #dc3545 !important;
        }
        .liked {
            color: #dc3545 !important;
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
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="patient.php"><i class="fas fa-home me-1"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="create-request.php"><i class="fas fa-plus-circle me-1"></i> New Request</a></li>
                    <li class="nav-item"><a class="nav-link" href="medical-tips.php"><i class="fas fa-lightbulb me-1"></i> Health Tips</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($user_name); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                        
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="fw-bold mb-3">Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1>
                    <p class="lead mb-0">How can we help you today?</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="create-request.php" class="btn btn-light btn-lg">
                        <i class="fas fa-plus-circle me-2"></i> New Health Request
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Statistics Cards -->
        <div class="row mb-5">
            <div class="col-md-3 mb-4">
                <div class="card stat-card text-center p-4 bg-primary text-white">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <h3><?php echo count($requests); ?></h3>
                    <p class="mb-0">Total Requests</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <?php
                $pending = array_filter($requests, function($req) {
                    return $req['request_status'] === 'pending';
                });
                ?>
                <div class="card stat-card text-center p-4 bg-warning text-dark">
                    <i class="fas fa-clock fa-3x mb-3"></i>
                    <h3><?php echo count($pending); ?></h3>
                    <p class="mb-0">Pending</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <?php
                $responded = array_filter($requests, function($req) {
                    return $req['request_status'] === 'responded';
                });
                ?>
                <div class="card stat-card text-center p-4 bg-success text-white">
                    <i class="fas fa-check-circle fa-3x mb-3"></i>
                    <h3><?php echo count($responded); ?></h3>
                    <p class="mb-0">Responded</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <?php
                $tips_count = count($medical_tips);
                ?>
                <div class="card stat-card text-center p-4 bg-info text-white">
                    <i class="fas fa-lightbulb fa-3x mb-3"></i>
                    <h3><?php echo $tips_count; ?></h3>
                    <p class="mb-0">Health Tips</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Requests -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-file-medical me-2"></i> Recent Health Requests</h5>
                        <a href="create-request.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> New Request
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($requests)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5>No health requests yet</h5>
                                <p class="text-muted">Submit your first health request to get started</p>
                                <a href="create-request.php" class="btn btn-primary">Create First Request</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Response</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($requests, 0, 5) as $request): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($request['request_title']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                                <td>
                                                    <span class="request-status status-<?php echo $request['request_status']; ?>">
                                                        <?php echo ucfirst($request['request_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($request['responded_by_name']): ?>
                                                        By <?php echo htmlspecialchars($request['responded_by_name']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Waiting...</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="view-request.php?id=<?php echo $request['request_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <a href="my-requests.php" class="btn btn-outline-primary">View All Requests</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Health Tips Sidebar -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i> Latest Health Tips</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($medical_tips)): ?>
                            <p class="text-muted text-center py-3">No health tips available yet.</p>
                        <?php else: ?>
                            <?php foreach ($medical_tips as $tip): ?>
                                <div class="card tip-card mb-3">
                                    <div class="card-body">
                                        <h6 class="card-title"><?php echo htmlspecialchars($tip['tip_title']); ?></h6>
                                        <p class="card-text small text-muted">
                                            <?php echo substr(htmlspecialchars($tip['tip_content']), 0, 100); ?>...
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                By Dr. <?php echo htmlspecialchars($tip['doctor_name']); ?>
                                            </small>
                                            <div>
                                                <span class="like-btn me-2 <?php echo in_array($tip['tip_id'], $liked_tips) ? 'liked' : 'text-muted'; ?>"
                                                      data-tip-id="<?php echo $tip['tip_id']; ?>">
                                                    <i class="fas fa-heart"></i> <span class="like-count"><?php echo $tip['total_likes']; ?></span>
                                                </span>
                                                <a href="../medical-tips/view.php?id=<?php echo $tip['tip_id']; ?>" class="text-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center">
                                <a href="../medical-tips/" class="btn btn-outline-success btn-sm">View All Tips</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="create-request.php" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-2"></i> New Health Request
                            </a>
                            <a href="medical-tips.php" class="btn btn-success">
                                <i class="fas fa-lightbulb me-2"></i> Browse Health Tips
                            </a>
                            <a href="profile.php" class="btn btn-outline-primary">
                                <i class="fas fa-user me-2"></i> Update Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2025 HealthConnect. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0">Patient Dashboard</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Like functionality
        document.querySelectorAll('.like-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const tipId = this.dataset.tipId;
                const likeCount = this.querySelector('.like-count');
                const heartIcon = this.querySelector('i');
                
                try {
                    const response = await fetch('../../api/tips.php?action=like', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            tip_id: tipId,
                            csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        if (result.liked) {
                            this.classList.add('liked');
                            this.classList.remove('text-muted');
                            likeCount.textContent = parseInt(likeCount.textContent) + 1;
                        } else {
                            this.classList.remove('liked');
                            this.classList.add('text-muted');
                            likeCount.textContent = parseInt(likeCount.textContent) - 1;
                        }
                    } else {
                        alert(result.message || 'Error liking tip');
                    }
                } catch (error) {
                    console.error('Like error:', error);
                    alert('An error occurred');
                }
            });
        });
    </script>
</body>
</html>