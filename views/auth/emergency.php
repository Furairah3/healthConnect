<?php
// healthconnect/views/auth/emergency.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header('Location: login.php?error=required');
    exit();
}

require_once '../../app/config/database.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get patient location from database
$user_sql = "SELECT location, phone_number FROM hc_users WHERE user_id = :user_id";
$user_stmt = $pdo->prepare($user_sql);
$user_stmt->execute([':user_id' => $user_id]);
$user_data = $user_stmt->fetch();

// Emergency contacts data
$emergency_contacts = [
    [
        'name' => 'Emergency Ambulance',
        'number' => '911 or 112',
        'icon' => 'fa-ambulance',
        'color' => 'danger',
        'description' => 'National emergency number for ambulance, fire, police'
    ],
    [
        'name' => 'Poison Control Center',
        'number' => '1-800-222-1222',
        'icon' => 'fa-skull-crossbones',
        'color' => 'warning',
        'description' => '24/7 poison emergency helpline'
    ],
    [
        'name' => 'Mental Health Crisis',
        'number' => '988',
        'icon' => 'fa-head-side-virus',
        'color' => 'info',
        'description' => 'Suicide & crisis lifeline'
    ],
    [
        'name' => 'Domestic Violence',
        'number' => '1-800-799-7233',
        'icon' => 'fa-handshake',
        'color' => 'danger',
        'description' => 'National domestic violence hotline'
    ],
    [
        'name' => 'Medical Emergency',
        'number' => '911',
        'icon' => 'fa-user-md',
        'color' => 'primary',
        'description' => 'Life-threatening medical emergencies'
    ],
    [
        'name' => 'Child Abuse Hotline',
        'number' => '1-800-422-4453',
        'icon' => 'fa-child',
        'color' => 'warning',
        'description' => 'Child help national abuse hotline'
    ]
];

// Nearby hospitals (this would typically come from an API)
$nearby_hospitals = [
    [
        'name' => 'City General Hospital',
        'distance' => '2.5 miles',
        'address' => '123 Medical Center Dr',
        'phone' => '(555) 123-4567',
        'emergency' => true,
        'rating' => '4.5'
    ],
    [
        'name' => 'Community Health Center',
        'distance' => '1.8 miles',
        'address' => '456 Health Ave',
        'phone' => '(555) 987-6543',
        'emergency' => true,
        'rating' => '4.2'
    ],
    [
        'name' => 'Urgent Care Clinic',
        'distance' => '0.5 miles',
        'address' => '789 Quick St',
        'phone' => '(555) 456-7890',
        'emergency' => false,
        'rating' => '4.0'
    ]
];

// Emergency procedures
$procedures = [
    [
        'title' => 'CPR (Cardiopulmonary Resuscitation)',
        'steps' => [
            'Check for responsiveness',
            'Call 911 or emergency number',
            'Begin chest compressions (100-120/min)',
            'Give rescue breaths if trained',
            'Continue until help arrives'
        ],
        'icon' => 'fa-heart'
    ],
    [
        'title' => 'Choking - Adult/Child',
        'steps' => [
            'Ask "Are you choking?"',
            'Perform abdominal thrusts (Heimlich maneuver)',
            'Continue until object is dislodged',
            'Call 911 if person becomes unconscious'
        ],
        'icon' => 'fa-lungs'
    ],
    [
        'title' => 'Severe Bleeding',
        'steps' => [
            'Apply direct pressure with clean cloth',
            'Elevate the injured area',
            'Do not remove original dressing',
            'Call 911 for severe bleeding',
            'Keep person calm and lying down'
        ],
        'icon' => 'fa-tint'
    ],
    [
        'title' => 'Heart Attack',
        'steps' => [
            'Call 911 immediately',
            'Have person sit or lie down',
            'Give aspirin if not allergic',
            'Loosen tight clothing',
            'Monitor breathing and consciousness'
        ],
        'icon' => 'fa-heartbeat'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Contacts - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --emergency-primary: #dc3545;
            --emergency-secondary: #842029;
            --emergency-accent: #ff6b6b;
            --emergency-light: #ffe6e6;
            --animation-speed: 0.5s;
            --ease-out: cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #fff5f5 0%, #ffe6e6 100%);
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
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .emergency-header {
            background: linear-gradient(135deg, var(--emergency-primary) 0%, var(--emergency-secondary) 100%);
            color: white;
            padding: 80px 0 50px;
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
        }
        
        .emergency-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,170.7C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            animation: float 20s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .emergency-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(220, 53, 69, 0.1);
            transition: all 0.5s var(--ease-out);
            overflow: hidden;
            position: relative;
            animation: slideUp 0.6s var(--ease-out) forwards;
            opacity: 0;
            background: white;
        }
        
        .emergency-card:nth-child(1) { animation-delay: 0.1s; }
        .emergency-card:nth-child(2) { animation-delay: 0.2s; }
        .emergency-card:nth-child(3) { animation-delay: 0.3s; }
        .emergency-card:nth-child(4) { animation-delay: 0.4s; }
        
        .emergency-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(220, 53, 69, 0.15);
        }
        
        .emergency-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--emergency-primary), var(--emergency-accent));
            transform-origin: left;
            transform: scaleX(0);
            transition: transform 0.6s var(--ease-out);
        }
        
        .emergency-card:hover::after {
            transform: scaleX(1);
        }
        
        .call-btn {
            background: linear-gradient(135deg, var(--emergency-primary), var(--emergency-secondary));
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 25px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .call-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(220, 53, 69, 0.3);
            animation: pulse 1s infinite, shake 0.5s;
        }
        
        .call-btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 50%;
            transform: scale(1, 1) translate(-50%);
        }
        
        .call-btn:active::after {
            animation: ripple 1s ease-out;
        }
        
        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            100% {
                transform: scale(20, 20);
                opacity: 0;
            }
        }
        
        .contact-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
            background: white;
            position: relative;
        }
        
        .contact-card:hover {
            border-color: var(--emergency-primary);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(220, 53, 69, 0.1);
        }
        
        .contact-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .contact-card:hover .contact-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        .bg-danger-light { background: rgba(220, 53, 69, 0.1); color: var(--emergency-primary); }
        .bg-warning-light { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .bg-info-light { background: rgba(13, 202, 240, 0.1); color: #0dcaf0; }
        .bg-primary-light { background: rgba(13, 110, 253, 0.1); color: #0d6efd; }
        
        .hospital-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
            background: white;
        }
        
        .hospital-card:hover {
            border-color: var(--emergency-primary);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(220, 53, 69, 0.1);
        }
        
        .hospital-badge {
            background: var(--emergency-primary);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .procedure-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
            background: white;
        }
        
        .procedure-card:hover {
            border-color: var(--emergency-primary);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(220, 53, 69, 0.1);
        }
        
        .step-item {
            padding: 10px 15px;
            margin-bottom: 8px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid var(--emergency-primary);
            transition: all 0.3s;
        }
        
        .step-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        
        .location-card {
            background: linear-gradient(135deg, #0d6efd, #052c65);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
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
            background: rgba(220, 53, 69, 0.05);
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
        
        .sos-btn {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #dc3545, #ff6b6b);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            box-shadow: 0 10px 30px rgba(220, 53, 69, 0.3);
            animation: pulse 2s infinite;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .sos-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 15px 40px rgba(220, 53, 69, 0.4);
        }
        
        .welcome-message {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(255,255,255,0.2);
            animation: slideUp 0.8s var(--ease-out) 0.2s forwards;
            opacity: 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .alert-emergency {
            animation: shake 0.5s infinite;
            border: 2px solid var(--emergency-primary);
        }
        
        @media (max-width: 768px) {
            .emergency-header {
                padding: 40px 0 30px;
            }
            
            .sos-btn {
                width: 60px;
                height: 60px;
                font-size: 18px;
                bottom: 20px;
                right: 20px;
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
    
    <!-- SOS Button -->
    <div class="sos-btn" onclick="callEmergency()">
        SOS
    </div>
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-danger" href="../../index.php">
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
                        <a class="nav-link active" href="emergency.php">
                            <i class="fas fa-ambulance me-1"></i> Emergency
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="resources.php">
                            <i class="fas fa-book-medical me-1"></i> Resources
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="profile-avatar me-2" style="width: 35px; height: 35px; background: linear-gradient(135deg, #dc3545, #842029); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                                <i class="fas fa-user"></i>
                            </div>
                            <span><?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="medical-history.php"><i class="fas fa-file-medical me-2"></i> Medical History</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Emergency Header -->
    <div class="emergency-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="welcome-message">
                        <h1 class="fw-bold mb-3">
                            <i class="fas fa-ambulance me-2"></i> Emergency Contacts
                        </h1>
                        <p class="lead mb-0">Immediate assistance when you need it most. Save these numbers for emergencies.</p>
                        
                        <!-- Emergency Alert -->
                        <div class="alert alert-emergency alert-light mt-4" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle fa-2x text-danger me-3"></i>
                                <div>
                                    <h5 class="alert-heading mb-2">IN CASE OF EMERGENCY</h5>
                                    <p class="mb-0">Call 911 immediately for life-threatening situations</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-end position-relative">
                    <!-- Location Info -->
                    <div class="location-card">
                        <h5 class="fw-bold mb-3">
                            <i class="fas fa-map-marker-alt me-2"></i> Your Location
                        </h5>
                        <p class="mb-2">
                            <i class="fas fa-user me-2"></i>
                            <?php echo htmlspecialchars($user_name); ?>
                        </p>
                        <?php if ($user_data['location']): ?>
                            <p class="mb-0">
                                <i class="fas fa-map-pin me-2"></i>
                                <?php echo htmlspecialchars($user_data['location']); ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($user_data['phone_number']): ?>
                            <p class="mb-0 mt-2">
                                <i class="fas fa-phone me-2"></i>
                                <?php echo htmlspecialchars($user_data['phone_number']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Emergency Call Section -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="emergency-card p-5 text-center">
                    <div class="mb-4">
                        <i class="fas fa-phone-alt fa-4x text-danger mb-3"></i>
                        <h2 class="fw-bold text-danger">Call Emergency Services</h2>
                        <p class="lead">For immediate, life-threatening emergencies</p>
                    </div>
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <button class="call-btn" onclick="callNumber('911')">
                            <i class="fas fa-phone me-2"></i> Call 911
                        </button>
                        <button class="call-btn" onclick="callNumber('112')">
                            <i class="fas fa-phone me-2"></i> Call 112
                        </button>
                        <button class="call-btn bg-warning" onclick="shareLocation()">
                            <i class="fas fa-share-location me-2"></i> Share Location
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Emergency Contacts -->
        <div class="row mb-5">
            <div class="col-12">
                <h3 class="fw-bold mb-4 text-danger">
                    <i class="fas fa-address-book me-2"></i> Emergency Contact Numbers
                </h3>
                <div class="row">
                    <?php foreach ($emergency_contacts as $index => $contact): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="contact-card" style="animation-delay: <?php echo $index * 0.1; ?>s">
                                <div class="contact-icon bg-<?php echo $contact['color']; ?>-light">
                                    <i class="fas <?php echo $contact['icon']; ?>"></i>
                                </div>
                                <h5 class="fw-bold mb-2"><?php echo $contact['name']; ?></h5>
                                <div class="mb-3">
                                    <span class="badge bg-<?php echo $contact['color']; ?> p-2 mb-2"><?php echo $contact['number']; ?></span>
                                    <p class="text-muted small mb-0"><?php echo $contact['description']; ?></p>
                                </div>
                                <button class="btn btn-outline-<?php echo $contact['color']; ?> w-100" 
                                        onclick="callNumber('<?php echo preg_replace('/[^0-9]/', '', $contact['number']); ?>')">
                                    <i class="fas fa-phone me-2"></i> Call Now
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Nearby Hospitals -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="emergency-card p-4">
                    <h3 class="fw-bold mb-4 text-danger">
                        <i class="fas fa-hospital me-2"></i> Nearby Medical Facilities
                    </h3>
                    <div class="row">
                        <?php foreach ($nearby_hospitals as $index => $hospital): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="hospital-card" style="animation-delay: <?php echo $index * 0.1; ?>s">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h6 class="fw-bold mb-0"><?php echo $hospital['name']; ?></h6>
                                        <?php if ($hospital['emergency']): ?>
                                            <span class="hospital-badge">ER Available</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-muted small mb-2">
                                        <i class="fas fa-map-marker-alt me-2"></i>
                                        <?php echo $hospital['address']; ?>
                                    </p>
                                    <p class="text-muted small mb-2">
                                        <i class="fas fa-route me-2"></i>
                                        Distance: <?php echo $hospital['distance']; ?>
                                    </p>
                                    <p class="text-muted small mb-3">
                                        <i class="fas fa-star me-2"></i>
                                        Rating: <?php echo $hospital['rating']; ?>/5
                                    </p>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-outline-danger btn-sm flex-grow-1" 
                                                onclick="callNumber('<?php echo preg_replace('/[^0-9]/', '', $hospital['phone']); ?>')">
                                            <i class="fas fa-phone me-1"></i> Call
                                        </button>
                                        <button class="btn btn-outline-primary btn-sm flex-grow-1" 
                                                onclick="getDirections('<?php echo urlencode($hospital['address']); ?>')">
                                            <i class="fas fa-directions me-1"></i> Directions
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Emergency Procedures -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="emergency-card p-4">
                    <h3 class="fw-bold mb-4 text-danger">
                        <i class="fas fa-first-aid me-2"></i> Emergency Procedures
                    </h3>
                    <div class="row">
                        <?php foreach ($procedures as $index => $procedure): ?>
                            <div class="col-md-6">
                                <div class="procedure-card" style="animation-delay: <?php echo $index * 0.1; ?>s">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="contact-icon bg-danger-light me-3">
                                            <i class="fas <?php echo $procedure['icon']; ?>"></i>
                                        </div>
                                        <h5 class="fw-bold mb-0"><?php echo $procedure['title']; ?></h5>
                                    </div>
                                    <div class="steps">
                                        <?php foreach ($procedure['steps'] as $step_index => $step): ?>
                                            <div class="step-item">
                                                <span class="fw-bold me-2">Step <?php echo $step_index + 1; ?>:</span>
                                                <?php echo $step; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Important Notes -->
        <div class="row">
            <div class="col-12">
                <div class="alert alert-warning">
                    <h5 class="alert-heading fw-bold">
                        <i class="fas fa-exclamation-circle me-2"></i> Important Information
                    </h5>
                    <ul class="mb-0">
                        <li>Call 911 immediately for life-threatening emergencies</li>
                        <li>Stay calm and provide clear information to the operator</li>
                        <li>Do not hang up until the operator tells you to</li>
                        <li>If possible, have someone meet emergency services at the entrance</li>
                        <li>Keep your emergency medical information updated in your profile</li>
                        <li>Know your location and nearest cross streets</li>
                    </ul>
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
                        <i class="fas fa-heartbeat text-danger me-2"></i>
                        HealthConnect Emergency Services
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0 text-muted">
                        &copy; <?php echo date('Y'); ?> HealthConnect. For emergencies only.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Emergency functions
        function callEmergency() {
            if (confirm('Call 911 for emergency services?')) {
                callNumber('911');
            }
        }

        function callNumber(number) {
            // In a real app, this would initiate a phone call
            // For web, we show a message
            alert(`Calling: ${number}\n\nIn a real application, this would initiate a phone call.\nFor now, please dial ${number} manually.`);
            
            // Log the emergency call attempt
            console.log(`Emergency call attempted to: ${number} at ${new Date().toLocaleString()}`);
            
            // Add visual feedback
            const btn = event.target.closest('button');
            if (btn) {
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-phone me-2"></i> Calling...';
                btn.disabled = true;
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }, 2000);
            }
        }

        function shareLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const locationUrl = `https://maps.google.com/?q=${lat},${lng}`;
                        
                        if (navigator.share) {
                            navigator.share({
                                title: 'My Emergency Location',
                                text: `I need help! My location: ${lat}, ${lng}`,
                                url: locationUrl
                            });
                        } else {
                            alert(`Location: ${lat}, ${lng}\n\nShare this with emergency services.`);
                        }
                    },
                    (error) => {
                        alert('Unable to get location. Please enable location services.');
                    }
                );
            } else {
                alert('Geolocation is not supported by your browser.');
            }
        }

        function getDirections(address) {
            const mapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(address)}`;
            window.open(mapsUrl, '_blank');
        }

        // SOS button animation
        const sosBtn = document.querySelector('.sos-btn');
        setInterval(() => {
            sosBtn.classList.toggle('alert-emergency');
        }, 3000);

        // Add click effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add CSS for animations
            const style = document.createElement('style');
            style.textContent = `
                .ripple {
                    position: relative;
                    overflow: hidden;
                }
                
                .ripple::after {
                    content: '';
                    position: absolute;
                    width: 100%;
                    height: 100%;
                    top: 0;
                    left: -100%;
                    background: linear-gradient(90deg, 
                        transparent, 
                        rgba(255,255,255,0.3), 
                        transparent);
                    transition: left 0.5s;
                }
                
                .ripple:hover::after {
                    left: 100%;
                }
                
                .btn {
                    transition: all 0.3s;
                }
                
                .btn:active {
                    transform: scale(0.95);
                }
            `;
            document.head.appendChild(style);

            // Add ripple effect to buttons
            document.querySelectorAll('.btn, .call-btn').forEach(button => {
                button.classList.add('ripple');
            });

            // Emergency alert blinking
            const emergencyAlert = document.querySelector('.alert-emergency');
            if (emergencyAlert) {
                setInterval(() => {
                    emergencyAlert.classList.toggle('alert-light');
                    emergencyAlert.classList.toggle('alert-danger');
                }, 2000);
            }
        });

        // Auto-refresh location every 5 minutes
        setInterval(() => {
            console.log('Refreshing emergency data...');
        }, 300000);
    </script>
</body>
</html>
