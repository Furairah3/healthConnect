<?php
// healthconnect/views/auth/my-responses.php
session_start();
require_once '../../app/config/database.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header('Location: login.php?error=required');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Handle filtering
$filter = $_GET['filter'] ?? 'all';
$category = $_GET['category'] ?? '';

// Build WHERE clause for filtering
$where_clause = "WHERE r.patient_id = :patient_id";
$params = [':patient_id' => $user_id];

if ($filter === 'responded') {
    $where_clause .= " AND r.request_status = 'responded'";
} elseif ($filter === 'pending') {
    $where_clause .= " AND r.request_status = 'pending'";
} elseif ($filter === 'closed') {
    $where_clause .= " AND r.request_status = 'closed'";
}

if (!empty($category)) {
    $where_clause .= " AND r.category = :category";
    $params[':category'] = $category;
}

// Get patient's health request responses
$sql = "SELECT r.request_id, r.request_title, r.request_description, 
               r.request_status, r.request_date, r.response_date,
               r.response_text, r.urgency_level, r.category,
               u.full_name as responded_by_name, u.profession as respondent_profession,
               v.full_name as volunteer_name, v.profession as volunteer_profession,
               r.volunteer_response
        FROM hc_medical_requests r
        LEFT JOIN hc_users u ON r.responded_by_user_id = u.user_id
        LEFT JOIN hc_users v ON r.responded_by_user_id = v.user_id
        $where_clause
        ORDER BY r.request_date DESC";
        
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$responses = $stmt->fetchAll();

// Get categories for filter
$categories_sql = "SELECT DISTINCT category FROM hc_medical_requests 
                   WHERE patient_id = :patient_id AND category IS NOT NULL 
                   ORDER BY category";
$categories_stmt = $pdo->prepare($categories_sql);
$categories_stmt->execute([':patient_id' => $user_id]);
$categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);

// Count statistics
$all_count = count(array_filter($responses, function($r) use ($filter) {
    return $filter === 'all';
}));
$responded_count = count(array_filter($responses, function($r) {
    return $r['request_status'] === 'responded';
}));
$pending_count = count(array_filter($responses, function($r) {
    return $r['request_status'] === 'pending';
}));
$closed_count = count(array_filter($responses, function($r) {
    return $r['request_status'] === 'closed';
}));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Responses - HealthConnect</title>
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
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .profile-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
        }
        
        .page-header {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        
        .page-title {
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .filter-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .response-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            transition: all 0.3s ease;
            overflow: hidden;
            border-left: 5px solid #4361ee;
        }
        
        .response-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.12);
        }
        
        .response-card.pending {
            border-left-color: var(--warning-color);
        }
        
        .response-card.responded {
            border-left-color: var(--success-color);
        }
        
        .response-card.closed {
            border-left-color: #6c757d;
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-responded {
            background: #d4edda;
            color: #155724;
        }
        
        .status-closed {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .urgency-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .urgency-low {
            background: #d4edda;
            color: #155724;
        }
        
        .urgency-medium {
            background: #fff3cd;
            color: #856404;
        }
        
        .urgency-high {
            background: #f8d7da;
            color: #721c24;
        }
        
        .category-tag {
            background: #e3e9ff;
            color: #4361ee;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .response-content {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 15px;
            border-left: 4px solid #28a745;
        }
        
        .respondent-info {
            background: #e3e9ff;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .filter-btn {
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 2px solid #dee2e6;
        }
        
        .filter-btn:hover, .filter-btn.active {
            border-color: #4361ee;
            background: rgba(67, 97, 238, 0.1);
        }
        
        .filter-btn.active {
            background: #4361ee;
            color: white;
            border-color: #4361ee;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-icon {
            font-size: 80px;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        
        .date-badge {
            background: #f8f9fa;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            color: #6c757d;
        }
        
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: #4361ee;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .back-to-top:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(67, 97, 238, 0.4);
            color: white;
        }
        
        .print-btn {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .print-btn:hover {
            transform: scale(1.1);
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            .response-card {
                box-shadow: none !important;
                border: 1px solid #dee2e6 !important;
                page-break-inside: avoid;
            }
            
            body {
                background: white !important;
            }
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
                        <a class="nav-link active" href="my-responses.php">
                            <i class="fas fa-reply me-1"></i> My Responses
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
                    <h1 class="page-title">My Medical Responses</h1>
                    <p class="lead mb-0">Review responses to your health requests from our medical team</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="create-request.php" class="btn btn-light btn-lg">
                        <i class="fas fa-plus-circle me-2"></i> New Request
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Filter Section -->
        <div class="filter-card no-print">
            <div class="row align-items-center mb-4">
                <div class="col-md-6">
                    <h5 class="mb-0">
                        <i class="fas fa-filter me-2 text-primary"></i>
                        Filter Responses
                    </h5>
                </div>
                <div class="col-md-6 text-end">
                    <span class="text-muted">
                        Showing <?php echo count($responses); ?> response<?php echo count($responses) !== 1 ? 's' : ''; ?>
                    </span>
                </div>
            </div>
            
            <!-- Status Filters -->
            <div class="mb-4">
                <h6 class="mb-3">By Status:</h6>
                <div class="d-flex flex-wrap gap-2">
                    <a href="?filter=all<?php echo $category ? '&category=' . urlencode($category) : ''; ?>" 
                       class="filter-btn btn btn-sm <?php echo $filter === 'all' ? 'active' : ''; ?>">
                        All <span class="badge bg-secondary ms-1"><?php echo count($responses); ?></span>
                    </a>
                    <a href="?filter=responded<?php echo $category ? '&category=' . urlencode($category) : ''; ?>" 
                       class="filter-btn btn btn-sm <?php echo $filter === 'responded' ? 'active' : ''; ?>">
                        <i class="fas fa-check-circle me-1"></i> Responded <span class="badge bg-success ms-1"><?php echo $responded_count; ?></span>
                    </a>
                    <a href="?filter=pending<?php echo $category ? '&category=' . urlencode($category) : ''; ?>" 
                       class="filter-btn btn btn-sm <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                        <i class="fas fa-clock me-1"></i> Pending <span class="badge bg-warning ms-1"><?php echo $pending_count; ?></span>
                    </a>
                    <a href="?filter=closed<?php echo $category ? '&category=' . urlencode($category) : ''; ?>" 
                       class="filter-btn btn btn-sm <?php echo $filter === 'closed' ? 'active' : ''; ?>">
                        <i class="fas fa-archive me-1"></i> Closed <span class="badge bg-secondary ms-1"><?php echo $closed_count; ?></span>
                    </a>
                </div>
            </div>
            
            <!-- Category Filters -->
            <?php if (!empty($categories)): ?>
            <div>
                <h6 class="mb-3">By Category:</h6>
                <div class="d-flex flex-wrap gap-2">
                    <a href="?filter=<?php echo $filter; ?>" 
                       class="filter-btn btn btn-sm <?php echo empty($category) ? 'active' : ''; ?>">
                        All Categories
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="?filter=<?php echo $filter; ?>&category=<?php echo urlencode($cat); ?>" 
                           class="filter-btn btn btn-sm <?php echo $category === $cat ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($cat); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Responses List -->
        <?php if (empty($responses)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <h4 class="mb-3">No responses yet</h4>
                <p class="text-muted mb-4">
                    <?php if ($filter !== 'all' || $category): ?>
                        No responses match your current filters. Try changing your filter criteria.
                    <?php else: ?>
                        You haven't received any responses to your health requests yet.
                    <?php endif; ?>
                </p>
                <a href="create-request.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus-circle me-2"></i> Submit Your First Request
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($responses as $response): ?>
                <div class="response-card <?php echo $response['request_status']; ?> p-4">
                    <div class="row">
                        <!-- Left Column: Request Info -->
                        <div class="col-md-8">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($response['request_title']); ?></h5>
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <span class="status-badge status-<?php echo $response['request_status']; ?>">
                                            <?php echo ucfirst($response['request_status']); ?>
                                        </span>
                                        <?php if ($response['urgency_level']): ?>
                                            <span class="urgency-badge urgency-<?php echo $response['urgency_level']; ?>">
                                                <i class="fas fa-exclamation-circle me-1"></i>
                                                <?php echo ucfirst($response['urgency_level']); ?> Priority
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($response['category']): ?>
                                            <span class="category-tag">
                                                <i class="fas fa-tag me-1"></i>
                                                <?php echo htmlspecialchars($response['category']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-end no-print">
                                    <span class="date-badge">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo date('M d, Y', strtotime($response['request_date'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <p class="text-muted mb-4">
                                <?php echo htmlspecialchars($response['request_description']); ?>
                            </p>
                        </div>
                        
                        <!-- Right Column: Response Info -->
                        <div class="col-md-4">
                            <div class="respondent-info">
                                <?php if ($response['request_status'] === 'responded' && ($response['responded_by_name'] || $response['volunteer_response'])): ?>
                                    <h6 class="fw-bold mb-3">
                                        <i class="fas fa-user-md text-success me-2"></i>
                                        Response Received
                                    </h6>
                                    
                                    <?php if ($response['responded_by_name']): ?>
                                        <div class="mb-3">
                                            <small class="text-muted d-block mb-1">Responded by:</small>
                                            <div class="d-flex align-items-center">
                                                <div class="profile-avatar me-2" style="width: 35px; height: 35px; font-size: 14px;">
                                                    <?php echo strtoupper(substr($response['responded_by_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($response['responded_by_name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($response['respondent_profession']); ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($response['response_date']): ?>
                                        <div class="mb-3">
                                            <small class="text-muted d-block mb-1">Response Date:</small>
                                            <div class="fw-bold">
                                                <?php echo date('F j, Y \a\t g:i A', strtotime($response['response_date'])); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mt-4 no-print">
                                        <?php if ($response['response_text'] || $response['volunteer_response']): ?>
                                            <button class="btn btn-outline-success btn-sm w-100 view-response-btn" 
                                                    data-response="<?php echo htmlspecialchars($response['response_text'] ?? $response['volunteer_response']); ?>"
                                                    data-respondent="<?php echo htmlspecialchars($response['responded_by_name'] ?? 'Healthcare Provider'); ?>">
                                                <i class="fas fa-eye me-1"></i> View Full Response
                                            </button>
                                        <?php endif; ?>
                                        <a href="view-request.php?id=<?php echo $response['request_id']; ?>" 
                                           class="btn btn-outline-primary btn-sm w-100 mt-2">
                                            <i class="fas fa-external-link-alt me-1"></i> View Request Details
                                        </a>
                                    </div>
                                    
                                <?php elseif ($response['request_status'] === 'pending'): ?>
                                    <div class="text-center py-3">
                                        <div class="mb-3">
                                            <i class="fas fa-clock fa-2x text-warning"></i>
                                        </div>
                                        <h6 class="fw-bold mb-2">Awaiting Response</h6>
                                        <p class="text-muted small mb-0">
                                            Your request is being reviewed by our medical team
                                        </p>
                                    </div>
                                <?php elseif ($response['request_status'] === 'closed'): ?>
                                    <div class="text-center py-3">
                                        <div class="mb-3">
                                            <i class="fas fa-archive fa-2x text-secondary"></i>
                                        </div>
                                        <h6 class="fw-bold mb-2">Case Closed</h6>
                                        <p class="text-muted small mb-0">
                                            This request has been completed
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Response Content (Hidden by default) -->
                    <?php if ($response['request_status'] === 'responded' && ($response['response_text'] || $response['volunteer_response'])): ?>
                        <div class="response-content d-none" id="response-content-<?php echo $response['request_id']; ?>">
                            <h6 class="fw-bold mb-3">
                                <i class="fas fa-stethoscope me-2 text-success"></i>
                                Medical Response
                            </h6>
                            <div class="mb-4">
                                <?php echo nl2br(htmlspecialchars($response['response_text'] ?? $response['volunteer_response'])); ?>
                            </div>
                            <div class="d-flex justify-content-between align-items-center no-print">
                                <small class="text-muted">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    This response is provided for informational purposes only.
                                </small>
                                <button class="btn btn-sm btn-outline-primary print-btn" 
                                        onclick="printResponse(<?php echo $response['request_id']; ?>)">
                                    <i class="fas fa-print me-1"></i> Print Response
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Back to Top Button -->
    <a href="#" class="back-to-top no-print">
        <i class="fas fa-arrow-up"></i>
    </a>

    <!-- Response Modal -->
    <div class="modal fade" id="responseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="responseModalTitle">Medical Response</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="responseModalBody">
                    <!-- Response content will be inserted here -->
                </div>
                <div class="modal-footer no-print">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // View full response
        document.querySelectorAll('.view-response-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const response = this.getAttribute('data-response');
                const respondent = this.getAttribute('data-respondent');
                
                document.getElementById('responseModalTitle').textContent = 
                    'Response from ' + respondent;
                document.getElementById('responseModalBody').innerHTML = 
                    '<div class="alert alert-info">' +
                    '<i class="fas fa-info-circle me-2"></i>' +
                    'This medical advice is provided for informational purposes only. ' +
                    'Always consult with a healthcare professional for medical concerns.' +
                    '</div>' +
                    '<div class="response-content p-4 mt-3">' +
                    response.replace(/\n/g, '<br>') +
                    '</div>';
                
                const modal = new bootstrap.Modal(document.getElementById('responseModal'));
                modal.show();
            });
        });
        
        // Print individual response
        function printResponse(requestId) {
            const responseContent = document.getElementById('response-content-' + requestId);
            if (responseContent) {
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Medical Response - HealthConnect</title>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; }
                            .print-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
                            .response-content { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
                            .disclaimer { font-size: 12px; color: #666; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; }
                            @page { margin: 20mm; }
                        </style>
                    </head>
                    <body>
                        <div class="print-header">
                            <h2>HealthConnect Medical Response</h2>
                            <p>Generated on ${new Date().toLocaleDateString()} at ${new Date().toLocaleTimeString()}</p>
                        </div>
                        ${responseContent.innerHTML}
                        <div class="disclaimer">
                            <p><strong>Disclaimer:</strong> This information is provided for educational purposes only and is not intended as medical advice. Always consult with a qualified healthcare professional for diagnosis and treatment.</p>
                        </div>
                    </body>
                    </html>
                `);
                printWindow.document.close();
                printWindow.print();
            }
        }
        
        // Back to top button
        const backToTop = document.querySelector('.back-to-top');
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTop.style.display = 'flex';
            } else {
                backToTop.style.display = 'none';
            }
        });
        
        backToTop.addEventListener('click', (e) => {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        
        // Expand/collapse response content
        document.querySelectorAll('.response-card').forEach(card => {
            const toggleBtn = card.querySelector('.view-response-btn');
            const contentDiv = card.querySelector('.response-content');
            
            if (toggleBtn && contentDiv) {
                toggleBtn.addEventListener('click', () => {
                    contentDiv.classList.toggle('d-none');
                    if (contentDiv.classList.contains('d-none')) {
                        toggleBtn.innerHTML = '<i class="fas fa-eye me-1"></i> View Full Response';
                    } else {
                        toggleBtn.innerHTML = '<i class="fas fa-eye-slash me-1"></i> Hide Response';
                    }
                });
            }
        });
        
        // Print all responses
        function printAllResponses() {
            window.print();
        }
    </script>
</body>
</html>