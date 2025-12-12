<?php
// healthconnect/views/auth/community.php
session_start();
require_once '../../app/config/database.php';

// Check if user is logged in and is a volunteer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'volunteer') {
    header('Location: login.php?error=required');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get all volunteers for the community - FIXED: removed user_status column
$sql = "SELECT u.user_id, u.full_name, u.profession, u.location, u.created_at,
               (SELECT COUNT(*) FROM hc_medical_requests WHERE responded_by_user_id = u.user_id AND request_status = 'closed') as helped_count,
               (SELECT COUNT(*) FROM hc_medical_requests WHERE responded_by_user_id = u.user_id) as total_responses
        FROM hc_users u
        WHERE u.user_role = 'volunteer' AND u.is_active = 1  -- FIXED: using is_active instead of user_status
        ORDER BY helped_count DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$volunteers = $stmt->fetchAll();

// Get community statistics
$sql = "SELECT 
        COUNT(DISTINCT u.user_id) as total_volunteers,
        COUNT(DISTINCT CASE WHEN u.location LIKE '%remote%' OR u.location LIKE '%rural%' THEN u.user_id END) as remote_volunteers,
        SUM(CASE WHEN r.request_status = 'closed' THEN 1 ELSE 0 END) as total_helped,
        COUNT(DISTINCT r.patient_id) as unique_patients_helped
        FROM hc_users u
        LEFT JOIN hc_medical_requests r ON u.user_id = r.responded_by_user_id
        WHERE u.user_role = 'volunteer' AND u.is_active = 1";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$community_stats = $stmt->fetch();

// Check if forum tables exist and get recent posts
$forum_posts = [];
try {
    // First check if the forum table exists
    $checkTable = $pdo->query("SHOW TABLES LIKE 'hc_forum_posts'")->fetch();
    
    if ($checkTable) {
        $sql = "SELECT f.*, u.full_name as author_name, u.profession as author_profession,
                       (SELECT COUNT(*) FROM hc_forum_comments c WHERE c.post_id = f.post_id) as comment_count
                FROM hc_forum_posts f
                JOIN hc_users u ON f.author_id = u.user_id
                WHERE f.category IN ('volunteer', 'general', 'healthcare')
                GROUP BY f.post_id
                ORDER BY f.created_at DESC
                LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $forum_posts = $stmt->fetchAll();
    }
} catch (Exception $e) {
    // Table doesn't exist, continue without forum posts
    $forum_posts = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Community - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        :root {
            --community-primary: #4a6bdf;
            --community-secondary: #3a56b9;
        }
        
        .community-hero {
            background: linear-gradient(135deg, var(--community-primary) 0%, var(--community-secondary) 100%);
            color: white;
            padding: 80px 0 50px;
            border-bottom-left-radius: 30px;
            border-bottom-right-radius: 30px;
        }
        
        .community-stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: none;
        }
        
        .community-stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .stat-icon-wrapper {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin-bottom: 20px;
        }
        
        .volunteer-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .volunteer-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .volunteer-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--community-primary), var(--community-secondary));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
        }
        
        .volunteer-badge {
            font-size: 12px;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .badge-top-volunteer {
            background: linear-gradient(135deg, #ffd700, #ffa500);
            color: #333;
        }
        
        .badge-active {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .forum-post {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .forum-post:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .category-badge {
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 15px;
            font-weight: 600;
        }
        
        .discussion {
            background: linear-gradient(135deg, #4a6bdf, #3a56b9);
            color: white;
        }
        
        .healthcare {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .general {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }
        
        .online-dot {
            width: 10px;
            height: 10px;
            background: #28a745;
            border-radius: 50%;
            position: absolute;
            bottom: 0;
            right: 0;
            border: 2px solid white;
        }
        
        .search-bar {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            padding: 10px 20px;
            color: white;
        }
        
        .search-bar::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .community-tabs .nav-link {
            color: #666;
            border: none;
            font-weight: 600;
            padding: 12px 25px;
        }
        
        .community-tabs .nav-link.active {
            color: var(--community-primary);
            border-bottom: 3px solid var(--community-primary);
            background: transparent;
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
                        <a class="nav-link" href="volunteer-dashboard.php">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="community.php">
                            <i class="fas fa-users me-1"></i> Community
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="respond-requests.php">
                            <i class="fas fa-hands-helping me-1"></i> Help Requests
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="volunteer-avatar me-2">
                                <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                            </div>
                            <span><?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="impact.php"><i class="fas fa-chart-line me-2"></i> My Impact</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Community Hero Section -->
    <div class="community-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="fw-bold display-4 mb-3">Volunteer Community</h1>
                    <p class="lead mb-4 opacity-75">Connect, share, and grow with fellow healthcare volunteers making a difference.</p>
                    <div class="input-group mb-3" style="max-width: 500px;">
                        <input type="text" class="form-control search-bar" placeholder="Search volunteers or topics...">
                        <button class="btn btn-light" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-lg-6 text-end">
                    <!-- You can add an image here if you have one -->
                    <div class="hero-icon-circle" style="display: inline-block;">
                        <i class="fas fa-users fa-6x text-white opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Community Statistics -->
    <div class="container mt-5">
        <div class="row g-4 mb-5">
            <div class="col-lg-3 col-md-6">
                <div class="community-stat-card text-center">
                    <div class="stat-icon-wrapper" style="background: rgba(74, 107, 223, 0.1); color: var(--community-primary);">
                        <i class="fas fa-users"></i>
                    </div>
                    <h2 class="fw-bold mb-2"><?php echo $community_stats['total_volunteers'] ?? count($volunteers); ?></h2>
                    <p class="text-muted mb-0">Total Volunteers</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="community-stat-card text-center">
                    <div class="stat-icon-wrapper" style="background: rgba(40, 167, 69, 0.1); color: #28a745;">
                        <i class="fas fa-hands-helping"></i>
                    </div>
                    <h2 class="fw-bold mb-2"><?php echo $community_stats['total_helped'] ?? 0; ?></h2>
                    <p class="text-muted mb-0">Lives Impacted</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="community-stat-card text-center">
                    <div class="stat-icon-wrapper" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                        <i class="fas fa-globe-americas"></i>
                    </div>
                    <h2 class="fw-bold mb-2"><?php echo $community_stats['remote_volunteers'] ?? 0; ?></h2>
                    <p class="text-muted mb-0">Remote Volunteers</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="community-stat-card text-center">
                    <div class="stat-icon-wrapper" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <h2 class="fw-bold mb-2"><?php echo $community_stats['unique_patients_helped'] ?? 0; ?></h2>
                    <p class="text-muted mb-0">Patients Helped</p>
                </div>
            </div>
        </div>

        <!-- Community Tabs -->
        <ul class="nav nav-tabs community-tabs mb-4" id="communityTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="volunteers-tab" data-bs-toggle="tab" data-bs-target="#volunteers" type="button" role="tab">
                    <i class="fas fa-users me-2"></i> Volunteers
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="forum-tab" data-bs-toggle="tab" data-bs-target="#forum" type="button" role="tab">
                    <i class="fas fa-comments me-2"></i> Discussions
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="events-tab" data-bs-toggle="tab" data-bs-target="#events" type="button" role="tab">
                    <i class="fas fa-calendar-alt me-2"></i> Events
                </button>
            </li>
        </ul>

        <div class="tab-content" id="communityTabsContent">
            <!-- Volunteers Tab -->
            <div class="tab-pane fade show active" id="volunteers" role="tabpanel">
                <div class="row g-4">
                    <?php if (empty($volunteers)): ?>
                        <div class="col-12 text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5>No volunteers found</h5>
                            <p class="text-muted">Be the first to join as a volunteer!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($volunteers as $volunteer): ?>
                            <div class="col-lg-4 col-md-6">
                                <div class="volunteer-card">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="position-relative">
                                                <div class="volunteer-avatar">
                                                    <?php echo strtoupper(substr($volunteer['full_name'], 0, 1)); ?>
                                                </div>
                                                <?php if (rand(0, 1)): ?>
                                                    <div class="online-dot"></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ms-3 flex-grow-1">
                                                <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($volunteer['full_name']); ?></h6>
                                                <p class="text-muted small mb-0"><?php echo htmlspecialchars($volunteer['profession']); ?></p>
                                            </div>
                                            <?php if ($volunteer['helped_count'] > 5): ?>
                                                <span class="volunteer-badge badge-top-volunteer">
                                                    <i class="fas fa-star me-1"></i> Top
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between mb-3">
                                            <div class="text-center">
                                                <div class="fw-bold text-success"><?php echo $volunteer['helped_count']; ?></div>
                                                <small class="text-muted">Helped</small>
                                            </div>
                                            <div class="text-center">
                                                <div class="fw-bold text-primary"><?php echo $volunteer['total_responses']; ?></div>
                                                <small class="text-muted">Responses</small>
                                            </div>
                                            <div class="text-center">
                                                <div class="fw-bold text-info">
                                                    <?php echo date('M Y', strtotime($volunteer['created_at'])); ?>
                                                </div>
                                                <small class="text-muted">Joined</small>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span class="text-muted small">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?php echo htmlspecialchars($volunteer['location'] ?? 'Not specified'); ?>
                                            </span>
                                            <button class="btn btn-sm btn-outline-primary" onclick="sendMessage(<?php echo $volunteer['user_id']; ?>)">
                                                <i class="fas fa-envelope me-1"></i> Message
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Forum Tab -->
            <div class="tab-pane fade" id="forum" role="tabpanel">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="mb-4">
                            <h4 class="fw-bold mb-3">Recent Discussions</h4>
                            <?php if (empty($forum_posts)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                    <h5>No discussions yet</h5>
                                    <p class="text-muted">Be the first to start a discussion!</p>
                                    <button class="btn btn-primary" onclick="startDiscussion()">
                                        <i class="fas fa-plus me-2"></i> Start Discussion
                                    </button>
                                </div>
                            <?php else: ?>
                                <?php foreach ($forum_posts as $post): ?>
                                    <div class="forum-post">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="volunteer-avatar me-3">
                                                    <?php echo strtoupper(substr($post['author_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($post['author_name']); ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($post['author_profession']); ?></small>
                                                </div>
                                            </div>
                                            <span class="category-badge <?php echo htmlspecialchars($post['category']); ?>">
                                                <?php echo ucfirst(htmlspecialchars($post['category'])); ?>
                                            </span>
                                        </div>
                                        
                                        <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($post['title']); ?></h5>
                                        <p class="text-muted mb-3"><?php echo substr(htmlspecialchars($post['content']), 0, 150); ?>...</p>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo date('F j, Y', strtotime($post['created_at'])); ?>
                                            </small>
                                            <div>
                                                <span class="badge bg-light text-dark me-2">
                                                    <i class="fas fa-comment me-1"></i> <?php echo $post['comment_count']; ?>
                                                </span>
                                                <button class="btn btn-sm btn-outline-primary" onclick="replyToPost(<?php echo $post['post_id']; ?>)">
                                                    <i class="fas fa-reply me-1"></i> Reply
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h6 class="fw-bold mb-0"><i class="fas fa-bolt me-2"></i> Quick Actions</h6>
                            </div>
                            <div class="card-body">
                                <button class="btn btn-primary w-100 mb-3" onclick="startDiscussion()">
                                    <i class="fas fa-plus me-2"></i> Start New Discussion
                                </button>
                                <button class="btn btn-outline-primary w-100 mb-3" onclick="searchTopics()">
                                    <i class="fas fa-search me-2"></i> Search Topics
                                </button>
                                <button class="btn btn-outline-success w-100" onclick="createEvent()">
                                    <i class="fas fa-calendar-alt me-2"></i> Create Event
                                </button>
                            </div>
                        </div>
                        
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h6 class="fw-bold mb-0"><i class="fas fa-fire me-2"></i> Popular Topics</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <span class="badge bg-danger me-2">#1</span>
                                    <div>
                                        <small class="fw-bold d-block">Telemedicine Tips</small>
                                        <small class="text-muted">Best practices for remote consultations</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center mb-3">
                                    <span class="badge bg-warning me-2">#2</span>
                                    <div>
                                        <small class="fw-bold d-block">Mental Health Support</small>
                                        <small class="text-muted">Resources for psychological first aid</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center mb-3">
                                    <span class="badge bg-info me-2">#3</span>
                                    <div>
                                        <small class="fw-bold d-block">Rural Healthcare</small>
                                        <small class="text-muted">Challenges and solutions</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Events Tab -->
            <div class="tab-pane fade" id="events" role="tabpanel">
                <div class="row">
                    <div class="col-lg-8">
                        <h4 class="fw-bold mb-4">Upcoming Events</h4>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-start mb-3">
                                            <div class="bg-primary text-white rounded p-3 me-3">
                                                <div class="text-center">
                                                    <div class="fw-bold fs-4">15</div>
                                                    <small>JAN</small>
                                                </div>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold mb-1">Telemedicine Workshop</h6>
                                                <small class="text-muted"><i class="fas fa-clock me-1"></i> 2:00 PM - 4:00 PM</small>
                                            </div>
                                        </div>
                                        <p class="text-muted small mb-3">Learn advanced telemedicine techniques for rural healthcare.</p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-users me-1"></i> 45 attending
                                            </span>
                                            <button class="btn btn-sm btn-primary">RSVP</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-start mb-3">
                                            <div class="bg-success text-white rounded p-3 me-3">
                                                <div class="text-center">
                                                    <div class="fw-bold fs-4">22</div>
                                                    <small>JAN</small>
                                                </div>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold mb-1">Mental Health First Aid</h6>
                                                <small class="text-muted"><i class="fas fa-clock me-1"></i> 10:00 AM - 1:00 PM</small>
                                            </div>
                                        </div>
                                        <p class="text-muted small mb-3">Training session on mental health support in rural areas.</p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-users me-1"></i> 28 attending
                                            </span>
                                            <button class="btn btn-sm btn-success">Join</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h6 class="fw-bold mb-0"><i class="fas fa-calendar-plus me-2"></i> Create Event</h6>
                            </div>
                            <div class="card-body">
                                <form id="eventForm">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Event Title</label>
                                        <input type="text" class="form-control" placeholder="Workshop title" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Date & Time</label>
                                        <input type="datetime-local" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Description</label>
                                        <textarea class="form-control" rows="3" placeholder="Event description" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-calendar-plus me-2"></i> Create Event
                                    </button>
                                </form>
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
                        <i class="fas fa-users text-primary me-2"></i>
                        HealthConnect Volunteer Community
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
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Search functionality
        document.querySelector('.search-bar').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                const query = this.value.toLowerCase();
                const volunteerCards = document.querySelectorAll('.volunteer-card');
                
                volunteerCards.forEach(card => {
                    const text = card.textContent.toLowerCase();
                    if (text.includes(query)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }
        });
        
        // Placeholder functions for actions
        function sendMessage(userId) {
            alert('Message feature will be implemented soon! User ID: ' + userId);
        }
        
        function startDiscussion() {
            alert('Discussion feature coming soon!');
        }
        
        function replyToPost(postId) {
            alert('Reply to post ' + postId + ' - feature coming soon!');
        }
        
        function searchTopics() {
            const query = prompt('Enter search keywords:');
            if (query) {
                alert('Searching for: ' + query);
            }
        }
        
        function createEvent() {
            alert('Event creation feature coming soon!');
        }
        
        // Event form submission
        document.getElementById('eventForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Event created successfully!');
            this.reset();
        });
    </script>
</body>
</html>