<?php
// healthconnect/views/auth/admin-users.php
session_start();
require_once '../../app/config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php?error=required');
    exit();
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];
    
    if ($action === 'delete') {
        // Delete user (except admin)
        $sql = "DELETE FROM hc_users WHERE user_id = :id AND user_role != 'admin'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $user_id]);
        
        $_SESSION['message'] = 'User deleted successfully.';
    } elseif ($action === 'toggle_status') {
        // Toggle user active status
        $sql = "UPDATE hc_users SET is_active = NOT is_active WHERE user_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $user_id]);
        
        $_SESSION['message'] = 'User status updated.';
    }
    
    header('Location: admin-users.php');
    exit();
}

// Get all users
$sql = "SELECT user_id, full_name, email_address, user_role, is_approved, is_active, 
               profession, location, created_at
        FROM hc_users
        ORDER BY created_at DESC";
$stmt = $pdo->query($sql);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - HealthConnect Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-users text-primary"></i> User Management</h2>
            <div>
                <a href="admin-dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <a href="#" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-plus"></i> Add User
                </a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Details</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($user['full_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($user['email_address']); ?></small>
                            </td>
                            <td>
                                <?php 
                                $role_badge = [
                                    'patient' => 'primary',
                                    'volunteer' => 'success', 
                                    'doctor' => 'info',
                                    'admin' => 'danger'
                                ];
                                ?>
                                <span class="badge bg-<?php echo $role_badge[$user['user_role']] ?? 'secondary'; ?>">
                                    <?php echo ucfirst($user['user_role']); ?>
                                </span>
                            </td>
                            <td>
                                <small>
                                    <?php if ($user['profession']): ?>
                                        <strong>Profession:</strong> <?php echo htmlspecialchars($user['profession']); ?><br>
                                    <?php endif; ?>
                                    <?php if ($user['location']): ?>
                                        <strong>Location:</strong> <?php echo htmlspecialchars($user['location']); ?>
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($user['user_role'] === 'doctor'): ?>
                                    <span class="badge bg-<?php echo $user['is_approved'] ? 'success' : 'warning'; ?>">
                                        <?php echo $user['is_approved'] ? 'Approved' : 'Pending'; ?>
                                    </span><br>
                                <?php endif; ?>
                                <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <small><?php echo date('M d, Y', strtotime($user['created_at'])); ?></small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if ($user['user_role'] !== 'admin'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <button type="submit" class="btn btn-<?php echo $user['is_active'] ? 'warning' : 'success'; ?>">
                                                <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this user?');">
                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">Admin Account</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>