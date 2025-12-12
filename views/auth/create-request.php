<?php
// healthconnect/views/auth/create-request.php
session_start();
require_once '../../app/config/database.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    header('Location: login.php?error=required');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = cleanInput($_POST['title'] ?? '');
    $description = cleanInput($_POST['description'] ?? '');
    $location = cleanInput($_POST['location'] ?? '');
    
    if (empty($title) || empty($description)) {
        $error = 'Title and description are required';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Insert health request
            $sql = "INSERT INTO hc_medical_requests (patient_id, request_title, request_description, patient_location) 
                    VALUES (:patient_id, :title, :description, :location)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':patient_id' => $user_id,
                ':title' => $title,
                ':description' => $description,
                ':location' => $location
            ]);
            
            $request_id = $pdo->lastInsertId();
            
            // Log activity
            $sql = "INSERT INTO hc_activity_logs (user_id, activity_type, activity_description, ip_address, user_agent) 
                    VALUES (:user_id, 'request_create', :description_text, :ip, :agent)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $user_id,
                ':description_text' => 'Created health request: ' . $title,
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            $pdo->commit();
            
            $success = 'Health request submitted successfully! A volunteer or doctor will respond soon.';
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to submit request: ' . $e->getMessage();
            error_log("Request creation error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Health Request - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-header {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 30px;
        }
        
        .form-body {
            background: white;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
        }
        
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }
        
        .char-count {
            font-size: 12px;
            color: #6c757d;
        }
        
        .tips-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .symptom-suggestions {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-top: 10px;
            display: none;
        }
        
        .symptom-tag {
            display: inline-block;
            background: #e3e9ff;
            color: #4361ee;
            padding: 5px 12px;
            border-radius: 20px;
            margin: 3px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .symptom-tag:hover {
            background: #4361ee;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="../../index.php">
                <i class="fas fa-heartbeat me-2"></i>HealthConnect
            </a>
            <div class="d-flex align-items-center">
                <span class="me-3">Welcome, <?php echo htmlspecialchars($user_name); ?></span>
                <a href="patient-dashboard.php" class="btn btn-outline-primary btn-sm me-2">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="form-container">
            <!-- Form Header -->
            <div class="form-header text-center">
                <div class="mb-4">
                    <i class="fas fa-file-medical fa-4x text-white mb-3"></i>
                    <h2 class="fw-bold mb-3">New Health Request</h2>
                    <p class="lead mb-0">Describe your health concern in detail for better assistance</p>
                </div>
            </div>
            
            <!-- Form Body -->
            <div class="form-body">
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle fa-2x me-3"></i>
                            <div>
                                <h5 class="mb-1">Request Submitted Successfully!</h5>
                                <p class="mb-0"><?php echo $success; ?></p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <div class="mt-3">
                            <a href="patient-dashboard.php" class="btn btn-success me-2">
                                <i class="fas fa-home me-1"></i> Go to Dashboard
                            </a>
                            <a href="create-request.php" class="btn btn-outline-primary">
                                <i class="fas fa-plus me-1"></i> Submit Another Request
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <!-- Title -->
                    <div class="mb-4">
                        <label for="title" class="form-label fw-bold">
                            <i class="fas fa-heading me-2 text-primary"></i>Request Title *
                        </label>
                        <input type="text" class="form-control" id="title" name="title" 
                               placeholder="e.g., Persistent headache and fever for 3 days" required
                               oninput="updateCharCount('title', 'titleCount', 100)">
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted">Brief summary of your health concern</small>
                            <small class="char-count"><span id="titleCount">0</span>/100 characters</small>
                        </div>
                    </div>
                    
                    <!-- Description -->
                    <div class="mb-4">
                        <label for="description" class="form-label fw-bold">
                            <i class="fas fa-align-left me-2 text-primary"></i>Detailed Description *
                        </label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="6" placeholder="Please describe your symptoms in detail..." required
                                  oninput="updateCharCount('description', 'descCount', 2000)"></textarea>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted">Include: Symptoms, duration, severity, medications taken</small>
                            <small class="char-count"><span id="descCount">0</span>/2000 characters</small>
                        </div>
                        
                        <!-- Symptom Suggestions -->
                        <div class="mt-2">
                            <small class="text-muted">Common symptoms (click to add):</small>
                            <div class="symptom-suggestions" id="symptomSuggestions">
                                <span class="symptom-tag" onclick="addSymptom('Fever')">Fever</span>
                                <span class="symptom-tag" onclick="addSymptom('Headache')">Headache</span>
                                <span class="symptom-tag" onclick="addSymptom('Cough')">Cough</span>
                                <span class="symptom-tag" onclick="addSymptom('Fatigue')">Fatigue</span>
                                <span class="symptom-tag" onclick="addSymptom('Nausea')">Nausea</span>
                                <span class="symptom-tag" onclick="addSymptom('Pain')">Pain</span>
                                <span class="symptom-tag" onclick="addSymptom('Dizziness')">Dizziness</span>
                                <span class="symptom-tag" onclick="addSymptom('Shortness of breath')">Shortness of breath</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Location -->
                    <div class="mb-4">
                        <label for="location" class="form-label fw-bold">
                            <i class="fas fa-map-marker-alt me-2 text-primary"></i>Location (Optional)
                        </label>
                        <input type="text" class="form-control" id="location" name="location" 
                               placeholder="e.g., Rural village name, District, State">
                        <small class="text-muted">Your location helps volunteers provide relevant advice</small>
                    </div>
                    
                    <!-- Emergency Warning -->
                    <div class="alert alert-warning">
                        <div class="d-flex">
                            <i class="fas fa-exclamation-triangle fa-2x me-3 text-warning"></i>
                            <div>
                                <h6 class="fw-bold mb-2">Important Notice</h6>
                                <p class="mb-0">
                                    <strong>This is for general medical advice only.</strong><br>
                                    In case of emergency, please contact local emergency services immediately.
                                    For life-threatening situations, call your local emergency number.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="d-grid gap-3">
                        <button type="submit" class="btn btn-primary btn-lg py-3">
                            <i class="fas fa-paper-plane me-2"></i> Submit Health Request
                        </button>
                        <a href="patient-dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Cancel
                        </a>
                    </div>
                </form>
                
                <!-- Tips Card -->
                <div class="tips-card">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-lightbulb text-warning me-2"></i> Tips for Better Responses
                    </h6>
                    <ul class="mb-0">
                        <li class="mb-2">Describe your symptoms clearly and in detail</li>
                        <li class="mb-2">Mention how long you've had the symptoms</li>
                        <li class="mb-2">Include any medications you're currently taking</li>
                        <li class="mb-2">Note any allergies you have</li>
                        <li class="mb-2">Mention if symptoms are getting better or worse</li>
                        <li>Include relevant medical history if applicable</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Character counter
        function updateCharCount(inputId, countId, maxLength) {
            const input = document.getElementById(inputId);
            const count = document.getElementById(countId);
            const length = input.value.length;
            
            count.textContent = length;
            
            if (length > maxLength * 0.8) {
                count.style.color = '#dc3545';
            } else if (length > maxLength * 0.6) {
                count.style.color = '#ffc107';
            } else {
                count.style.color = '#6c757d';
            }
        }
        
        // Show symptom suggestions when focusing on description
        document.getElementById('description').addEventListener('focus', function() {
            document.getElementById('symptomSuggestions').style.display = 'block';
        });
        
        // Add symptom to description
        function addSymptom(symptom) {
            const description = document.getElementById('description');
            const currentText = description.value;
            
            if (currentText.length > 0 && !currentText.endsWith(' ')) {
                description.value += ', ';
            }
            
            description.value += symptom + ', ';
            description.focus();
        }
        
        // Initialize character counts
        document.addEventListener('DOMContentLoaded', function() {
            updateCharCount('title', 'titleCount', 100);
            updateCharCount('description', 'descCount', 2000);
        });
    </script>
</body>
</html>