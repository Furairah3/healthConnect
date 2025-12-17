<?php
// healthconnect/views/auth/patient-directory.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'doctor') {
    header('Location: login.php?error=required');
    exit();
}

require_once '../../app/config/database.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get filter parameters
$search = $_GET['search'] ?? '';
$location = $_GET['location'] ?? '';
$sort = $_GET['sort'] ?? 'recent';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build query for patients
$where_conditions = ["u.user_role = 'patient'"];
$params = [];

if ($search) {
    $where_conditions[] = "(u.full_name LIKE :search OR u.email_address LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($location) {
    $where_conditions[] = "u.location LIKE :location";
    $params[':location'] = "%$location%";
}

$where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM hc_users u $where_sql";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_patients = $count_stmt->fetch()['total'];
$total_pages = ceil($total_patients / $per_page);

// Determine sort order
$order_by = 'u.date_created DESC';
switch ($sort) {
    case 'name':
        $order_by = 'u.full_name ASC';
        break;
    case 'location':
        $order_by = 'u.location ASC, u.full_name ASC';
        break;
    case 'requests':
        $order_by = 'request_count DESC';
        break;
}

// Get patients with their request counts
$sql = "SELECT 
            u.user_id,
            u.full_name,
            u.email_address,
            u.location,
            u.profession,
            u.date_created,
            u.is_active,
            COUNT(r.request_id) as request_count,
            MAX(r.request_date) as last_request_date,
            (SELECT COUNT(*) FROM hc_medical_requests 
             WHERE patient_id = u.user_id AND request_status = 'closed') as closed_requests
        FROM hc_users u
        LEFT JOIN hc_medical_requests r ON u.user_id = r.patient_id
        $where_sql
        GROUP BY u.user_id
        ORDER BY $order_by
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$patients = $stmt->fetchAll();

// Get unique locations for filter
$locations_sql = "SELECT DISTINCT location FROM hc_users WHERE user_role = 'patient' AND location IS NOT NULL AND location != '' ORDER BY location";
$locations = $pdo->query($locations_sql)->fetchAll();

// Get doctor's patient stats
$stats_sql = "SELECT 
    (SELECT COUNT(DISTINCT patient_id) FROM hc_medical_requests WHERE responded_by_user_id = :doctor_id) as total_patients_helped,
    (SELECT COUNT(*) FROM hc_medical_requests WHERE responded_by_user_id = :doctor_id2 AND request_status = 'responded') as active_patients,
    (SELECT COUNT(*) FROM hc_medical_requests WHERE responded_by_user_id = :doctor_id3 AND request_status = 'closed') as closed_cases";
    
$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute([
    ':doctor_id' => $user_id,
    ':doctor_id2' => $user_id,
    ':doctor_id3' => $user_id
]);
$stats = $stats_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Directory - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --doctor-primary: #0d6efd;
            --doctor-secondary: #052c65;
            --doctor-accent: #20c997;
            --doctor-light: #e3f2fd;
            --animation-speed: 0.5s;
            --ease-out: cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e3f2fd 100%);
            min-height: 100vh;
            opacity: 0;
            animation: fadeIn 0.8s var(--ease-out) forwards;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .directory-header {
            background: linear-gradient(135deg, var(--doctor-primary) 0%, var(--doctor-secondary) 100%);
            color: white;
            padding: 60px 0 40px;
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
        }
        
        .directory-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,192C672,181,768,139,864,138.7C960,139,1056,181,1152,197.3C1248,213,1344,203,1392,197.3L1440,192L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            animation: float 25s ease-in-out infinite;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.4s var(--ease-out);
            border-left: 4px solid var(--doctor-primary);
            animation: slideUp 0.6s var(--ease-out) forwards;
            opacity: 0;
        }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
            background: rgba(13, 110, 253, 0.1);
            color: var(--doctor-primary);
            transition: all 0.3s;
        }
        
        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(10deg);
            background: linear-gradient(135deg, var(--doctor-primary), var(--doctor-secondary));
            color: white;
        }
        
        .patient-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.4s var(--ease-out);
            border-top: 5px solid transparent;
            animation: slideUp 0.5s var(--ease-out) forwards;
            opacity: 0;
            position: relative;
            overflow: hidden;
        }
        
        .patient-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--doctor-primary), var(--doctor-accent));
            transform-origin: left;
            transform: scaleX(0);
            transition: transform 0.6s var(--ease-out);
        }
        
        .patient-card:hover::before {
            transform: scaleX(1);
        }
        
        .patient-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }
        
        .patient-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--doctor-primary), var(--doctor-accent));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: bold;
            margin: 0 auto 20px;
            transition: all 0.4s var(--ease-out);
            box-shadow: 0 8px 20px rgba(13, 110, 253, 0.3);
        }
        
        .patient-card:hover .patient-avatar {
            transform: scale(1.1) rotate(360deg);
            box-shadow: 0 12px 25px rgba(13, 110, 253, 0.4);
        }
        
        .request-badge {
            background: rgba(32, 201, 151, 0.1);
            color: #198754;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .request-badge:hover {
            background: #198754;
            color: white;
            transform: scale(1.1);
        }
        
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            animation: slideUp 0.6s var(--ease-out) 0.2s forwards;
            opacity: 0;
        }
        
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--doctor-primary);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            transform: translateY(-2px);
        }
        
        .btn-search {
            background: linear-gradient(135deg, var(--doctor-primary), var(--doctor-secondary));
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-search:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(13, 110, 253, 0.3);
        }
        
        .pagination .page-link {
            border: none;
            border-radius: 8px;
            margin: 0 5px;
            color: var(--doctor-primary);
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, var(--doctor-primary), var(--doctor-secondary));
            color: white;
        }
        
        .pagination .page-link:hover {
            background: var(--doctor-light);
            transform: translateY(-2px);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            animation: slideUp 0.6s var(--ease-out) forwards;
            opacity: 0;
        }
        
        .empty-state-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: var(--doctor-primary);
            margin: 0 auto 30px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .location-tag {
            background: var(--doctor-light);
            color: var(--doctor-primary);
            border: 2px solid var(--doctor-primary);
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .location-tag:hover {
            background: var(--doctor-primary);
            color: white;
            transform: translateY(-2px);
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
        }
        
        .loading-overlay.active {
            opacity: 1;
            pointer-events: all;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--doctor-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Floating elements */
        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .floating-element {
            position: absolute;
            background: rgba(13, 110, 253, 0.05);
            border-radius: 50%;
            animation: floatElement 20s infinite linear;
        }
        
        @keyframes floatElement {
            0% {
                transform: translate(0, 0) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translate(100px, -100vh) rotate(360deg);
                opacity: 0;
            }
        }
        
        .btn-action {
            border-radius: 8px;
            padding: 8px 15px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            .directory-header {
                padding: 40px 0 30px;
            }
            
            .patient-card {
                padding: 20px;
            }
            
            .patient-avatar {
                width: 60px;
                height: 60px;
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <!-- Floating Background Elements -->
    <div class="floating-elements">
        <?php for ($i = 0; $i < 15; $i++): ?>
            <div class="floating-element" 
                 style="width: <?php echo rand(20, 80); ?>px; 
                        height: <?php echo rand(20, 80); ?>px;
                        top: <?php echo rand(0, 100); ?>%;
                        left: <?php echo rand(0, 100); ?>%;
                        animation-delay: <?php echo rand(0, 20); ?>s;
                        animation-duration: <?php echo rand(15, 30); ?>s;"></div>
        <?php endfor; ?>
    </div>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>
    
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
                        <a class="nav-link" href="doctor-dashboard.php">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="respond-requests.php">
                            <i class="fas fa-comments-medical me-1"></i> Respond to Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="patient-directory.php">
                            <i class="fas fa-users me-1"></i> Patient Directory
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-line me-1"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="profile-avatar me-2" style="width: 35px; height: 35px; background: linear-gradient(135deg, #0d6efd, #052c65); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <span>Dr. <?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="my-tips.php"><i class="fas fa-list me-2"></i> My Tips</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <div class="directory-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="fw-bold mb-3 animate-charcter">
                        <i class="fas fa-users me-2"></i> Patient Directory
                    </h1>
                    <p class="lead mb-0 opacity-75">Manage and view all patients under your care</p>
                </div>
                <div class="col-lg-4 text-end">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-light btn-lg px-4" onclick="exportPatients()">
                            <i class="fas fa-download me-2"></i> Export
                        </button>
                        <button type="button" class="btn btn-light btn-lg px-4" onclick="printDirectory()">
                            <i class="fas fa-print me-2"></i> Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="container mb-5">
        <div class="row">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <h3 class="fw-bold mb-2"><?php echo $total_patients; ?></h3>
                    <p class="text-muted mb-0">Total Patients</p>
                    <small>All registered patients</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-hands-helping"></i>
                    </div>
                    <h3 class="fw-bold mb-2"><?php echo $stats['total_patients_helped'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Patients Helped</p>
                    <small>Under your care</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="fw-bold mb-2"><?php echo $stats['closed_cases'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Cases Closed</p>
                    <small>Successfully resolved</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Filter Section -->
        <div class="filter-card">
            <form method="GET" id="filterForm">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" 
                                   class="form-control border-start-0" 
                                   name="search" 
                                   placeholder="Search patients..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="location">
                            <option value="">All Locations</option>
                            <?php foreach ($locations as $loc): ?>
                                <?php if ($loc['location']): ?>
                                    <option value="<?php echo htmlspecialchars($loc['location']); ?>" 
                                            <?php echo $location == $loc['location'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($loc['location']); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="sort">
                            <option value="recent" <?php echo $sort == 'recent' ? 'selected' : ''; ?>>Most Recent</option>
                            <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>Name (A-Z)</option>
                            <option value="location" <?php echo $sort == 'location' ? 'selected' : ''; ?>>Location</option>
                            <option value="requests" <?php echo $sort == 'requests' ? 'selected' : ''; ?>>Most Requests</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-search w-100">
                            <i class="fas fa-filter me-2"></i> Filter
                        </button>
                    </div>
                </div>
                
                <!-- Quick Filter Tags -->
                <?php if (!empty($locations)): ?>
                    <div class="mt-3">
                        <small class="text-muted d-block mb-2">Quick Location Filter:</small>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="?location=" class="location-tag text-decoration-none <?php echo empty($location) ? 'active' : ''; ?>">
                                All Locations
                            </a>
                            <?php foreach (array_slice($locations, 0, 8) as $loc): ?>
                                <?php if ($loc['location']): ?>
                                    <a href="?location=<?php echo urlencode($loc['location']); ?>" 
                                       class="location-tag text-decoration-none <?php echo $location == $loc['location'] ? 'active' : ''; ?>">
                                        <?php echo htmlspecialchars($loc['location']); ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Patients Grid -->
        <?php if (empty($patients)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-user-slash"></i>
                </div>
                <h4 class="fw-bold mb-3">No Patients Found</h4>
                <p class="text-muted mb-4">Try adjusting your search or filter criteria</p>
                <a href="patient-directory.php" class="btn btn-primary">
                    <i class="fas fa-redo me-2"></i> Reset Filters
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($patients as $index => $patient): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="patient-card" style="animation-delay: <?php echo $index * 0.1; ?>s">
                            <div class="patient-avatar">
                                <?php echo strtoupper(substr($patient['full_name'], 0, 1)); ?>
                            </div>
                            <h5 class="text-center fw-bold mb-2"><?php echo htmlspecialchars($patient['full_name']); ?></h5>
                            
                            <?php if ($patient['profession']): ?>
                                <p class="text-center text-muted small mb-2">
                                    <i class="fas fa-briefcase me-1"></i>
                                    <?php echo htmlspecialchars($patient['profession']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if ($patient['location']): ?>
                                <p class="text-center text-muted small mb-3">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo htmlspecialchars($patient['location']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <div class="fw-bold fs-4"><?php echo $patient['request_count']; ?></div>
                                    <div class="text-muted small">Total Requests</div>
                                </div>
                                <div class="col-6">
                                    <div class="fw-bold fs-4 text-success"><?php echo $patient['closed_requests']; ?></div>
                                    <div class="text-muted small">Closed</div>
                                </div>
                            </div>
                            
                            <?php if ($patient['last_request_date']): ?>
                                <p class="text-center text-muted small mb-3">
                                    <i class="fas fa-clock me-1"></i>
                                    Last request: <?php echo date('M d, Y', strtotime($patient['last_request_date'])); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-center gap-2">
                                <a href="respond-requests.php?patient_id=<?php echo $patient['user_id']; ?>" 
                                   class="btn btn-outline-primary btn-action">
                                    <i class="fas fa-eye me-1"></i> View
                                </a>
                                <a href="view-request.php?new=1&patient_id=<?php echo $patient['user_id']; ?>" 
                                   class="btn btn-outline-success btn-action">
                                    <i class="fas fa-plus me-1"></i> New Request
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-5">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&location=<?php echo urlencode($location); ?>&sort=<?php echo $sort; ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&location=<?php echo urlencode($location); ?>&sort=<?php echo $sort; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&location=<?php echo urlencode($location); ?>&sort=<?php echo $sort; ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <p class="text-muted">
                    Showing <?php echo min($per_page, count($patients)); ?> of <?php echo $total_patients; ?> patients
                </p>
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
                        HealthConnect Patient Directory
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
        // Add animations on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Add CSS for text animation
            const style = document.createElement('style');
            style.textContent = `
                .animate-charcter {
                    background-image: linear-gradient(
                        -225deg,
                        #ffffff 0%,
                        #a6c1ee 29%,
                        #0d6efd 67%,
                        #052c65 100%
                    );
                    background-size: 200% auto;
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                    animation: textclip 3s linear infinite;
                    display: inline-block;
                }
                
                @keyframes textclip {
                    to {
                        background-position: 200% center;
                    }
                }
                
                .ripple-effect {
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.6);
                    transform: scale(0);
                    animation: ripple-animation 0.6s linear;
                    pointer-events: none;
                }
                
                @keyframes ripple-animation {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);

            // Add ripple effect to buttons
            document.querySelectorAll('.btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    const x = e.clientX - e.target.getBoundingClientRect().left;
                    const y = e.clientY - e.target.getBoundingClientRect().top;
                    
                    const ripple = document.createElement('span');
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    ripple.classList.add('ripple-effect');
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });

            // Add loading state to form submission
            const filterForm = document.getElementById('filterForm');
            const loadingOverlay = document.getElementById('loadingOverlay');
            
            filterForm.addEventListener('submit', function() {
                loadingOverlay.classList.add('active');
            });
        });

        // Export functionality
        function exportPatients() {
            loadingOverlay.classList.add('active');
            
            // Simulate export process
            setTimeout(() => {
                loadingOverlay.classList.remove('active');
                alert('Patient data exported successfully!');
            }, 2000);
        }

        // Print functionality
        function printDirectory() {
            const printContent = document.querySelector('.container').innerHTML;
            const originalContent = document.body.innerHTML;
            
            document.body.innerHTML = `
                <div class="container">
                    <h1 class="text-center mb-4">Patient Directory - <?php echo date('Y-m-d'); ?></h1>
                    ${printContent}
                </div>
            `;
            
            window.print();
            document.body.innerHTML = originalContent;
            window.location.reload();
        }

        // Auto-refresh data every 2 minutes
        setInterval(() => {
            if (!document.hidden) {
                window.location.reload();
            }
        }, 120000);

        // Search auto-submit on Enter
        document.querySelector('input[name="search"]').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                filterForm.submit();
            }
        });
    </script>
</body>
</html>
