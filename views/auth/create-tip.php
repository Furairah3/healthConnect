<?php
// healthconnect/views/auth/create-tip.php
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

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $category = $_POST['category'] ?? 'general';
    
    // Validate input
    if (empty($title) || empty($content)) {
        $message = 'Please fill in all required fields';
        $message_type = 'danger';
    } else {
        try {
            // Insert health tip
            $sql = "INSERT INTO hc_health_tips (doctor_user_id, tip_title, tip_content, category, is_published, created_by_admin_id, tip_date) 
                    VALUES (:doctor_id, :title, :content, :category, 1, NULL, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':doctor_id' => $user_id,
                ':title' => $title,
                ':content' => $content,
                ':category' => $category
            ]);
            
            $tip_id = $pdo->lastInsertId();
            
            // Log activity
            $log_sql = "INSERT INTO hc_activity_logs (user_id, activity_type, activity_description) 
                        VALUES (:user_id, 'tip_created', :description)";
            $log_stmt = $pdo->prepare($log_sql);
            $log_stmt->execute([
                ':user_id' => $user_id,
                ':description' => 'Created health tip: ' . $title
            ]);
            
            $message = 'Health tip created successfully!';
            $message_type = 'success';
            
            // Clear form on success
            $_POST = [];
            
        } catch (Exception $e) {
            $message = 'Error creating tip: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Get categories
$categories = [
    'general' => 'General Health',
    'nutrition' => 'Nutrition',
    'exercise' => 'Exercise & Fitness',
    'mental' => 'Mental Health',
    'chronic' => 'Chronic Conditions',
    'prevention' => 'Prevention',
    'children' => 'Children Health',
    'seniors' => 'Senior Health'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Health Tip - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --doctor-primary: #0d6efd;
            --doctor-secondary: #052c65;
            --doctor-accent: #20c997;
            --doctor-light: #e3f2fd;
        }
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e3f2fd 100%);
            min-height: 100vh;
        }
        
        .create-tip-container {
            max-width: 900px;
            margin: 30px auto;
            opacity: 0;
            animation: fadeIn 0.8s ease forwards;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .tip-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            transition: all 0.5s ease;
        }
        
        .tip-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--doctor-primary) 0%, var(--doctor-secondary) 100%);
            color: white;
            border-radius: 20px 20px 0 0 !important;
            padding: 25px 30px;
            position: relative;
            overflow: hidden;
        }
        
        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,160L48,165.3C96,171,192,181,288,181.3C384,181,480,171,576,165.3C672,160,768,160,864,170.7C960,181,1056,203,1152,202.7C1248,203,1344,181,1392,170.7L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            animation: float 20s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .form-label {
            font-weight: 600;
            color: var(--doctor-secondary);
            margin-bottom: 8px;
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
        
        .required::after {
            content: ' *';
            color: #dc3545;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--doctor-primary), var(--doctor-secondary));
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(13, 110, 253, 0.3);
        }
        
        .btn-submit::after {
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
        
        .btn-submit:focus:not(:active)::after {
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
        
        /* Quill Editor Styles */
        #editor {
            height: 300px;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        #editor:hover {
            border-color: var(--doctor-primary);
        }
        
        .ql-toolbar {
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            border-color: #e9ecef !important;
        }
        
        .ql-container {
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
            border-color: #e9ecef !important;
            font-family: 'Poppins', sans-serif;
        }
        
        .preview-box {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 20px;
            min-height: 150px;
            transition: all 0.3s;
        }
        
        .preview-box:hover {
            border-color: var(--doctor-primary);
        }
        
        .category-badge {
            background: var(--doctor-light);
            color: var(--doctor-primary);
            border: 2px solid var(--doctor-primary);
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .category-badge:hover {
            background: var(--doctor-primary);
            color: white;
            transform: translateY(-2px);
        }
        
        .category-badge.active {
            background: var(--doctor-primary);
            color: white;
        }
        
        .char-count {
            font-size: 12px;
            color: #6c757d;
        }
        
        .floating-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .particle {
            position: absolute;
            background: rgba(13, 110, 253, 0.05);
            border-radius: 50%;
            animation: floatParticle 20s infinite linear;
        }
        
        @keyframes floatParticle {
            0% {
                transform: translateY(0) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) translateX(100px);
                opacity: 0;
            }
        }
        
        .tips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .tip-example {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-left: 4px solid var(--doctor-accent);
            transition: all 0.3s;
        }
        
        .tip-example:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .tip-example h6 {
            color: var(--doctor-primary);
            font-weight: 600;
        }
        
        .tip-example small {
            color: var(--doctor-secondary);
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .create-tip-container {
                margin: 15px;
            }
            
            .card-header {
                padding: 20px;
            }
            
            .tips-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Floating Background Particles -->
    <div class="floating-particles">
        <?php for ($i = 0; $i < 10; $i++): ?>
            <div class="particle" 
                 style="width: <?php echo rand(2, 6); ?>px; 
                        height: <?php echo rand(2, 6); ?>px;
                        left: <?php echo rand(0, 100); ?>%;
                        animation-delay: <?php echo rand(0, 20); ?>s;
                        animation-duration: <?php echo rand(15, 30); ?>s;"></div>
        <?php endfor; ?>
    </div>
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
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
                        <a class="nav-link active" href="create-tip.php">
                            <i class="fas fa-lightbulb me-1"></i> Health Tips
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

    <!-- Main Content -->
    <div class="container create-tip-container">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> me-2 fa-lg"></i>
                    <div><?php echo htmlspecialchars($message); ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="card tip-card">
            <div class="card-header position-relative">
                <h4 class="mb-0 fw-bold">
                    <i class="fas fa-lightbulb me-2"></i> Create Health Tip
                </h4>
                <p class="mb-0 opacity-75 mt-2">Share your medical knowledge with the community</p>
            </div>
            <div class="card-body p-4">
                <form method="POST" id="tipForm">
                    <!-- Title -->
                    <div class="mb-4">
                        <label for="title" class="form-label required">Tip Title</label>
                        <input type="text" 
                               class="form-control" 
                               id="title" 
                               name="title" 
                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                               placeholder="Enter a clear and descriptive title"
                               maxlength="200"
                               required>
                        <div class="char-count mt-1 text-end">
                            <span id="titleCount">0</span>/200 characters
                        </div>
                    </div>
                    
                    <!-- Category -->
                    <div class="mb-4">
                        <label class="form-label required">Category</label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($categories as $value => $label): ?>
                                <label class="category-badge <?php echo ($_POST['category'] ?? '') === $value ? 'active' : ''; ?>">
                                    <input type="radio" 
                                           name="category" 
                                           value="<?php echo $value; ?>" 
                                           <?php echo ($_POST['category'] ?? '') === $value ? 'checked' : ''; ?>
                                           required
                                           style="display: none;">
                                    <?php echo $label; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Content Editor -->
                    <div class="mb-4">
                        <label class="form-label required">Tip Content</label>
                        <div id="editor"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></div>
                        <textarea name="content" id="content" style="display: none;" required></textarea>
                        <div class="char-count mt-1 text-end">
                            <span id="contentCount">0</span> characters
                        </div>
                    </div>
                    
                    <!-- Preview Button -->
                    <div class="mb-4">
                        <button type="button" class="btn btn-outline-primary mb-3" onclick="previewTip()">
                            <i class="fas fa-eye me-2"></i> Preview Tip
                        </button>
                        <div id="preview" class="preview-box" style="display: none;">
                            <h5 id="previewTitle"></h5>
                            <small class="text-muted d-block mb-3" id="previewCategory"></small>
                            <div id="previewContent"></div>
                        </div>
                    </div>
                    
                    <!-- Tips Grid -->
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3 text-primary">
                            <i class="fas fa-lightbulb me-2"></i> Example Tips
                        </h6>
                        <div class="tips-grid">
                            <div class="tip-example">
                                <h6>Stay Hydrated Daily</h6>
                                <small class="d-block mb-2 text-muted">General Health</small>
                                <p class="small mb-0">Drink at least 8 glasses of water daily to maintain proper hydration and support overall health.</p>
                            </div>
                            <div class="tip-example">
                                <h6>Regular Exercise Benefits</h6>
                                <small class="d-block mb-2 text-muted">Exercise & Fitness</small>
                                <p class="small mb-0">30 minutes of moderate exercise daily can improve cardiovascular health and boost mood.</p>
                            </div>
                            <div class="tip-example">
                                <h6>Balanced Nutrition</h6>
                                <small class="d-block mb-2 text-muted">Nutrition</small>
                                <p class="small mb-0">Include a variety of fruits, vegetables, whole grains, and lean proteins in your diet.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Buttons -->
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="doctor-dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                        </a>
                        <div class="d-flex gap-2">
                            <button type="reset" class="btn btn-outline-danger">
                                <i class="fas fa-times me-2"></i> Clear
                            </button>
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-paper-plane me-2"></i> Publish Tip
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
        // Initialize Quill editor
        const quill = new Quill('#editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['link', 'clean']
                ]
            },
            placeholder: 'Write your health tip here...'
        });
        
        // Update character counts
        const titleInput = document.getElementById('title');
        const titleCount = document.getElementById('titleCount');
        const contentCount = document.getElementById('contentCount');
        
        titleInput.addEventListener('input', function() {
            titleCount.textContent = this.value.length;
        });
        
        quill.on('text-change', function() {
            const text = quill.getText();
            contentCount.textContent = text.length;
            document.getElementById('content').value = quill.root.innerHTML;
        });
        
        // Initialize counts
        titleCount.textContent = titleInput.value.length;
        contentCount.textContent = quill.getText().length;
        document.getElementById('content').value = quill.root.innerHTML;
        
        // Category badge selection
        document.querySelectorAll('.category-badge').forEach(badge => {
            badge.addEventListener('click', function() {
                document.querySelectorAll('.category-badge').forEach(b => {
                    b.classList.remove('active');
                });
                this.classList.add('active');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });
        
        // Preview function
        function previewTip() {
            const title = document.getElementById('title').value;
            const category = document.querySelector('input[name="category"]:checked');
            const content = quill.root.innerHTML;
            
            if (!title || !category || !content) {
                alert('Please fill in all fields before previewing');
                return;
            }
            
            const categoryLabels = {
                'general': 'General Health',
                'nutrition': 'Nutrition',
                'exercise': 'Exercise & Fitness',
                'mental': 'Mental Health',
                'chronic': 'Chronic Conditions',
                'prevention': 'Prevention',
                'children': 'Children Health',
                'seniors': 'Senior Health'
            };
            
            document.getElementById('previewTitle').textContent = title;
            document.getElementById('previewCategory').textContent = categoryLabels[category.value] || category.value;
            document.getElementById('previewContent').innerHTML = content;
            document.getElementById('preview').style.display = 'block';
        }
        
        // Form submission
        document.getElementById('tipForm').addEventListener('submit', function(e) {
            // Ensure content is set
            document.getElementById('content').value = quill.root.innerHTML;
            
            // Validate
            const title = document.getElementById('title').value.trim();
            const category = document.querySelector('input[name="category"]:checked');
            const content = quill.getText().trim();
            
            if (!title || !category || !content) {
                e.preventDefault();
                alert('Please fill in all required fields');
                return;
            }
            
            if (title.length < 10) {
                e.preventDefault();
                alert('Title should be at least 10 characters long');
                return;
            }
            
            if (content.length < 50) {
                e.preventDefault();
                alert('Tip content should be at least 50 characters long');
                return;
            }
        });
        
        // Add animations
        document.addEventListener('DOMContentLoaded', function() {
            // Fade in content
            const container = document.querySelector('.create-tip-container');
            setTimeout(() => {
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
            
            // Add ripple effect to submit button
            const submitBtn = document.querySelector('.btn-submit');
            if (submitBtn) {
                submitBtn.addEventListener('click', function(e) {
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
            }
        });
    </script>
</body>
</html>
