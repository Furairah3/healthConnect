<?php
// healthconnect/views/auth/admin-doctors.php
session_start();
require_once '../../app/config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php?error=required');
    exit();
}

// Handle doctor approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = $_POST['doctor_id'];
    $action = $_POST['action'];
    $admin_notes = $_POST['admin_notes'] ?? '';
    
    if ($action === 'approve') {
        // Approve doctor
        $sql = "UPDATE hc_users SET is_approved = 1 WHERE user_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $doctor_id]);
        
        // Update verification status - FIXED: changed notes to admin_notes, reviewed_at to review_date
        $sql2 = "UPDATE hc_doctor_verifications SET 
                verification_status = 'approved',
                reviewed_by_admin_id = :admin_id,
                review_date = NOW(),
                admin_notes = :notes
                WHERE doctor_user_id = :doctor_id";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([
            ':admin_id' => $_SESSION['user_id'],
            ':notes' => $admin_notes,
            ':doctor_id' => $doctor_id
        ]);
        
        $_SESSION['message'] = 'Doctor approved successfully!';
    } elseif ($action === 'reject') {
        // Reject doctor - FIXED: changed notes to admin_notes, reviewed_at to review_date
        $sql = "UPDATE hc_doctor_verifications SET 
                verification_status = 'rejected',
                reviewed_by_admin_id = :admin_id,
                review_date = NOW(),
                admin_notes = :notes
                WHERE doctor_user_id = :doctor_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':admin_id' => $_SESSION['user_id'],
            ':notes' => $admin_notes,
            ':doctor_id' => $doctor_id
        ]);
        
        $_SESSION['message'] = 'Doctor application rejected.';
    }
    
    header('Location: admin-doctors.php');
    exit();
}

// Get all doctor applications - FIXED: changed notes to admin_notes
$sql = "SELECT u.user_id, u.full_name, u.email_address, u.date_created, u.profession, u.location,
               dv.document_filename, dv.submission_date, dv.verification_status,
               dv.admin_notes
        FROM hc_users u
        LEFT JOIN hc_doctor_verifications dv ON u.user_id = dv.doctor_user_id
        WHERE u.user_role = 'doctor'
        ORDER BY 
            CASE dv.verification_status 
                WHEN 'pending_review' THEN 1
                WHEN 'approved' THEN 2
                WHEN 'rejected' THEN 3
                ELSE 4
            END,
            u.date_created DESC";
$stmt = $pdo->query($sql);
$doctors = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Doctors - HealthConnect Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,0.03);
        }
        .badge-pending { background-color: #ffc107; }
        .badge-approved { background-color: #198754; }
        .badge-rejected { background-color: #dc3545; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-user-md text-primary"></i> Doctor Applications</h2>
            <div>
                <a href="admin-dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="admin-users.php" class="btn btn-outline-secondary ms-2">
                    <i class="fas fa-users"></i> All Users
                </a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $_SESSION['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        
        <!-- Status Summary -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-clock"></i> Pending Review
                        </h5>
                        <?php 
                        $pending = array_filter($doctors, function($d) {
                            return ($d['verification_status'] ?? 'pending_review') === 'pending_review';
                        });
                        ?>
                        <h2><?php echo count($pending); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-check-circle"></i> Approved
                        </h5>
                        <?php 
                        $approved = array_filter($doctors, function($d) {
                            return ($d['verification_status'] ?? '') === 'approved';
                        });
                        ?>
                        <h2><?php echo count($approved); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-danger mb-3">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-times-circle"></i> Rejected
                        </h5>
                        <?php 
                        $rejected = array_filter($doctors, function($d) {
                            return ($d['verification_status'] ?? '') === 'rejected';
                        });
                        ?>
                        <h2><?php echo count($rejected); ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Doctor</th>
                        <th>Details</th>
                        <th>Status</th>
                        <th>Document</th>
                        <th>Applied</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($doctors as $doctor): ?>
                        <?php 
                        $status = $doctor['verification_status'] ?? 'pending_review';
                        $is_approved = $doctor['verification_status'] === 'approved';
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($doctor['full_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($doctor['email_address']); ?></small>
                            </td>
                            <td>
                                <small>
                                    <strong>Profession:</strong> <?php echo htmlspecialchars($doctor['profession'] ?? 'Not specified'); ?><br>
                                    <strong>Location:</strong> <?php echo htmlspecialchars($doctor['location'] ?? 'Not specified'); ?>
                                </small>
                            </td>
                            <td>
                                <?php 
                                $badge_class = '';
                                if ($status === 'approved') $badge_class = 'badge-approved';
                                elseif ($status === 'rejected') $badge_class = 'badge-rejected';
                                else $badge_class = 'badge-pending';
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                </span>
                                <?php if ($doctor['admin_notes']): ?>
                                    <br><small class="text-muted mt-1 d-block">
                                        <i class="fas fa-sticky-note"></i> 
                                        <?php echo htmlspecialchars($doctor['admin_notes']); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($doctor['document_filename']): ?>
                                    <a href="../../uploads/certificates/<?php echo $doctor['document_filename']; ?>" 
                                       target="_blank" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-file-pdf"></i> View Certificate
                                    </a>
                                    <small class="d-block text-muted mt-1">
                                        <?php echo date('M d', strtotime($doctor['submission_date'])); ?>
                                    </small>
                                <?php else: ?>
                                    <span class="text-muted"><i class="fas fa-times-circle"></i> No document</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small><?php echo date('M d, Y', strtotime($doctor['date_created'])); ?></small>
                            </td>
                            <td>
                                <?php if ($status === 'pending_review'): ?>
                                    <div class="btn-group-vertical" role="group" aria-label="Doctor Actions">
                                        <form method="POST" class="mb-2">
                                            <input type="hidden" name="doctor_id" value="<?php echo $doctor['user_id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <textarea name="admin_notes" class="form-control form-control-sm mb-1" 
                                                      placeholder="Approval notes (optional)" rows="1"></textarea>
                                            <button type="submit" class="btn btn-success btn-sm w-100">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>
                                        <form method="POST">
                                            <input type="hidden" name="doctor_id" value="<?php echo $doctor['user_id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <textarea name="admin_notes" class="form-control form-control-sm mb-1" 
                                                      placeholder="Rejection reason" rows="1" required></textarea>
                                            <button type="submit" class="btn btn-danger btn-sm w-100">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </form>
                                    </div>
                                <?php elseif ($status === 'approved'): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle"></i> Approved
                                    </span>
                                    <?php if ($doctor['admin_notes']): ?>
                                        <br><small class="text-muted"><?php echo substr($doctor['admin_notes'], 0, 30); ?>...</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-danger">
                                        <i class="fas fa-times-circle"></i> Rejected
                                    </span>
                                    <?php if ($doctor['admin_notes']): ?>
                                        <br><small class="text-muted"><?php echo substr($doctor['admin_notes'], 0, 30); ?>...</small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (empty($doctors)): ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle"></i> No doctor applications found.
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-resize textareas
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        });
    </script>
</body>
</html>