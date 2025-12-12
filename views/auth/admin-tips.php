<?php
// healthconnect/views/auth/admin-tips.php
session_start();
require_once '../../app/config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php?error=required');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Handle tip actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        // Create new tip
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $category = $_POST['category'] ?? 'general';
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        
        $sql = "INSERT INTO hc_health_tips (tip_title, tip_content, category, is_featured, created_by_admin_id)
                VALUES (:title, :content, :category, :featured, :admin_id)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':title' => $title,
            ':content' => $content,
            ':category' => $category,
            ':featured' => $is_featured,
            ':admin_id' => $user_id
        ]);
        
        $_SESSION['message'] = 'Health tip created successfully!';
        
    } elseif ($action === 'update') {
        // Update existing tip
        $tip_id = $_POST['tip_id'];
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $category = $_POST['category'] ?? 'general';
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $sql = "UPDATE hc_health_tips 
                SET tip_title = :title,
                    tip_content = :content,
                    category = :category,
                    is_featured = :featured,
                    is_active = :active,
                    updated_at = NOW()
                WHERE tip_id = :tip_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':title' => $title,
            ':content' => $content,
            ':category' => $category,
            ':featured' => $is_featured,
            ':active' => $is_active,
            ':tip_id' => $tip_id
        ]);
        
        $_SESSION['message'] = 'Health tip updated successfully!';
        
    } elseif ($action === 'delete') {
        // Delete tip
        $tip_id = $_POST['tip_id'];
        $sql = "DELETE FROM hc_health_tips WHERE tip_id = :tip_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':tip_id' => $tip_id]);
        
        $_SESSION['message'] = 'Health tip deleted.';
    }
    
    header('Location: admin-tips.php');
    exit();
}

// Get all health tips
$sql = "SELECT ht.*, u.full_name as admin_name 
        FROM hc_health_tips ht
        LEFT JOIN hc_users u ON ht.created_by_admin_id = u.user_id
        ORDER BY ht.created_at DESC";
$stmt = $pdo->query($sql);
$tips = $stmt->fetchAll();

// Get tip statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END) as featured,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
    COUNT(DISTINCT category) as categories
    FROM hc_health_tips";
$stats_stmt = $pdo->query($stats_sql);
$tip_stats = $stats_stmt->fetch();

// Get unique categories
$cats_sql = "SELECT DISTINCT category FROM hc_health_tips WHERE category IS NOT NULL AND category != ''";
$cats_stmt = $pdo->query($cats_sql);
$categories = $cats_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Health Tips - HealthConnect Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tip-card {
            border-radius: 10px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .tip-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .tip-featured {
            border-left: 4px solid #ffc107;
        }
        .tip-inactive {
            opacity: 0.7;
            background: #f8f9fa;
        }
        .category-badge {
            display: inline-block;
            background: #e9ecef;
            color: #495057;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        .stats-card {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            color: white;
            margin-bottom: 15px;
        }
        .stats-total { background: linear-gradient(135deg, #6c757d, #495057); }
        .stats-featured { background: linear-gradient(135deg, #ffc107, #fd7e14); }
        .stats-active { background: linear-gradient(135deg, #198754, #146c43); }
        .stats-categories { background: linear-gradient(135deg, #0dcaf0, #0d6efd); }
        .editor-toolbar {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px 5px 0 0;
            border: 1px solid #dee2e6;
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-lightbulb text-warning"></i> Manage Health Tips</h2>
            <div>
                <a href="admin-dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createTipModal">
                    <i class="fas fa-plus"></i> New Tip
                </button>
            </div>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card stats-total">
                    <h3><?php echo $tip_stats['total']; ?></h3>
                    <p class="mb-0">Total Tips</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-featured">
                    <h3><?php echo $tip_stats['featured']; ?></h3>
                    <p class="mb-0">Featured</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-active">
                    <h3><?php echo $tip_stats['active']; ?></h3>
                    <p class="mb-0">Active</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-categories">
                    <h3><?php echo $tip_stats['categories']; ?></h3>
                    <p class="mb-0">Categories</p>
                </div>
            </div>
        </div>
        
        <!-- Tips Grid -->
        <div class="row">
            <?php foreach ($tips as $tip): ?>
                <div class="col-md-4">
                    <div class="card tip-card <?php echo $tip['is_featured'] ? 'tip-featured' : ''; ?> <?php echo !$tip['is_active'] ? 'tip-inactive' : ''; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h5 class="card-title"><?php echo htmlspecialchars($tip['tip_title']); ?></h5>
                                    <div class="mb-2">
                                        <span class="category-badge">
                                            <?php echo htmlspecialchars($tip['category']); ?>
                                        </span>
                                        <?php if ($tip['is_featured']): ?>
                                            <span class="badge bg-warning">
                                                <i class="fas fa-star"></i> Featured
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!$tip['is_active']): ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-eye-slash"></i> Inactive
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" 
                                            data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <button class="dropdown-item" data-bs-toggle="modal" 
                                                    data-bs-target="#editTipModal<?php echo $tip['tip_id']; ?>">
                                                <i class="fas fa-edit me-2"></i> Edit
                                            </button>
                                        </li>
                                        <li>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="tip_id" value="<?php echo $tip['tip_id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="dropdown-item text-danger" 
                                                        onclick="return confirm('Delete this tip?');">
                                                    <i class="fas fa-trash me-2"></i> Delete
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <p class="card-text">
                                <?php echo substr(strip_tags($tip['tip_content']), 0, 120); ?>...
                            </p>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <small class="text-muted">
                                    <i class="far fa-calendar"></i> 
                                    <?php echo date('M d, Y', strtotime($tip['created_at'])); ?>
                                </small>
                                <small class="text-muted">
                                    By: <?php echo htmlspecialchars($tip['admin_name'] ?? 'Admin'); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Edit Tip Modal -->
                <div class="modal fade" id="editTipModal<?php echo $tip['tip_id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Health Tip</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="tip_id" value="<?php echo $tip['tip_id']; ?>">
                                    <input type="hidden" name="action" value="update">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Title *</label>
                                        <input type="text" name="title" class="form-control" 
                                               value="<?php echo htmlspecialchars($tip['tip_title']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Category</label>
                                        <select name="category" class="form-select">
                                            <option value="general" <?php echo $tip['category'] === 'general' ? 'selected' : ''; ?>>General Health</option>
                                            <option value="nutrition" <?php echo $tip['category'] === 'nutrition' ? 'selected' : ''; ?>>Nutrition</option>
                                            <option value="exercise" <?php echo $tip['category'] === 'exercise' ? 'selected' : ''; ?>>Exercise</option>
                                            <option value="mental" <?php echo $tip['category'] === 'mental' ? 'selected' : ''; ?>>Mental Health</option>
                                            <option value="prevention" <?php echo $tip['category'] === 'prevention' ? 'selected' : ''; ?>>Disease Prevention</option>
                                            <option value="first_aid" <?php echo $tip['category'] === 'first_aid' ? 'selected' : ''; ?>>First Aid</option>
                                            <option value="childcare" <?php echo $tip['category'] === 'childcare' ? 'selected' : ''; ?>>Child Care</option>
                                            <option value="elderly" <?php echo $tip['category'] === 'elderly' ? 'selected' : ''; ?>>Elderly Care</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Content *</label>
                                        <div class="editor-toolbar">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                    onclick="formatText('bold', '<?php echo $tip['tip_id']; ?>')">
                                                <i class="fas fa-bold"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                    onclick="formatText('italic', '<?php echo $tip['tip_id']; ?>')">
                                                <i class="fas fa-italic"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                    onclick="formatText('list', '<?php echo $tip['tip_id']; ?>')">
                                                <i class="fas fa-list"></i>
                                            </button>
                                        </div>
                                        <textarea name="content" id="content<?php echo $tip['tip_id']; ?>" 
                                                  class="form-control" rows="8" required
                                                  placeholder="Write health tip content here... You can use basic HTML tags like &lt;b&gt;, &lt;i&gt;, &lt;ul&gt;, &lt;li&gt;"><?php echo htmlspecialchars($tip['tip_content']); ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" name="is_featured" 
                                                       id="featured<?php echo $tip['tip_id']; ?>" 
                                                       <?php echo $tip['is_featured'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="featured<?php echo $tip['tip_id']; ?>">
                                                    <i class="fas fa-star text-warning"></i> Mark as Featured
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" name="is_active" 
                                                       id="active<?php echo $tip['tip_id']; ?>" 
                                                       <?php echo $tip['is_active'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="active<?php echo $tip['tip_id']; ?>">
                                                    <i class="fas fa-eye"></i> Active (Visible to users)
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">Update Tip</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($tips)): ?>
            <div class="text-center py-5">
                <i class="fas fa-lightbulb fa-3x text-muted mb-3"></i>
                <h4>No health tips yet</h4>
                <p class="text-muted">Create your first health tip to help users</p>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createTipModal">
                    <i class="fas fa-plus me-2"></i> Create First Tip
                </button>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Create Tip Modal -->
    <div class="modal fade" id="createTipModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Health Tip</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="mb-3">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" class="form-control" 
                                   placeholder="e.g., 10 Tips for Better Sleep" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="general">General Health</option>
                                <option value="nutrition">Nutrition</option>
                                <option value="exercise">Exercise</option>
                                <option value="mental">Mental Health</option>
                                <option value="prevention">Disease Prevention</option>
                                <option value="first_aid">First Aid</option>
                                <option value="childcare">Child Care</option>
                                <option value="elderly">Elderly Care</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Content *</label>
                            <div class="editor-toolbar">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="formatText('bold', 'new')">
                                    <i class="fas fa-bold"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="formatText('italic', 'new')">
                                    <i class="fas fa-italic"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="formatText('list', 'new')">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>
                            <textarea name="content" id="contentnew" class="form-control" rows="8" required
                                      placeholder="Write health tip content here... You can use basic HTML tags like &lt;b&gt;, &lt;i&gt;, &lt;ul&gt;, &lt;li&gt;"></textarea>
                            <small class="text-muted">Tip: Use &lt;b&gt;text&lt;/b&gt; for bold, &lt;i&gt;text&lt;/i&gt; for italic</small>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_featured" id="featurednew">
                            <label class="form-check-label" for="featurednew">
                                <i class="fas fa-star text-warning"></i> Mark as Featured (Will appear on homepage)
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Create Tip</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Text formatting helper
        function formatText(type, tipId) {
            const textarea = document.getElementById('content' + tipId);
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const selectedText = textarea.value.substring(start, end);
            
            let formattedText = '';
            
            switch(type) {
                case 'bold':
                    formattedText = '<b>' + selectedText + '</b>';
                    break;
                case 'italic':
                    formattedText = '<i>' + selectedText + '</i>';
                    break;
                case 'list':
                    formattedText = '<ul>\n<li>' + selectedText + '</li>\n</ul>';
                    break;
            }
            
            textarea.value = textarea.value.substring(0, start) + 
                           formattedText + 
                           textarea.value.substring(end);
            
            // Restore cursor position
            textarea.focus();
            textarea.setSelectionRange(start + formattedText.length, start + formattedText.length);
        }
        
        // Character counter
        document.querySelectorAll('textarea[name="content"]').forEach(textarea => {
            const counter = document.createElement('div');
            counter.className = 'form-text text-end';
            counter.innerHTML = '<span class="char-count">0</span> characters';
            
            textarea.parentNode.appendChild(counter);
            
            textarea.addEventListener('input', function() {
                const charCount = this.value.length;
                counter.querySelector('.char-count').textContent = charCount;
                
                if (charCount < 100) {
                    counter.style.color = '#dc3545';
                } else if (charCount < 300) {
                    counter.style.color = '#ffc107';
                } else {
                    counter.style.color = '#198754';
                }
            });
            
            // Trigger initial count
            textarea.dispatchEvent(new Event('input'));
        });
    </script>
</body>
</html>