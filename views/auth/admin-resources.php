<?php
// healthconnect/views/auth/admin-resources.php
session_start();
require_once '../../app/config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php?error=required');
    exit();
}

// Handle resource upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? 'general';
    $access_level = $_POST['access_level'] ?? 'all';
    
    // File upload handling
    if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['resource_file'];
        $allowed_types = [
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'video/mp4' => 'mp4',
            'image/jpeg' => 'jpg',
            'image/png' => 'png'
        ];
        
        if (array_key_exists($file['type'], $allowed_types)) {
            $upload_dir = '../../uploads/resources/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $extension = $allowed_types[$file['type']];
            $filename = uniqid('resource_') . '.' . $extension;
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                // Insert into database
                $sql = "INSERT INTO hc_training_resources 
                        (title, description, category, file_path, file_type, access_level, uploaded_by_admin_id)
                        VALUES (:title, :desc, :category, :path, :type, :access, :admin_id)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':title' => $title,
                    ':desc' => $description,
                    ':category' => $category,
                    ':path' => $filename,
                    ':type' => $extension,
                    ':access' => $access_level,
                    ':admin_id' => $_SESSION['user_id']
                ]);
                
                $_SESSION['message'] = 'Resource uploaded successfully!';
            } else {
                $_SESSION['error'] = 'Failed to upload file.';
            }
        } else {
            $_SESSION['error'] = 'Invalid file type. Allowed: PDF, DOC, MP4, JPG, PNG';
        }
    }
    
    header('Location: admin-resources.php');
    exit();
}

// Get existing resources
$sql = "SELECT * FROM hc_training_resources ORDER BY upload_date DESC";
$stmt = $pdo->query($sql);
$resources = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Resources - HealthConnect Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <!-- Upload Form -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-upload me-2"></i> Upload Training Resource</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['message'])): ?>
                            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Resource Title *</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select">
                                    <option value="first_aid">First Aid</option>
                                    <option value="common_conditions">Common Conditions</option>
                                    <option value="medication">Medication</option>
                                    <option value="maternal">Maternal Health</option>
                                    <option value="mental_health">Mental Health</option>
                                    <option value="communication">Communication</option>
                                    <option value="general">General</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Access Level</label>
                                <select name="access_level" class="form-select">
                                    <option value="all">All Users</option>
                                    <option value="volunteers">Volunteers Only</option>
                                    <option value="doctors">Doctors Only</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Resource File *</label>
                                <input type="file" name="resource_file" class="form-control" accept=".pdf,.doc,.docx,.mp4,.jpg,.png" required>
                                <small class="text-muted">Max 10MB. Allowed: PDF, DOC, MP4, JPG, PNG</small>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-upload me-2"></i> Upload Resource
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Existing Resources -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-book-medical me-2"></i> Existing Resources</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($resources)): ?>
                            <p class="text-muted text-center">No resources uploaded yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Category</th>
                                            <th>Type</th>
                                            <th>Access</th>
                                            <th>Uploaded</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($resources as $resource): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($resource['title']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($resource['description']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?php echo ucfirst(str_replace('_', ' ', $resource['category'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?php echo strtoupper($resource['file_type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $resource['access_level'] === 'all' ? 'success' : 'warning'; ?>">
                                                        <?php echo ucfirst($resource['access_level']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?php echo date('M d, Y', strtotime($resource['upload_date'])); ?></small>
                                                </td>
                                                <td>
                                                    <a href="../../uploads/resources/<?php echo $resource['file_path']; ?>" 
                                                       target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-download"></i> View
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>