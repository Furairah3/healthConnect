<?php
// healthconnect/views/auth/volunteer-dashboard.php
session_start();
require_once '../../app/config/database.php';

// Check if user is logged in and is a volunteer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'volunteer') {
    header('Location: login.php?error=required');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get volunteer details including profession and location
$sql = "SELECT full_name, email_address, profession, location 
        FROM hc_users 
        WHERE user_id = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$volunteer_details = $stmt->fetch();

$profession = $volunteer_details['profession'] ?? 'Healthcare Volunteer';
$location = $volunteer_details['location'] ?? 'Not specified';

// Get volunteer statistics
$sql = "SELECT 
        (SELECT COUNT(*) FROM hc_medical_requests WHERE responded_by_user_id = :volunteer_id AND request_status = 'closed') as total_helped,
        (SELECT COUNT(*) FROM hc_medical_requests WHERE request_status = 'pending') as pending_requests,
        (SELECT COUNT(*) FROM hc_medical_requests WHERE responded_by_user_id = :volunteer_id2 AND request_status = 'responded') as active_responses,
        (SELECT COUNT(*) FROM hc_medical_requests WHERE responded_by_user_id = :volunteer_id3) as total_responses";
        
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':volunteer_id' => $user_id,
    ':volunteer_id2' => $user_id,
    ':volunteer_id3' => $user_id
]);
$stats = $stmt->fetch();

// Get recent pending requests
$sql = "SELECT r.request_id, r.request_title, r.request_description, 
               r.request_date, u.full_name as patient_name, 
               u.profession as patient_profession
        FROM hc_medical_requests r
        JOIN hc_users u ON r.patient_id = u.user_id
        WHERE r.request_status = 'pending'
        ORDER BY r.request_date DESC
        LIMIT 5";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$pending_requests = $stmt->fetchAll();

// Get volunteer's recent responses
$sql = "SELECT r.request_id, r.request_title, u.full_name as patient_name,
               r.response_date, r.request_status
        FROM hc_medical_requests r
        JOIN hc_users u ON r.patient_id = u.user_id
        WHERE r.responded_by_user_id = :volunteer_id
        ORDER BY r.response_date DESC
        LIMIT 5";
$stmt = $pdo->prepare($sql);
$stmt->execute([':volunteer_id' => $user_id]);
$my_responses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Dashboard - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        :root {
            --volunteer-primary: #198754;
            --volunteer-secondary: #146c43;
            --volunteer-accent: #20c997;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, var(--volunteer-primary) 0%, var(--volunteer-secondary) 100%);
            color: white;
            padding: 60px 0 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,192C672,181,768,139,864,138.7C960,139,1056,181,1152,197.3C1248,213,1344,203,1392,197.3L1440,192L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            background-position: center;
        }
        
        .stat-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            overflow: hidden;
            position: relative;
        }
        
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
        }
        
        .stat-card.helped::before { background: linear-gradient(90deg, #198754, #20c997); }
        .stat-card.pending::before { background: linear-gradient(90deg, #ffc107, #ff9800); }
        .stat-card.active::before { background: linear-gradient(90deg, #0dcaf0, #0d6efd); }
        .stat-card.total::before { background: linear-gradient(90deg, #6f42c1, #d63384); }
        
        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin: 0 auto 20px;
        }
        
        .stat-card.helped .stat-icon { background: rgba(25, 135, 84, 0.1); color: #198754; }
        .stat-card.pending .stat-icon { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .stat-card.active .stat-icon { background: rgba(13, 202, 240, 0.1); color: #0dcaf0; }
        .stat-card.total .stat-icon { background: rgba(111, 66, 193, 0.1); color: #6f42c1; }
        
        .request-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border-left: 4px solid var(--volunteer-primary);
        }
        
        .request-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .volunteer-badge {
            background: linear-gradient(135deg, var(--volunteer-primary), var(--volunteer-secondary));
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .action-btn {
            min-width: 120px;
        }
        
        .profile-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--volunteer-primary), var(--volunteer-secondary));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: bold;
        }
        
        .welcome-message {
            background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .impact-meter {
            height: 10px;
            background: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .impact-fill {
            height: 100%;
            background: linear-gradient(90deg, #20c997, #198754);
            border-radius: 5px;
            transition: width 1s ease;
        }
        
        .volunteer-info-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .info-item i {
            width: 30px;
            color: var(--volunteer-primary);
        }
        
        .action-card {
            border: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            text-decoration: none;
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
                        <a class="nav-link active" href="volunteer-dashboard.php">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="respond-requests.php">
                            <i class="fas fa-hands-helping me-1"></i> Help Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="training.php">
                            <i class="fas fa-book-medical me-1"></i> Training
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="profile-avatar me-2">
                                <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                            </div>
                            <span><?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="my-responses.php"><i class="fas fa-history me-2"></i> My Responses</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
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
                <div class="col-lg-6">
                    <div class="welcome-message">
                        <h1 class="fw-bold mb-3">Welcome, <?php echo htmlspecialchars($user_name); ?>! 🙌</h1>
                        <p class="lead mb-0">Your volunteer work makes a real difference in rural healthcare access.</p>
                        
                        <!-- Volunteer Info -->
                        <div class="volunteer-info-card mt-4">
                            <div class="info-item">
                                <i class="fas fa-briefcase"></i>
                                <span style="color: black;"><strong>Profession:</strong> <?php echo htmlspecialchars($profession); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span style="color: black;"><strong>Location:</strong> <?php echo htmlspecialchars($location); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-envelope"></i>
                                <span style="color: black;"><strong>Email:</strong> <?php echo htmlspecialchars($volunteer_details['email_address'] ?? ''); ?></span>
                            </div>
                        </div>
                        
                        <!-- Impact Meter -->
                        <div class="mt-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-white">Your Impact Level</span>
                                <span class="text-white fw-bold"><?php echo ($stats['total_helped'] ?? 0) > 0 ? 'Active Helper' : 'Getting Started'; ?></span>
                            </div>
                            <div class="impact-meter">
                                <div class="impact-fill" style="width: <?php echo min(($stats['total_helped'] ?? 0) * 20, 100); ?>%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 text-end">
    <div class="mb-4">
        <a href="volunteer-info.php" 
           class="volunteer-badge me-3" 
           style="text-decoration: none; transition: all 0.3s;"
           onmouseover="this.style.opacity='0.8'" 
           onmouseout="this.style.opacity='1'">
            <i class="fas fa-hands-helping me-1"></i> Healthcare Volunteer
        </a>
        <a href="profile.php" 
           class="btn btn-outline-light btn-lg px-4 me-2"
           style="transition: all 0.3s;">
            <i class="fas fa-edit me-2"></i> Update Profile
        </a>
    </div>
    <a href="respond-requests.php" 
       class="btn btn-light btn-lg px-4 shadow"
       style="transition: all 0.3s;"
       onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(0,0,0,0.15)'"
       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.1)'">
        <i class="fas fa-plus-circle me-2"></i> Help Someone Now
    </a>
</div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="container mb-5">
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card helped p-4 text-center">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 class="fw-bold mb-2"><?php echo $stats['total_helped'] ?? 0; ?></h2>
                    <p class="text-muted mb-0">Successfully Helped</p>
                    <small class="text-success">Patients assisted</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card pending p-4 text-center">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h2 class="fw-bold mb-2"><?php echo $stats['pending_requests'] ?? 0; ?></h2>
                    <p class="text-muted mb-0">Pending Requests</p>
                    <small class="text-warning">Need assistance</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card active p-4 text-center">
                    <div class="stat-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h2 class="fw-bold mb-2"><?php echo $stats['active_responses'] ?? 0; ?></h2>
                    <p class="text-muted mb-0">Active Responses</p>
                    <small class="text-info">Currently helping</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card total p-4 text-center">
                    <div class="stat-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <h2 class="fw-bold mb-2"><?php echo $stats['total_responses'] ?? 0; ?></h2>
                    <p class="text-muted mb-0">Total Responses</p>
                    <small class="text-purple">All-time responses</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="row">
            <!-- Pending Requests -->
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0 py-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-0"><i class="fas fa-hands-helping text-success me-2"></i> Requests Needing Help</h4>
                                <p class="text-muted mb-0 mt-1">Patients waiting for your assistance</p>
                            </div>
                            <a href="respond-requests.php" class="btn btn-success">
                                <i class="fas fa-search me-1"></i> Browse All
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <?php if (empty($pending_requests)): ?>
                            <div class="text-center py-5">
                                <div class="mb-4">
                                    <i class="fas fa-check-circle fa-3x text-success"></i>
                                </div>
                                <h5>No pending requests!</h5>
                                <p class="text-muted mb-4">All current requests have been responded to.</p>
                                <a href="training.php" class="btn btn-outline-success">
                                    <i class="fas fa-graduation-cap me-2"></i> View Training Materials
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($pending_requests as $request): ?>
                                <div class="request-card mb-3 p-3">
                                    <h6 class="fw-bold mb-2"><?php echo htmlspecialchars($request['request_title']); ?></h6>
                                    <p class="text-muted small mb-2">
                                        <?php echo substr(htmlspecialchars($request['request_description']), 0, 100); ?>...
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i>
                                                <?php echo htmlspecialchars($request['patient_name']); ?>
                                            </small>
                                            <?php if (!empty($request['patient_profession'])): ?>
                                                <small class="text-muted ms-3">
                                                    <i class="fas fa-briefcase me-1"></i>
                                                    <?php echo htmlspecialchars($request['patient_profession']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <a href="respond-requests.php?request_id=<?php echo $request['request_id']; ?>" 
                                           class="btn btn-sm btn-success action-btn">
                                            <i class="fas fa-comment me-1"></i> Respond
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- My Recent Responses -->
            <div class="col-lg-5">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0 py-4">
                        <h4 class="mb-0"><i class="fas fa-history text-primary me-2"></i> My Recent Help</h4>
                        <p class="text-muted mb-0 mt-1">Your recent volunteer work</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if (empty($my_responses)): ?>
                            <div class="text-center py-4">
                                <div class="mb-4">
                                    <i class="fas fa-hands-helping fa-3x text-muted"></i>
                                </div>
                                <h5>Start helping!</h5>
                                <p class="text-muted mb-0">Your responses will appear here.</p>
                                <a href="respond-requests.php" class="btn btn-success mt-3">
                                    <i class="fas fa-plus-circle me-2"></i> Help First Patient
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($my_responses as $response): ?>
                                <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                    <div class="flex-shrink-0">
                                        <div class="bg-light rounded-circle p-3">
                                            <i class="fas fa-user-injured text-success"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($response['patient_name']); ?></h6>
                                        <p class="text-muted small mb-0">
                                            <?php echo htmlspecialchars(substr($response['request_title'], 0, 30)); ?>...
                                        </p>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo date('M d', strtotime($response['response_date'])); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-<?php echo $response['request_status'] === 'closed' ? 'success' : 'info'; ?>">
                                        <?php echo ucfirst($response['request_status']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-3">
                                <a href="my-responses.php" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-list me-1"></i> View All Responses
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0 py-4">
                        <h4 class="mb-0"><i class="fas fa-bolt text-warning me-2"></i> Volunteer Tools</h4>
                        <p class="text-muted mb-0 mt-1">Resources and tools for volunteers</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6">
                                <a href="training.php" class="card action-card text-decoration-none">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-graduation-cap fa-3x text-primary"></i>
                                        </div>
                                        <h6 class="fw-bold mb-2">Training Materials</h6>
                                        <p class="text-muted small mb-0">Learn best practices</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="resources.php" class="card action-card text-decoration-none">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-book-medical fa-3x text-success"></i>
                                        </div>
                                        <h6 class="fw-bold mb-2">Health Resources</h6>
                                        <p class="text-muted small mb-0">Reference materials</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="community.php" class="card action-card text-decoration-none">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-users fa-3x text-info"></i>
                                        </div>
                                        <h6 class="fw-bold mb-2">Volunteer Community</h6>
                                        <p class="text-muted small mb-0">Connect with others</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="impact.php" class="card action-card text-decoration-none">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-chart-line fa-3x text-warning"></i>
                                        </div>
                                        <h6 class="fw-bold mb-2">Your Impact</h6>
                                        <p class="text-muted small mb-0">See your contribution</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">
                        <i class="fas fa-hands-helping text-success me-2"></i>
                        HealthConnect Volunteer Dashboard
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Action card hover effect
        document.querySelectorAll('.action-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 10px 25px rgba(0,0,0,0.1)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });
        
        // Update impact meter animation
        const impactFill = document.querySelector('.impact-fill');
        if (impactFill) {
            const width = impactFill.style.width;
            impactFill.style.width = '0';
            setTimeout(() => {
                impactFill.style.width = width;
            }, 500);
        }
    </script>
</body>
</html>