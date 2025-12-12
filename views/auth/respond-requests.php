<?php
// healthconnect/views/auth/respond-requests.php
session_start();
require_once '../../app/config/database.php';

// Check if user is logged in and is a volunteer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'volunteer') {
    header('Location: login.php?error=required');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Handle responding to a request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond_to_request'])) {
    $request_id = $_POST['request_id'];
    $response_text = cleanInput($_POST['response_text']);
    
    // Update the request with volunteer's response
    $sql = "UPDATE hc_medical_requests 
            SET responded_by_user_id = :volunteer_id, 
                response_text = :response_text,
                response_date = NOW(),
                request_status = 'responded'
            WHERE request_id = :request_id AND request_status = 'pending'";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':volunteer_id' => $user_id,
        ':response_text' => $response_text,
        ':request_id' => $request_id
    ]);
    
    // Log activity
    logActivity($user_id, 'respond', 'Responded to medical request #' . $request_id);
    
    $_SESSION['success'] = 'Response submitted successfully!';
    header('Location: respond-requests.php?success=responded');
    exit();
}

// Handle search and filters
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$urgency = isset($_GET['urgency']) ? cleanInput($_GET['urgency']) : '';
$category = isset($_GET['category']) ? cleanInput($_GET['category']) : '';

// Build query with filters
$sql = "SELECT r.request_id, r.request_title, r.request_description, 
               r.request_date, r.urgency_level, r.category, r.request_status,
               u.full_name as patient_name, u.profession as patient_profession
        FROM hc_medical_requests r
        JOIN hc_users u ON r.patient_id = u.user_id
        WHERE r.request_status = 'pending'";

$params = [];

if (!empty($search)) {
    $sql .= " AND (r.request_title LIKE :search OR r.request_description LIKE :search OR u.full_name LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($urgency) && $urgency !== 'all') {
    $sql .= " AND r.urgency_level = :urgency";
    $params[':urgency'] = $urgency;
}

if (!empty($category) && $category !== 'all') {
    $sql .= " AND r.category = :category";
    $params[':category'] = $category;
}

$sql .= " ORDER BY 
            CASE r.urgency_level 
                WHEN 'high' THEN 1
                WHEN 'medium' THEN 2
                WHEN 'low' THEN 3
                ELSE 4
            END, 
            r.request_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Get available categories for filter
$category_sql = "SELECT DISTINCT category FROM hc_medical_requests WHERE category IS NOT NULL AND category != ''";
$category_stmt = $pdo->prepare($category_sql);
$category_stmt->execute();
$categories = $category_stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Requests - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        :root {
            --volunteer-primary: #198754;
            --volunteer-secondary: #146c43;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--volunteer-primary) 0%, var(--volunteer-secondary) 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        
        .urgency-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .urgency-high {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid #dc3545;
        }
        
        .urgency-medium {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
            border: 1px solid #ffc107;
        }
        
        .urgency-low {
            background: rgba(13, 202, 240, 0.1);
            color: #0dcaf0;
            border: 1px solid #0dcaf0;
        }
        
        .request-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            margin-bottom: 20px;
            border-left: 4px solid var(--volunteer-primary);
        }
        
        .request-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .request-card.responded {
            border-left: 4px solid #6c757d;
            opacity: 0.8;
        }
        
        .category-tag {
            display: inline-block;
            background: #e9ecef;
            color: #495057;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        
        .time-badge {
            background: #f8f9fa;
            color: #6c757d;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
        }
        
        .patient-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .response-form {
            background: linear-gradient(135deg, rgba(25, 135, 84, 0.05), rgba(20, 108, 67, 0.05));
            border-radius: 10px;
            padding: 20px;
            border: 1px solid rgba(25, 135, 84, 0.1);
        }
        
        .filter-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state-icon {
            font-size: 60px;
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .modal-lg-custom {
            max-width: 800px;
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
                        <a class="nav-link active" href="respond-requests.php">
                            <i class="fas fa-hands-helping me-1"></i> Help Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my-responses.php">
                            <i class="fas fa-history me-1"></i> My Responses
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="resources.php">
                            <i class="fas fa-book-medical me-1"></i> Resources
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
                    <h1 class="fw-bold mb-3">Help Those in Need 🙏</h1>
                    <p class="lead mb-0">Provide medical advice and support to patients in rural areas.</p>
                </div>
                <div class="col-lg-4 text-end">
                    <div class="alert alert-light d-inline-block">
                        <i class="fas fa-info-circle text-success me-2"></i>
                        <strong><?php echo count($requests); ?></strong> requests need your help
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Success Message -->
        <?php if (isset($_GET['success']) && $_GET['success'] === 'responded'): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Thank you!</strong> Your response has been submitted and will help the patient.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Search and Filters -->
        <div class="filter-card">
            <h5 class="fw-bold mb-4"><i class="fas fa-filter me-2"></i> Filter Requests</h5>
            <form method="GET" action="respond-requests.php">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Search</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" 
                                   name="search" placeholder="Search by title, description, or patient name..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Urgency Level</label>
                        <select class="form-select" name="urgency">
                            <option value="all" <?php echo $urgency === 'all' || empty($urgency) ? 'selected' : ''; ?>>All Urgency Levels</option>
                            <option value="high" <?php echo $urgency === 'high' ? 'selected' : ''; ?>>High Priority</option>
                            <option value="medium" <?php echo $urgency === 'medium' ? 'selected' : ''; ?>>Medium Priority</option>
                            <option value="low" <?php echo $urgency === 'low' ? 'selected' : ''; ?>>Low Priority</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category">
                            <option value="all" <?php echo $category === 'all' || empty($category) ? 'selected' : ''; ?>>All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" 
                                    <?php echo $category === $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($cat)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-success px-4">
                                <i class="fas fa-filter me-2"></i> Apply Filters
                            </button>
                            <a href="respond-requests.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i> Clear Filters
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Requests List -->
        <?php if (empty($requests)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="fw-bold mb-3">No Requests Found!</h3>
                <p class="text-muted mb-4">All current requests have been responded to. Check back later for new requests.</p>
                <a href="volunteer-dashboard.php" class="btn btn-success">
                    <i class="fas fa-home me-2"></i> Back to Dashboard
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($requests as $request): ?>
                    <div class="col-lg-6 mb-4">
                        <div class="request-card p-4">
                            <!-- Request Header -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <span class="urgency-badge urgency-<?php echo $request['urgency_level']; ?>">
                                        <i class="fas fa-exclamation-circle me-1"></i>
                                        <?php echo ucfirst($request['urgency_level']); ?> Priority
                                    </span>
                                </div>
                                <span class="time-badge">
                                    <i class="far fa-clock me-1"></i>
                                    <?php echo timeAgo($request['request_date']); ?>
                                </span>
                            </div>
                            
                            <!-- Request Title -->
                            <h5 class="fw-bold mb-3"><?php echo htmlspecialchars($request['request_title']); ?></h5>
                            
                            <!-- Category -->
                            <?php if ($request['category']): ?>
                                <div class="mb-3">
                                    <span class="category-tag">
                                        <i class="fas fa-tag me-1"></i>
                                        <?php echo htmlspecialchars(ucfirst($request['category'])); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Request Description -->
                            <p class="text-muted mb-4">
                                <?php echo nl2br(htmlspecialchars(substr($request['request_description'], 0, 200))); ?>
                                <?php if (strlen($request['request_description']) > 200): ?>
                                    ... <a href="#" data-bs-toggle="modal" data-bs-target="#requestModal<?php echo $request['request_id']; ?>" class="text-success">Read more</a>
                                <?php endif; ?>
                            </p>
                            
                            <!-- Patient Info -->
                            <div class="patient-info">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($request['patient_name']); ?></h6>
                                        <?php if ($request['patient_profession']): ?>
                                            <p class="text-muted small mb-0">
                                                <i class="fas fa-briefcase me-1"></i>
                                                <?php echo htmlspecialchars($request['patient_profession']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#responseModal<?php echo $request['request_id']; ?>">
                                    <i class="fas fa-comment-medical me-2"></i> Provide Help
                                </button>
                                <a href="#" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#requestModal<?php echo $request['request_id']; ?>">
                                    <i class="fas fa-eye me-2"></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Request Details Modal -->
                    <div class="modal fade" id="requestModal<?php echo $request['request_id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg-custom">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Request Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-4">
                                        <span class="urgency-badge urgency-<?php echo $request['urgency_level']; ?> mb-3 d-inline-block">
                                            <i class="fas fa-exclamation-circle me-1"></i>
                                            <?php echo ucfirst($request['urgency_level']); ?> Priority
                                        </span>
                                        <h4 class="fw-bold"><?php echo htmlspecialchars($request['request_title']); ?></h4>
                                        <p class="text-muted">
                                            <i class="far fa-clock me-1"></i>
                                            Posted <?php echo date('F j, Y g:i A', strtotime($request['request_date'])); ?>
                                        </p>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h6 class="fw-bold mb-2">Description</h6>
                                        <div class="bg-light p-3 rounded">
                                            <?php echo nl2br(htmlspecialchars($request['request_description'])); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="patient-info">
                                        <h6 class="fw-bold mb-3">About the Patient</h6>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($request['patient_name']); ?></h5>
                                                <?php if ($request['patient_profession']): ?>
                                                    <p class="text-muted mb-0">
                                                        <i class="fas fa-briefcase me-1"></i>
                                                        <?php echo htmlspecialchars($request['patient_profession']); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#responseModal<?php echo $request['request_id']; ?>" data-bs-dismiss="modal">
                                        <i class="fas fa-comment-medical me-2"></i> Provide Help
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Response Modal -->
                    <div class="modal fade" id="responseModal<?php echo $request['request_id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg-custom">
                            <div class="modal-content">
                                <form method="POST" action="respond-requests.php">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Provide Medical Help</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                        <input type="hidden" name="respond_to_request" value="1">
                                        
                                        <div class="alert alert-info">
                                            <i class="fas fa-lightbulb me-2"></i>
                                            <strong>Tips for a good response:</strong> Be clear, professional, and compassionate. Include specific advice when possible.
                                        </div>
                                        
                                        <div class="mb-4">
                                            <h6 class="fw-bold mb-2">Request Summary</h6>
                                            <div class="bg-light p-3 rounded">
                                                <p class="fw-bold mb-1"><?php echo htmlspecialchars($request['request_title']); ?></p>
                                                <p class="text-muted mb-0"><?php echo htmlspecialchars(substr($request['request_description'], 0, 150)); ?>...</p>
                                            </div>
                                        </div>
                                        
                                        <div class="response-form">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Your Response *</label>
                                                <textarea class="form-control" name="response_text" rows="8" 
                                                          placeholder="Provide your medical advice, suggestions, or guidance here. Be as detailed as possible..." 
                                                          required></textarea>
                                                <div class="form-text">
                                                    Minimum 50 characters. Include:
                                                    <ul class="mb-0">
                                                        <li>Clear medical advice</li>
                                                        <li>Recommended actions</li>
                                                        <li>When to seek in-person care</li>
                                                        <li>Any helpful resources</li>
                                                    </ul>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Response Type</label>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="response_type" id="advice<?php echo $request['request_id']; ?>" value="advice" checked>
                                                    <label class="form-check-label" for="advice<?php echo $request['request_id']; ?>">
                                                        <i class="fas fa-comment-medical me-1"></i> Medical Advice
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="response_type" id="referral<?php echo $request['request_id']; ?>" value="referral">
                                                    <label class="form-check-label" for="referral<?php echo $request['request_id']; ?>">
                                                        <i class="fas fa-hospital me-1"></i> Specialist Referral Suggestion
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="response_type" id="resources<?php echo $request['request_id']; ?>" value="resources">
                                                    <label class="form-check-label" for="resources<?php echo $request['request_id']; ?>">
                                                        <i class="fas fa-book-medical me-1"></i> Provide Resources
                                                    </label>
                                                </div>
                                            </div>
                                            
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                <strong>Disclaimer:</strong> Your response is for informational purposes only and does not replace professional medical care. Always advise patients to seek in-person medical attention for serious conditions.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-paper-plane me-2"></i> Submit Response
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination Info -->
            <div class="d-flex justify-content-between align-items-center mt-4">
                <p class="text-muted mb-0">
                    Showing <strong><?php echo count($requests); ?></strong> of <strong><?php echo count($requests); ?></strong> requests
                </p>
                <a href="#top" class="btn btn-outline-success">
                    <i class="fas fa-arrow-up me-2"></i> Back to Top
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">
                        <i class="fas fa-hands-helping text-success me-2"></i>
                        HealthConnect Volunteer Portal
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0 text-muted">
                        Need help? <a href="resources.php" class="text-success">Visit Resources</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Character counter for response text
        document.querySelectorAll('textarea[name="response_text"]').forEach(textarea => {
            const formGroup = textarea.closest('.mb-3');
            const counter = document.createElement('div');
            counter.className = 'form-text text-end';
            counter.innerHTML = '<span class="char-count">0</span> / 5000 characters';
            formGroup.appendChild(counter);
            
            textarea.addEventListener('input', function() {
                const charCount = this.value.length;
                counter.querySelector('.char-count').textContent = charCount;
                
                if (charCount < 50) {
                    counter.style.color = '#dc3545';
                } else if (charCount < 100) {
                    counter.style.color = '#ffc107';
                } else {
                    counter.style.color = '#198754';
                }
            });
            
            // Trigger initial count
            textarea.dispatchEvent(new Event('input'));
        });
        
        // Auto-focus on response modal when opened
        document.addEventListener('shown.bs.modal', function(event) {
            const modal = event.target;
            const textarea = modal.querySelector('textarea[name="response_text"]');
            if (textarea) {
                textarea.focus();
            }
        });
        
        // Auto-close modals when response submitted
        document.addEventListener('submit', function(event) {
            if (event.target.closest('form') && event.target.querySelector('input[name="respond_to_request"]')) {
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(event.target.closest('.modal'));
                    if (modal) {
                        modal.hide();
                    }
                }, 100);
            }
        });
        
        // Smooth scroll to top
        document.querySelector('a[href="#top"]').addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    </script>
</body>
</html>

<?php
// Helper function to display time ago
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}
?>