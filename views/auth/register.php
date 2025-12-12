<?php
// healthconnect/views/auth/register.php

// // Prevent direct access
// if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
//     header("Location: ../../index.php");
//     exit();
// }

require_once '../../app/config/database.php';

// Get role from URL if provided
$selected_role = isset($_GET['role']) ? $_GET['role'] : '';
$valid_roles = ['patient', 'volunteer', 'doctor'];
if (!in_array($selected_role, $valid_roles)) {
    $selected_role = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #f5f7ff 0%, #e3e9ff 100%);
            padding: 40px 0;
        }
        .register-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .register-header {
            background: var(--primary-color);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .role-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .role-option {
            flex: 1;
            text-align: center;
            padding: 15px;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .role-option:hover {
            border-color: var(--primary-color);
            background: rgba(67, 97, 238, 0.05);
        }
        .role-option.selected {
            border-color: var(--primary-color);
            background: rgba(67, 97, 238, 0.1);
        }
        .form-label {
            font-weight: 500;
            margin-bottom: 5px;
        }
        .form-control, .form-select {
            border-radius: 8px;
            padding: 12px 15px;
            border: 2px solid #dee2e6;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            background: #e9ecef;
        }
        .password-strength-bar {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s;
        }
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .upload-area:hover {
            border-color: var(--primary-color);
            background: rgba(67, 97, 238, 0.05);
        }
        .upload-area.dragover {
            border-color: var(--primary-color);
            background: rgba(67, 97, 238, 0.1);
        }
        .file-info {
            margin-top: 10px;
            font-size: 14px;
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
            <a href="../../index.php" class="btn btn-outline-primary">
                <i class="fas fa-home me-2"></i>Back to Home
            </a>
        </div>
    </nav>

    <div class="register-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="register-card">
                        <div class="register-header">
                            <h2 class="fw-bold mb-3">Create Your HealthConnect Account</h2>
                            <p class="mb-0">Join our community to access or provide healthcare support</p>
                        </div>
                        
                        <div class="card-body p-4 p-md-5">
                            <!-- Role Selection -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">I am registering as:</label>
                                <div class="role-selector">
                                    <div class="role-option <?php echo $selected_role == 'patient' ? 'selected' : ''; ?>" 
                                         onclick="selectRole('patient')">
                                        <i class="fas fa-user-injured fa-2x mb-2"></i>
                                        <h6 class="fw-bold">Patient</h6>
                                        <small class="text-muted">Need medical advice</small>
                                    </div>
                                    <div class="role-option <?php echo $selected_role == 'volunteer' ? 'selected' : ''; ?>" 
                                         onclick="selectRole('volunteer')">
                                        <i class="fas fa-hands-helping fa-2x mb-2"></i>
                                        <h6 class="fw-bold">Volunteer</h6>
                                        <small class="text-muted">Want to help others</small>
                                    </div>
                                    <div class="role-option <?php echo $selected_role == 'doctor' ? 'selected' : ''; ?>" 
                                         onclick="selectRole('doctor')">
                                        <i class="fas fa-user-md fa-2x mb-2"></i>
                                        <h6 class="fw-bold">Medical Professional</h6>
                                        <small class="text-muted">Provide medical advice</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Registration Form -->
                            <form id="registrationForm" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" id="user_role" name="user_role" value="<?php echo htmlspecialchars($selected_role); ?>">

                                <!-- Doctor Verification (Hidden by default) -->
                                <div id="doctorVerificationSection" class="mb-4" style="display: <?php echo $selected_role == 'doctor' ? 'block' : 'none'; ?>;">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Important for Doctors:</strong> Please upload your medical license or certificate for verification. 
                                        Your account will be reviewed by our admin team within 24-48 hours.
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Medical License/Certificate *</label>
                                        <div class="upload-area" onclick="document.getElementById('certificate_file').click()" 
                                             ondragover="handleDragOver(event)" 
                                             ondrop="handleFileDrop(event)" 
                                             ondragleave="handleDragLeave(event)">
                                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                            <h5>Upload Verification Document</h5>
                                            <p class="text-muted mb-2">Drag & drop your file here or click to browse</p>
                                            <small class="text-muted">Accepted: PDF, JPG, PNG (Max 5MB)</small>
                                            <div id="fileInfo" class="file-info"></div>
                                        </div>
                                        <input type="file" id="certificate_file" name="certificate_file" 
                                               class="d-none" accept=".pdf,.jpg,.jpeg,.png">
                                        <small class="text-muted mt-2 d-block">
                                            Your document will be securely stored and only visible to admin staff for verification purposes.
                                        </small>
                                    </div>
                                </div>

                                <!-- Personal Information -->
                                <div class="row mb-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="full_name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                                        <div class="invalid-feedback">Please enter your full name</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email_address" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" id="email_address" name="email_address" required>
                                        <div class="invalid-feedback">Please enter a valid email address</div>
                                        <small class="text-muted" id="emailStatus"></small>
                                    </div>
                                </div>

                                <!-- Password -->
                                <div class="row mb-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="password" class="form-label">Password *</label>
                                        <input type="password" class="form-control" id="password" name="password" required 
                                               pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$">
                                        <div class="password-strength">
                                            <div id="passwordStrengthBar" class="password-strength-bar"></div>
                                        </div>
                                        <small class="text-muted">
                                            Minimum 8 characters with uppercase, lowercase, number, and special character
                                        </small>
                                        <div class="invalid-feedback" id="passwordFeedback"></div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="confirm_password" class="form-label">Confirm Password *</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <div class="invalid-feedback">Passwords do not match</div>
                                    </div>
                                </div>

                                <!-- Terms and Conditions -->
                                <div class="mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="terms" required>
                                        <label class="form-check-label" for="terms">
                                            I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms of Service</a> 
                                            and <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a> *
                                        </label>
                                        <div class="invalid-feedback">You must agree to the terms and conditions</div>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                        <span id="submitText">Create Account</span>
                                        <span id="loadingSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                                    </button>
                                </div>
                            </form>

                            <!-- Login Link -->
                            <div class="text-center mt-4">
                                <p>Already have an account? <a href="login.php" class="text-primary fw-bold">Login here</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terms of Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Acceptance of Terms</h6>
                    <p>By accessing HealthConnect, you agree to these terms...</p>
                    <!-- Add more terms content -->
                </div>
            </div>
        </div>
    </div>

    <!-- Privacy Modal -->
    <div class="modal fade" id="privacyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Privacy Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>Data Collection and Use</h6>
                    <p>We collect your information to provide healthcare services...</p>
                    <!-- Add more privacy content -->
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/validation.js"></script>
    <script>
        // Role selection
        function selectRole(role) {
            document.getElementById('user_role').value = role;
            
            // Update UI
            document.querySelectorAll('.role-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            
            // Show/hide doctor verification
            const doctorSection = document.getElementById('doctorVerificationSection');
            doctorSection.style.display = role === 'doctor' ? 'block' : 'none';
        }
        
        // File upload handling
        const fileInput = document.getElementById('certificate_file');
        const fileInfo = document.getElementById('fileInfo');
        const uploadArea = document.querySelector('.upload-area');
        
        fileInput.addEventListener('change', function(e) {
            if (this.files.length > 0) {
                const file = this.files[0];
                displayFileInfo(file);
            }
        });
        
        function handleDragOver(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        }
        
        function handleDragLeave(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        }
        
        function handleFileDrop(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                displayFileInfo(e.dataTransfer.files[0]);
            }
        }
        
        function displayFileInfo(file) {
            if (file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB');
                fileInput.value = '';
                fileInfo.innerHTML = '';
                return;
            }
            
            const validTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
            if (!validTypes.includes(file.type)) {
                alert('Please upload PDF, JPG, or PNG files only');
                fileInput.value = '';
                fileInfo.innerHTML = '';
                return;
            }
            
            fileInfo.innerHTML = `
                <div class="alert alert-success p-2">
                    <i class="fas fa-file-alt me-2"></i>
                    <strong>${file.name}</strong> (${(file.size / 1024 / 1024).toFixed(2)} MB)
                </div>
            `;
        }
        
        // Initialize selected role from URL
        <?php if ($selected_role): ?>
            document.addEventListener('DOMContentLoaded', function() {
                selectRole('<?php echo $selected_role; ?>');
            });
        <?php endif; ?>
    </script>
</body>
</html>