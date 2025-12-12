<?php
// healthconnect/views/auth/profile.php
session_start();
require_once '../../app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];

// Get current user data
$sql = "SELECT full_name, email_address, profession, location, date_created, is_approved FROM hc_users WHERE user_id = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$user = $stmt->fetch();

// Get user statistics based on role
$stats = [];
if ($user_role === 'patient') {
    $sql = "SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN request_status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
        SUM(CASE WHEN request_status = 'responded' THEN 1 ELSE 0 END) as responded_requests
        FROM hc_medical_requests WHERE patient_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    $stats = $stmt->fetch();
} elseif ($user_role === 'volunteer') {
    $sql = "SELECT 
        COUNT(*) as total_responses,
        SUM(CASE WHEN request_status = 'responded' AND responded_by_user_id = :user_id THEN 1 ELSE 0 END) as helped_patients
        FROM hc_medical_requests";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    $stats = $stmt->fetch();
} elseif ($user_role === 'doctor') {
    $sql = "SELECT 
        COUNT(*) as total_tips,
        SUM(total_likes) as total_likes,
        SUM(total_views) as total_views
        FROM hc_health_tips WHERE doctor_user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    $stats = $stmt->fetch();
}

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $profession = $_POST['profession'] ?? '';
    $location = $_POST['location'] ?? '';
    
    $sql = "UPDATE hc_users SET profession = :profession, location = :location WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([
        ':profession' => $profession,
        ':location' => $location,
        ':user_id' => $user_id
    ])) {
        $message = '<div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i>Profile updated successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
        $_SESSION['profession'] = $profession;
        $_SESSION['location'] = $location;
        
        // Refresh user data
        $sql = "SELECT full_name, email_address, profession, location FROM hc_users WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        $user = $stmt->fetch();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            --success-gradient: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            --warning-gradient: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
        }
        
        .profile-header {
            background: var(--primary-gradient);
            color: white;
            padding: 60px 0 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
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
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: var(--primary-gradient);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
            border: 5px solid white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin: 0 auto;
        }
        
        .profile-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }
        
        .stat-card {
            border-radius: 12px;
            padding: 20px;
            color: white;
            margin-bottom: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.patient { background: linear-gradient(135deg, #0dcaf0, #0d6efd); }
        .stat-card.volunteer { background: linear-gradient(135deg, #20c997, #198754); }
        .stat-card.doctor { background: linear-gradient(135deg, #6f42c1, #d63384); }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 15px;
        }
        
        .form-control:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(67, 97, 238, 0.3);
        }
        
        .user-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-patient { background: #0dcaf0; color: white; }
        .badge-volunteer { background: #20c997; color: white; }
        .badge-doctor { background: #6f42c1; color: white; }
        .badge-admin { background: #fd7e14; color: white; }
        
        .info-item {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 14px;
        }
        
        .info-value {
            font-weight: 500;
            color: #333;
        }
        
        .nav-back-btn {
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .nav-back-btn:hover {
            color: rgba(255,255,255,0.8);
            transform: translateX(-3px);
        }
        
        .member-since {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #856404;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
            margin-top: 10px;
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
                        <a class="nav-link" href="<?php echo $user_role; ?>-dashboard.php">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">
                            <i class="fas fa-user me-1"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog me-1"></i> Settings
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="profile-avatar-small me-2" style="width: 35px; height: 35px; background: var(--primary-gradient); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: bold;">
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

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3 text-center text-md-start">
                    <div class="profile-avatar mb-3">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <h1 class="fw-bold mb-2"><?php echo htmlspecialchars($user['full_name']); ?></h1>
                    <div class="d-flex align-items-center mb-3">
                        <span class="user-badge badge-<?php echo $user_role; ?> me-3">
                            <i class="fas fa-user-tag me-1"></i>
                            <?php echo ucfirst($user_role); ?>
                        </span>
                        
                        <?php if ($user_role === 'doctor'): ?>
                            <span class="badge <?php echo $user['is_approved'] ? 'bg-success' : 'bg-warning'; ?>">
                                <i class="fas fa-<?php echo $user['is_approved'] ? 'check-circle' : 'clock'; ?> me-1"></i>
                                <?php echo $user['is_approved'] ? 'Verified Doctor' : 'Pending Approval'; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <p class="lead mb-0">
                        <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($user['email_address']); ?>
                    </p>
                    
                    <?php if ($user['date_created']): ?>
                        <div class="member-since">
                            <i class="fas fa-calendar-star me-2"></i>
                            Member since <?php echo date('F Y', strtotime($user['date_created'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-3 text-end">
                    <a href="<?php echo $user_role; ?>-dashboard.php" class="nav-back-btn">
                        <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="row">
            <!-- Left Column: Statistics -->
            <div class="col-lg-4">
                <div class="profile-card mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-4"><i class="fas fa-chart-line me-2 text-primary"></i> Your Statistics</h5>
                        
                        <?php if ($user_role === 'patient'): ?>
                            <div class="stat-card patient">
                                <div class="stat-icon">
                                    <i class="fas fa-file-medical"></i>
                                </div>
                                <h3 class="fw-bold"><?php echo $stats['total_requests'] ?? 0; ?></h3>
                                <p class="mb-0">Total Requests</p>
                            </div>
                            
                            <div class="row">
                                <div class="col-6">
                                    <div class="text-center p-3 border rounded">
                                        <h5 class="fw-bold text-warning"><?php echo $stats['pending_requests'] ?? 0; ?></h5>
                                        <small class="text-muted">Pending</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-3 border rounded">
                                        <h5 class="fw-bold text-success"><?php echo $stats['responded_requests'] ?? 0; ?></h5>
                                        <small class="text-muted">Responded</small>
                                    </div>
                                </div>
                            </div>
                            
                        <?php elseif ($user_role === 'volunteer'): ?>
                            <div class="stat-card volunteer">
                                <div class="stat-icon">
                                    <i class="fas fa-hands-helping"></i>
                                </div>
                                <h3 class="fw-bold"><?php echo $stats['total_responses'] ?? 0; ?></h3>
                                <p class="mb-0">Responses Given</p>
                            </div>
                            
                            <div class="text-center p-4 border rounded mt-3">
                                <h5 class="fw-bold text-success"><?php echo $stats['helped_patients'] ?? 0; ?></h5>
                                <small class="text-muted">Patients Helped</small>
                            </div>
                            
                        <?php elseif ($user_role === 'doctor'): ?>
                            <div class="stat-card doctor">
                                <div class="stat-icon">
                                    <i class="fas fa-lightbulb"></i>
                                </div>
                                <h3 class="fw-bold"><?php echo $stats['total_tips'] ?? 0; ?></h3>
                                <p class="mb-0">Health Tips</p>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-6">
                                    <div class="text-center p-3 border rounded">
                                        <h5 class="fw-bold text-danger"><?php echo number_format($stats['total_likes'] ?? 0); ?></h5>
                                        <small class="text-muted">Total Likes</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-3 border rounded">
                                        <h5 class="fw-bold text-info"><?php echo number_format($stats['total_views'] ?? 0); ?></h5>
                                        <small class="text-muted">Total Views</small>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Info -->
                <div class="profile-card">
                    <div class="card-body">
                        <h5 class="fw-bold mb-4"><i class="fas fa-info-circle me-2 text-info"></i> Quick Info</h5>
                        
                        <div class="info-item">
                            <div class="info-label">Account Type</div>
                            <div class="info-value">
                                <span class="badge bg-primary"><?php echo ucfirst($user_role); ?></span>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Email Address</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['email_address']); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Registration Date</div>
                            <div class="info-value"><?php echo date('F d, Y', strtotime($user['date_created'])); ?></div>
                        </div>
                        
                        <?php if ($user_role === 'doctor'): ?>
                            <div class="info-item">
                                <div class="info-label">Verification Status</div>
                                <div class="info-value">
                                    <span class="badge <?php echo $user['is_approved'] ? 'bg-success' : 'bg-warning'; ?>">
                                        <?php echo $user['is_approved'] ? 'Verified' : 'Pending Review'; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Edit Profile -->
            <div class="col-lg-8">
                <div class="profile-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="fw-bold mb-0"><i class="fas fa-user-edit me-2 text-primary"></i> Edit Profile</h4>
                            <a href="change-password.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-key me-1"></i> Change Password
                            </a>
                        </div>
                        
                        <?php echo $message; ?>
                        
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Full Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" readonly>
                                    </div>
                                    <small class="text-muted">Contact admin to change your name</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email_address']); ?>" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Profession</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
                                        <input type="text" name="profession" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['profession'] ?? ''); ?>" 
                                               placeholder="e.g., Nurse, Medical Student, Doctor" required>
                                    </div>
                                    <small class="text-muted">Your medical/healthcare background</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Location</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                        <input type="text" name="location" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>" 
                                               placeholder="e.g., Accra, Ghana" required>
                                    </div>
                                    <small class="text-muted">City/Region where you're based</small>
                                </div>
                            </div>
                            
                            <?php if ($user_role === 'doctor' && !$user['is_approved']): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Pending Verification:</strong> Your account is awaiting admin approval. You'll gain full access once verified.
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <a href="<?php echo $user_role; ?>-dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> Save Changes
                                </button>
                            </div>
                        </form>
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
                        <i class="fas fa-heartbeat text-primary me-2"></i>
                        HealthConnect Profile Management
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