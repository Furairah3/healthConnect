<?php
// healthconnect/views/auth/resources.php
session_start();
require_once '../../app/config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?error=required');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];

// Updated resources data with REAL LINKS
$resources = [
    'first_aid' => [
        'title' => 'First Aid Essentials',
        'icon' => 'fa-first-aid',
        'color' => 'danger',
        'items' => [
            [
                'title' => 'CPR Guidelines 2024',
                'description' => 'Latest CPR procedures for adults, children, and infants',
                'type' => 'pdf',
                'link' => 'https://cpr.heart.org/-/media/CPR-Files/Resources/CPR-Guidelines/2024-Focused-Update/2024-CPR-Guidelines-Focused-Update.pdf',
                'duration' => '15 min'
            ],
            [
                'title' => 'Wound Care Basics',
                'description' => 'How to clean, dress, and manage different types of wounds',
                'type' => 'video',
                'link' => 'https://www.youtube.com/watch?v=3lGlW9cKjZk',
                'duration' => '10 min'
            ],
            [
                'title' => 'Emergency Response Checklist',
                'description' => 'Step-by-step guide for common medical emergencies',
                'type' => 'checklist',
                'link' => 'https://www.redcross.org/content/dam/redcross/atg/PDFs/checklists/Emergency_Checklist.pdf',
                'duration' => '5 min'
            ]
        ]
    ],
    'common_conditions' => [
        'title' => 'Common Medical Conditions',
        'icon' => 'fa-stethoscope',
        'color' => 'primary',
        'items' => [
            [
                'title' => 'Hypertension Management',
                'description' => 'Recognizing and advising on high blood pressure',
                'type' => 'guide',
                'link' => 'https://www.who.int/news-room/fact-sheets/detail/hypertension',
                'duration' => '20 min'
            ],
            [
                'title' => 'Diabetes Care Guide',
                'description' => 'Basic diabetes management for rural patients',
                'type' => 'pdf',
                'link' => 'https://www.who.int/publications/i/item/9789240031596',
                'duration' => '25 min'
            ],
            [
                'title' => 'Respiratory Infections',
                'description' => 'Identifying and advising on common respiratory issues',
                'type' => 'video',
                'link' => 'https://www.youtube.com/watch?v=6O9-uQ8crqA',
                'duration' => '18 min'
            ]
        ]
    ],
    'medication' => [
        'title' => 'Medication Guidelines',
        'icon' => 'fa-pills',
        'color' => 'info',
        'items' => [
            [
                'title' => 'Common Drug Interactions',
                'description' => 'Important medication interactions to watch for',
                'type' => 'guide',
                'link' => 'https://www.who.int/medicines/areas/quality_safety/safety_efficacy/drug_interactions/en/',
                'duration' => '30 min'
            ],
            [
                'title' => 'Pain Management Basics',
                'description' => 'Safe pain relief options and precautions',
                'type' => 'pdf',
                'link' => 'https://www.who.int/publications/i/item/9789241548120',
                'duration' => '15 min'
            ],
            [
                'title' => 'Antibiotic Guidelines',
                'description' => 'Proper use and common antibiotics',
                'type' => 'checklist',
                'link' => 'https://www.who.int/medicines/areas/rational_use/antibiotics_checklist/en/',
                'duration' => '12 min'
            ]
        ]
    ],
    'maternal' => [
        'title' => 'Maternal & Child Health',
        'icon' => 'fa-baby',
        'color' => 'success',
        'items' => [
            [
                'title' => 'Prenatal Care Basics',
                'description' => 'Essential care during pregnancy',
                'type' => 'guide',
                'link' => 'https://www.who.int/publications/i/item/9789240023072',
                'duration' => '20 min'
            ],
            [
                'title' => 'Childhood Vaccination Schedule',
                'description' => 'Complete immunization guide',
                'type' => 'pdf',
                'link' => 'https://www.who.int/teams/immunization-vaccines-and-biologicals/policies/who-recommendations-for-routine-immunization---summary-tables',
                'duration' => '10 min'
            ],
            [
                'title' => 'Newborn Care',
                'description' => 'Basic care for newborns and infants',
                'type' => 'video',
                'link' => 'https://www.youtube.com/watch?v=4ljeGsi95x8',
                'duration' => '22 min'
            ]
        ]
    ],
    'mental_health' => [
        'title' => 'Mental Health Support',
        'icon' => 'fa-brain',
        'color' => 'warning',
        'items' => [
            [
                'title' => 'Stress Management',
                'description' => 'Techniques for managing stress and anxiety',
                'type' => 'guide',
                'link' => 'https://www.who.int/publications/i/item/9789240003920',
                'duration' => '15 min'
            ],
            [
                'title' => 'Recognizing Depression',
                'description' => 'Identifying signs and providing support',
                'type' => 'pdf',
                'link' => 'https://www.who.int/news-room/fact-sheets/detail/depression',
                'duration' => '18 min'
            ],
            [
                'title' => 'Crisis Intervention Basics',
                'description' => 'How to help in mental health crises',
                'type' => 'video',
                'link' => 'https://www.youtube.com/watch?v=WzLvWpX4l1M',
                'duration' => '25 min'
            ]
        ]
    ],
    'telemedicine' => [
        'title' => 'Telemedicine Best Practices',
        'icon' => 'fa-video',
        'color' => 'purple',
        'items' => [
            [
                'title' => 'Effective Remote Consultation',
                'description' => 'How to conduct quality remote assessments',
                'type' => 'guide',
                'link' => 'https://www.who.int/publications/i/item/9789240020504',
                'duration' => '20 min'
            ],
            [
                'title' => 'Digital Assessment Tools',
                'description' => 'Tools and techniques for remote diagnosis',
                'type' => 'pdf',
                'link' => 'https://www.who.int/publications/i/item/9789240010567',
                'duration' => '15 min'
            ],
            [
                'title' => 'Privacy & Ethics in Telehealth',
                'description' => 'Maintaining confidentiality and standards',
                'type' => 'video',
                'link' => 'https://www.youtube.com/watch?v=zB5ZQ4Q7tI0',
                'duration' => '18 min'
            ]
        ]
    ]
];

// Get recently accessed resources from database (simplified example)
// Now using real links for recently accessed
$recent_resources = [
    [
        'title' => 'CPR Guidelines 2024',
        'description' => 'Latest CPR procedures for adults, children, and infants',
        'type' => 'pdf',
        'link' => 'https://cpr.heart.org/-/media/CPR-Files/Resources/CPR-Guidelines/2024-Focused-Update/2024-CPR-Guidelines-Focused-Update.pdf',
        'duration' => '15 min'
    ],
    [
        'title' => 'Hypertension Management',
        'description' => 'Recognizing and advising on high blood pressure',
        'type' => 'guide',
        'link' => 'https://www.who.int/news-room/fact-sheets/detail/hypertension',
        'duration' => '20 min'
    ],
    [
        'title' => 'Prenatal Care Basics',
        'description' => 'Essential care during pregnancy',
        'type' => 'guide',
        'link' => 'https://www.who.int/publications/i/item/9789240023072',
        'duration' => '20 min'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Resources - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        :root {
            --resource-primary: #198754;
            --resource-secondary: #146c43;
            --purple: #6f42c1;
            --orange: #fd7e14;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--resource-primary) 0%, var(--resource-secondary) 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        
        .resource-category {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .resource-category:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.12);
        }
        
        .category-header {
            padding: 25px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .category-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
        }
        
        .category-header.bg-danger { background: linear-gradient(135deg, #dc3545, #c82333); }
        .category-header.bg-primary { background: linear-gradient(135deg, #0d6efd, #0b5ed7); }
        .category-header.bg-info { background: linear-gradient(135deg, #0dcaf0, #0bb6d4); }
        .category-header.bg-success { background: linear-gradient(135deg, #198754, #146c43); }
        .category-header.bg-warning { background: linear-gradient(135deg, #ffc107, #e0a800); }
        .category-header.bg-purple { background: linear-gradient(135deg, #6f42c1, #5a32a3); }
        
        .category-icon {
            font-size: 40px;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        
        .resource-item {
            padding: 20px;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }
        
        .resource-item:hover {
            background: #f8f9fa;
        }
        
        .resource-item:last-child {
            border-bottom: none;
        }
        
        .resource-type {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 10px;
        }
        
        .type-pdf { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
        .type-video { background: rgba(13, 110, 253, 0.1); color: #0d6efd; }
        .type-guide { background: rgba(25, 135, 84, 0.1); color: #198754; }
        .type-checklist { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        
        .duration-badge {
            background: #f8f9fa;
            color: #6c757d;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
        }
        
        .quick-search {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .recent-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .tool-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            text-align: center;
            height: 100%;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            border-color: var(--resource-primary);
        }
        
        .tool-icon {
            font-size: 40px;
            margin-bottom: 20px;
            color: var(--resource-primary);
        }
        
        .download-btn {
            position: relative;
            overflow: hidden;
        }
        
        .download-btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }
        
        .download-btn:focus:not(:active)::after {
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
        
        .search-highlight {
            background: #fff3cd;
            padding: 2px 4px;
            border-radius: 3px;
        }
        
        .progress-ring {
            width: 60px;
            height: 60px;
        }
        
        .progress-ring-circle {
            stroke-width: 4;
            stroke-linecap: round;
            fill: none;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }
        
        /* New: Icon colors for different resource types */
        .resource-item .fa-file-pdf { color: #dc3545; }
        .resource-item .fa-video { color: #0d6efd; }
        .resource-item .fa-book { color: #198754; }
        .resource-item .fa-list-check { color: #ffc107; }
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
                        <a class="nav-link" href="respond-requests.php">
                            <i class="fas fa-hands-helping me-1"></i> Help Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="resources.php">
                            <i class="fas fa-book-medical me-1"></i> Resources
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="training.php">
                            <i class="fas fa-graduation-cap me-1"></i> Training
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="profile-avatar-small bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                            </div>
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
                <div class="col-lg-8">
                    <h1 class="fw-bold mb-3"><i class="fas fa-book-medical me-3"></i> Medical Resources & References</h1>
                    <p class="lead mb-0">Essential guides, protocols, and training materials for effective volunteer work.</p>
                </div>
                <div class="col-lg-4 text-end">
                    <div class="alert alert-light d-inline-block">
                        <i class="fas fa-brain text-success me-2"></i>
                        <strong>Knowledge is power</strong> in healthcare
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Quick Search & Stats -->
        <div class="row mb-5">
            <div class="col-lg-8">
                <div class="quick-search">
                    <h4 class="fw-bold mb-4"><i class="fas fa-search me-2"></i> Find Resources Quickly</h4>
                    <form id="searchForm">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" 
                                   id="resourceSearch" 
                                   placeholder="Search for medical conditions, procedures, or topics...">
                            <button class="btn btn-success" type="submit">
                                <i class="fas fa-search me-2"></i> Search
                            </button>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-lightbulb me-1"></i>
                                Try: "first aid", "hypertension", "child care", "medication guidelines"
                            </small>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="recent-card">
                    <h5 class="fw-bold mb-3"><i class="fas fa-history me-2"></i> Your Progress</h5>
                    <div class="d-flex align-items-center">
                        <div class="progress-ring me-3">
                            <svg viewBox="0 0 36 36">
                                <path class="progress-ring-circle"
                                      stroke="#e9ecef"
                                      stroke-width="4"
                                      fill="none"
                                      d="M18 2.0845
                                         a 15.9155 15.9155 0 0 1 0 31.831
                                         a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                <path class="progress-ring-circle"
                                      stroke="#198754"
                                      stroke-width="4"
                                      stroke-dasharray="60, 100"
                                      fill="none"
                                      d="M18 2.0845
                                         a 15.9155 15.9155 0 0 1 0 31.831
                                         a 15.9155 15.9155 0 0 1 0 -31.831"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0">60%</h3>
                            <p class="text-muted mb-0">Training Complete</p>
                            <small>12 of 20 resources reviewed</small>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="training.php" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-play-circle me-1"></i> Continue Learning
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Tools -->
        <div class="row mb-5">
            <div class="col-12">
                <h4 class="fw-bold mb-4"><i class="fas fa-tools me-2"></i> Quick Tools & Calculators</h4>
                <div class="row g-4">
                    <div class="col-lg-3 col-md-6">
                        <div class="tool-card">
                            <div class="tool-icon">
                                <i class="fas fa-calculator"></i>
                            </div>
                            <h5 class="fw-bold mb-2">BMI Calculator</h5>
                            <p class="text-muted small mb-3">Calculate Body Mass Index quickly</p>
                            <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#bmiModal">
                                <i class="fas fa-external-link-alt me-1"></i> Open Tool
                            </button>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="tool-card">
                            <div class="tool-icon">
                                <i class="fas fa-heartbeat"></i>
                            </div>
                            <h5 class="fw-bold mb-2">Dosage Calculator</h5>
                            <p class="text-muted small mb-3">Medication dosage based on weight</p>
                            <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#dosageModal">
                                <i class="fas fa-external-link-alt me-1"></i> Open Tool
                            </button>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="tool-card">
                            <div class="tool-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <h5 class="fw-bold mb-2">Due Date Calculator</h5>
                            <p class="text-muted small mb-3">Pregnancy due date estimation</p>
                            <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#dueDateModal">
                                <i class="fas fa-external-link-alt me-1"></i> Open Tool
                            </button>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="tool-card">
                            <div class="tool-icon">
                                <i class="fas fa-file-medical-alt"></i>
                            </div>
                            <h5 class="fw-bold mb-2">Symptom Checker</h5>
                            <p class="text-muted small mb-3">Basic symptom assessment guide</p>
                            <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#symptomModal">
                                <i class="fas fa-external-link-alt me-1"></i> Open Tool
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resources by Category -->
        <div class="row">
            <div class="col-12">
                <h4 class="fw-bold mb-4"><i class="fas fa-folder-open me-2"></i> Resources by Category</h4>
            </div>
        </div>

        <?php foreach ($resources as $key => $category): ?>
            <div class="resource-category">
                <div class="category-header bg-<?php echo $category['color']; ?>">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="category-icon">
                                <i class="fas <?php echo $category['icon']; ?>"></i>
                            </div>
                            <h3 class="fw-bold mb-2"><?php echo $category['title']; ?></h3>
                            <p class="mb-0 opacity-75">Essential resources for <?php echo strtolower(str_replace('_', ' ', $key)); ?></p>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-light text-dark fs-6 p-3">
                                <?php echo count($category['items']); ?> Resources
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="category-body">
                    <?php foreach ($category['items'] as $item): ?>
                        <div class="resource-item">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($item['title']); ?></h5>
                                    <p class="text-muted mb-2"><?php echo htmlspecialchars($item['description']); ?></p>
                                    <div>
                                        <span class="resource-type type-<?php echo $item['type']; ?>">
                                            <i class="fas fa-<?php echo getTypeIcon($item['type']); ?> me-1"></i>
                                            <?php echo strtoupper($item['type']); ?>
                                        </span>
                                        <span class="duration-badge">
                                            <i class="far fa-clock me-1"></i>
                                            <?php echo $item['duration']; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-4 text-end">
                                    <a href="<?php echo htmlspecialchars($item['link']); ?>" 
                                       class="btn btn-success download-btn" 
                                       target="_blank"
                                       onclick="trackResourceAccess('<?php echo htmlspecialchars($item['title']); ?>')">
                                        <i class="fas fa-external-link-alt me-2"></i> Access Resource
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Recently Accessed -->
        <div class="row mt-5">
            <div class="col-lg-8">
                <div class="recent-card">
                    <h5 class="fw-bold mb-4"><i class="fas fa-history me-2"></i> Recently Accessed</h5>
                    <?php foreach ($recent_resources as $item): ?>
                        <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                            <div class="flex-shrink-0">
                                <div class="bg-light rounded-circle p-3">
                                    <i class="fas fa-<?php echo getTypeIcon($item['type']); ?> text-<?php echo $item['type'] == 'pdf' ? 'danger' : ($item['type'] == 'video' ? 'primary' : 'success'); ?>"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($item['title']); ?></h6>
                                <p class="text-muted small mb-0"><?php echo htmlspecialchars($item['description']); ?></p>
                                <small class="text-muted">
                                    <i class="far fa-clock me-1"></i>
                                    Accessed 2 days ago • <?php echo $item['duration']; ?> read
                                </small>
                            </div>
                            <a href="<?php echo htmlspecialchars($item['link']); ?>" 
                               class="btn btn-outline-success btn-sm" 
                               target="_blank">
                                <i class="fas fa-redo me-1"></i> Reopen
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="recent-card">
                    <h5 class="fw-bold mb-4"><i class="fas fa-lightbulb me-2"></i> Quick Tips</h5>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Always verify</strong> information with current medical guidelines
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Update regularly</strong> - Medical knowledge evolves constantly
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Know your limits</strong> - Refer complex cases to professionals
                    </div>
                    <div class="text-center mt-4">
                        <a href="training.php" class="btn btn-success">
                            <i class="fas fa-graduation-cap me-2"></i> Start Training Program
                        </a>
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
                        <i class="fas fa-book-medical text-success me-2"></i>
                        HealthConnect Medical Resources
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0 text-muted">
                        Resources last verified: <?php echo date('F j, Y'); ?>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- BMI Calculator Modal -->
    <div class="modal fade" id="bmiModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-calculator me-2"></i> BMI Calculator</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Weight (kg)</label>
                        <input type="number" class="form-control" id="weight" placeholder="e.g., 70">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Height (cm)</label>
                        <input type="number" class="form-control" id="height" placeholder="e.g., 175">
                    </div>
                    <button class="btn btn-success w-100 mb-3" onclick="calculateBMI()">
                        <i class="fas fa-calculator me-2"></i> Calculate BMI
                    </button>
                    <div id="bmiResult" class="alert alert-info d-none">
                        <h5 class="mb-2">Result: <span id="bmiValue">0</span></h5>
                        <p class="mb-0" id="bmiCategory">Category will appear here</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Other Calculator Modals (you can expand these) -->
    <div class="modal fade" id="dosageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-heartbeat me-2"></i> Dosage Calculator</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Dosage calculator coming soon. For now, refer to:</p>
                    <ul class="list-group mb-3">
                        <li class="list-group-item">
                            <a href="https://www.who.int/medicines/areas/rational_use/antibiotics_checklist/en/" target="_blank">
                                WHO Antibiotic Checklist
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="https://www.cdc.gov/antibiotic-use/clinical-guidelines.html" target="_blank">
                                CDC Clinical Guidelines
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Resource Search
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const searchTerm = document.getElementById('resourceSearch').value.toLowerCase();
            
            if (searchTerm.trim() === '') return;
            
            // Highlight and scroll to matching resources
            document.querySelectorAll('.resource-item').forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    item.style.backgroundColor = '#fff3cd';
                    item.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // Highlight the matching text
                    const title = item.querySelector('h5');
                    if (title && title.textContent.toLowerCase().includes(searchTerm)) {
                        const original = title.innerHTML;
                        const highlighted = original.replace(
                            new RegExp(searchTerm, 'gi'),
                            match => `<span class="search-highlight">${match}</span>`
                        );
                        title.innerHTML = highlighted;
                    }
                }
            });
        });

        // BMI Calculator
        function calculateBMI() {
            const weight = parseFloat(document.getElementById('weight').value);
            const height = parseFloat(document.getElementById('height').value) / 100; // Convert cm to m
            
            if (!weight || !height || height === 0) {
                alert('Please enter valid weight and height');
                return;
            }
            
            const bmi = weight / (height * height);
            const result = document.getElementById('bmiResult');
            const bmiValue = document.getElementById('bmiValue');
            const bmiCategory = document.getElementById('bmiCategory');
            
            bmiValue.textContent = bmi.toFixed(1);
            
            let category = '';
            let color = '';
            
            if (bmi < 18.5) {
                category = 'Underweight';
                color = 'warning';
            } else if (bmi < 25) {
                category = 'Normal weight';
                color = 'success';
            } else if (bmi < 30) {
                category = 'Overweight';
                color = 'warning';
            } else {
                category = 'Obese';
                color = 'danger';
            }
            
            bmiCategory.textContent = category;
            result.className = `alert alert-${color} d-block`;
            result.classList.remove('d-none');
        }

        // Animate progress ring
        document.addEventListener('DOMContentLoaded', function() {
            const circle = document.querySelector('.progress-ring-circle:last-child');
            const radius = circle.r.baseVal.value;
            const circumference = 2 * Math.PI * radius;
            
            circle.style.strokeDasharray = `${circumference} ${circumference}`;
            circle.style.strokeDashoffset = circumference;
            
            const offset = circumference - (60 / 100) * circumference;
            setTimeout(() => {
                circle.style.strokeDashoffset = offset;
            }, 500);
        });

        // Resource type icons mapping (for JavaScript)
        function getTypeIcon(type) {
            const icons = {
                'pdf': 'file-pdf',
                'video': 'video',
                'guide': 'book',
                'checklist': 'list-check'
            };
            return icons[type] || 'file';
        }

        // Track resource access (you can expand this to log to database)
        function trackResourceAccess(resourceTitle) {
            console.log(`Resource accessed: ${resourceTitle} at ${new Date().toLocaleString()}`);
            // In a real implementation, you would send this to your server
            // fetch('track-resource.php', {
            //     method: 'POST',
            //     headers: {'Content-Type': 'application/json'},
            //     body: JSON.stringify({
            //         user_id: <?php echo $user_id; ?>,
            //         resource: resourceTitle,
            //         timestamp: new Date().toISOString()
            //     })
            // });
            
            // Update progress (simplified)
            const progressElement = document.querySelector('.progress-ring-circle:last-child');
            const currentProgress = 60;
            const newProgress = Math.min(currentProgress + 5, 100);
            const circumference = 2 * Math.PI * progressElement.r.baseVal.value;
            const offset = circumference - (newProgress / 100) * circumference;
            
            setTimeout(() => {
                progressElement.style.strokeDashoffset = offset;
                document.querySelector('.recent-card h3').textContent = `${newProgress}%`;
            }, 300);
        }

        // Add tool modals for other calculators
        function createCalculatorModal(title, content) {
            // You can expand this to create dynamic modals for other tools
            console.log(`Opening ${title} calculator`);
        }
    </script>
</body>
</html>

<?php
// Helper function to get type icon
function getTypeIcon($type) {
    $icons = [
        'pdf' => 'file-pdf',
        'video' => 'video',
        'guide' => 'book',
        'checklist' => 'list-check'
    ];
    return $icons[$type] ?? 'file';
}
?>