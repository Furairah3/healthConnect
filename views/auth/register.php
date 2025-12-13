<?php
// healthconnect/views/auth/register.php

// Start session first
session_start();

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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
        
        /* Terms Modal Styling */
        #termsModal .modal-header {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
        }

        #privacyModal .modal-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }

        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .modal-body {
            max-height: 60vh;
            overflow-y: auto;
            padding: 25px;
        }

        .modal-body h6 {
            font-weight: 600;
            padding-bottom: 8px;
            border-bottom: 2px solid currentColor;
        }

        .modal-body ul {
            padding-left: 20px;
        }

        .modal-body li {
            margin-bottom: 8px;
            line-height: 1.5;
        }

        /* Terms checkbox styling */
        .form-check-input:checked {
            background-color: #4361ee;
            border-color: #4361ee;
        }

        .form-check-input:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }

        /* Links in terms checkbox */
        .form-check-label a {
            text-decoration: none;
            transition: all 0.3s;
        }

        .form-check-label a:hover {
            text-decoration: underline;
            transform: translateY(-1px);
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
                                        <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                        <label class="form-check-label" for="terms">
                                            I agree to the 
                                            <a href="#" class="text-primary fw-bold" data-bs-toggle="modal" data-bs-target="#termsModal">
                                                <i class="fas fa-file-contract me-1"></i>Terms of Service
                                            </a> 
                                            and 
                                            <a href="#" class="text-success fw-bold" data-bs-toggle="modal" data-bs-target="#privacyModal">
                                                <i class="fas fa-shield-alt me-1"></i>Privacy Policy
                                            </a> *
                                        </label>
                                        <div class="invalid-feedback">
                                            <i class="fas fa-exclamation-circle me-1"></i> You must agree to the terms and conditions
                                        </div>
                                        <div class="form-text">
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle me-1"></i> By checking this box, you acknowledge that you have read and understood our terms.
                                            </small>
                                        </div>
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

    <!-- Terms of Service Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="termsModalLabel">
                        <i class="fas fa-file-contract me-2"></i> HealthConnect Terms of Service
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <p class="text-muted"><small>Last Updated: <?php echo date('F j, Y'); ?></small></p>
                    </div>
                    
                    <h6 class="text-primary mb-3">1. Acceptance of Terms</h6>
                    <p>By accessing and using HealthConnect, you accept and agree to be bound by the terms and provision of this agreement.</p>
                    
                    <h6 class="text-primary mb-3">2. Medical Disclaimer</h6>
                    <p><strong>Important:</strong> HealthConnect is a platform connecting patients with healthcare volunteers and professionals. This service is <strong>NOT a substitute for professional medical advice, diagnosis, or treatment</strong>.</p>
                    <ul>
                        <li>Always seek the advice of your physician or other qualified health provider with any questions you may have regarding a medical condition</li>
                        <li>Never disregard professional medical advice or delay in seeking it because of something you have read on this platform</li>
                        <li>In case of emergency, call your local emergency services immediately</li>
                    </ul>
                    
                    <h6 class="text-primary mb-3">3. User Responsibilities</h6>
                    <p>As a user, you agree to:</p>
                    <ul>
                        <li>Provide accurate and complete information</li>
                        <li>Maintain the confidentiality of your account</li>
                        <li>Use the service only for lawful purposes</li>
                        <li>Not share medical advice that you are not qualified to give</li>
                        <li>Report any suspicious or inappropriate content</li>
                    </ul>
                    
                    <h6 class="text-primary mb-3">4. Volunteer/Professional Guidelines</h6>
                    <p>Healthcare volunteers and professionals must:</p>
                    <ul>
                        <li>Provide advice within their scope of practice</li>
                        <li>Clearly state their qualifications and limitations</li>
                        <li>Maintain patient confidentiality</li>
                        <li>Not provide diagnosis without proper examination</li>
                        <li>Refer to appropriate medical services when needed</li>
                    </ul>
                    
                    <h6 class="text-primary mb-3">5. Limitation of Liability</h6>
                    <p>HealthConnect and its affiliates shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use of or inability to use the service.</p>
                    
                    <h6 class="text-primary mb-3">6. Modifications to Terms</h6>
                    <p>We reserve the right to modify these terms at any time. Continued use of the service after changes constitutes acceptance of the new terms.</p>
                    
                    <div class="alert alert-warning mt-4">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Emergency Notice:</strong> This platform is for general health advice only. For medical emergencies, please contact your local emergency services immediately.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                        <i class="fas fa-check me-1"></i> I Understand
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Privacy Policy Modal -->
    <div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="privacyModalLabel">
                        <i class="fas fa-shield-alt me-2"></i> HealthConnect Privacy Policy
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <p class="text-muted"><small>Last Updated: <?php echo date('F j, Y'); ?></small></p>
                    </div>
                    
                    <h6 class="text-success mb-3">1. Information We Collect</h6>
                    <p>We collect information to provide better services to our users:</p>
                    <ul>
                        <li><strong>Personal Information:</strong> Name, email address, contact information</li>
                        <li><strong>Health Information:</strong> Medical requests, symptoms, health concerns (shared voluntarily)</li>
                        <li><strong>Professional Information:</strong> Qualifications, certifications (for healthcare providers)</li>
                        <li><strong>Usage Data:</strong> How you interact with our platform</li>
                    </ul>
                    
                    <h6 class="text-success mb-3">2. How We Use Your Information</h6>
                    <p>Your information is used to:</p>
                    <ul>
                        <li>Connect patients with appropriate healthcare volunteers</li>
                        <li>Improve our services and user experience</li>
                        <li>Ensure platform security and prevent fraud</li>
                        <li>Communicate important updates about our services</li>
                        <li>Comply with legal obligations</li>
                    </ul>
                    
                    <h6 class="text-success mb-3">3. Medical Data Protection</h6>
                    <p>We take extra precautions with health information:</p>
                    <ul>
                        <li>Health data is encrypted in transit and at rest</li>
                        <li>Access is restricted to authorized personnel only</li>
                        <li>We follow healthcare data protection standards</li>
                        <li>You can request deletion of your health data</li>
                    </ul>
                    
                    <h6 class="text-success mb-3">4. Data Sharing</h6>
                    <p>We do NOT sell your personal or health information. We may share data:</p>
                    <ul>
                        <li>With healthcare volunteers responding to your requests</li>
                        <li>When required by law or legal process</li>
                        <li>To protect the rights and safety of our users</li>
                        <li>With service providers under strict confidentiality agreements</li>
                    </ul>
                    
                    <h6 class="text-success mb-3">5. Your Rights</h6>
                    <p>You have the right to:</p>
                    <ul>
                        <li>Access your personal information</li>
                        <li>Correct inaccurate data</li>
                        <li>Request deletion of your data</li>
                        <li>Opt-out of non-essential communications</li>
                        <li>Download your data in a portable format</li>
                    </ul>
                    
                    <h6 class="text-success mb-3">6. Security Measures</h6>
                    <p>We implement security measures including:</p>
                    <ul>
                        <li>Encryption of sensitive data</li>
                        <li>Regular security audits</li>
                        <li>Access controls and authentication</li>
                        <li>Secure data backup procedures</li>
                    </ul>
                    
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Contact Us:</strong> For privacy-related questions, contact our Data Protection Officer at privacy@healthconnect.example.com
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                        <i class="fas fa-check me-1"></i> I Understand
                    </button>
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