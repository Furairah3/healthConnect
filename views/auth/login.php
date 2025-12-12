<?php
// healthconnect/views/auth/login.php
require_once '../../app/config/database.php';

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to appropriate dashboard
    $role = $_SESSION['user_role'];
    if ($role === 'doctor' && !$_SESSION['is_approved']) {
        header('Location: pending-approval.php');
    } else {
        header('Location: ' . $role . '-dashboard.php');
    }
    exit();
}

// Handle login errors/success
$error = isset($_GET['error']) ? $_GET['error'] : '';
$success = isset($_GET['success']) ? $_GET['success'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HealthConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #f5f7ff 0%, #e3e9ff 100%);
            padding: 40px 0;
        }
        .login-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 450px;
            margin: 0 auto;
        }
        .login-header {
            background: var(--primary-color);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-logo {
            font-size: 3rem;
            margin-bottom: 20px;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 2px solid #dee2e6;
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
        }
        .forgot-link {
            text-decoration: none;
            font-size: 14px;
        }
        .remember-me {
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

    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    <div class="login-card">
                        <div class="login-header">
                            <div class="login-logo">
                                <i class="fas fa-heartbeat"></i>
                            </div>
                            <h2 class="fw-bold mb-3">Welcome Back</h2>
                            <p class="mb-0">Sign in to access your HealthConnect account</p>
                        </div>
                        
                        <div class="card-body p-4 p-md-5">
                            <?php if (isset($_GET['error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <?php 
                                        $errors = [
                                            'invalid' => 'Invalid email or password',
                                            'inactive' => 'Account is not active',
                                            'not_approved' => 'Your account is pending admin approval',
                                            'timeout' => 'Your session has expired. Please login again.',
                                            'required' => 'Please login to access that page'
                                        ];
                                        echo $errors[$_GET['error']] ?? 'Login failed. Please try again.';
                                    ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($_GET['success'])): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <?php 
                                        $success_msgs = [
                                            'registered' => 'Registration successful! Please login.',
                                            'logout' => 'You have been successfully logged out.'
                                        ];
                                        echo $success_msgs[$_GET['success']] ?? 'Success!';
                                    ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <form id="loginForm" action="../../api/auth.php?action=login" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                
                                <!-- Email -->
                                <div class="mb-4">
                                    <label for="email_address" class="form-label fw-bold">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="fas fa-envelope text-muted"></i>
                                        </span>
                                        <input type="email" class="form-control border-start-0" 
                                               id="email_address" name="email_address" 
                                               placeholder="Enter your email" required>
                                    </div>
                                    <div class="invalid-feedback">Please enter a valid email address</div>
                                </div>

                                <!-- Password -->
                                <div class="mb-3">
                                    <label for="password" class="form-label fw-bold">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="fas fa-lock text-muted"></i>
                                        </span>
                                        <input type="password" class="form-control border-start-0" 
                                               id="password" name="password" 
                                               placeholder="Enter your password" required>
                                        <button type="button" class="password-toggle" onclick="togglePassword()">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">Please enter your password</div>
                                </div>

                                <!-- Remember Me & Forgot Password -->
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="form-check remember-me">
                                        <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me">
                                        <label class="form-check-label" for="remember_me">
                                            Remember me for 30 days
                                        </label>
                                    </div>
                                    <a href="forgot-password.php" class="forgot-link text-primary">
                                        Forgot password?
                                    </a>
                                </div>

                                <!-- Submit Button -->
                                <div class="d-grid gap-2 mb-4">
                                    <button type="submit" class="btn btn-primary btn-lg" id="loginBtn">
                                        <span id="loginText">Sign In</span>
                                        <span id="loginSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                                    </button>
                                </div>

                                <!-- Divider -->
                                <div class="position-relative my-4">
                                    <hr>
                                    <div class="position-absolute top-50 start-50 translate-middle bg-white px-3 text-muted">
                                        OR
                                    </div>
                                </div>

                                <!-- Register Links -->
                                <div class="text-center">
                                    <p class="mb-2">Don't have an account?</p>
                                    <div class="d-flex gap-2 justify-content-center">
                                        <a href="register.php?role=patient" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-user-injured me-1"></i>Patient
                                        </a>
                                        <a href="register.php?role=volunteer" class="btn btn-outline-success btn-sm">
                                            <i class="fas fa-hands-helping me-1"></i>Volunteer
                                        </a>
                                        <a href="register.php?role=doctor" class="btn btn-outline-info btn-sm">
                                            <i class="fas fa-user-md me-1"></i>Doctor
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Security Notice -->
                    <div class="mt-4 text-center text-muted small">
                        <i class="fas fa-shield-alt me-1"></i>
                        Your data is protected with 256-bit SSL encryption
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('#password + button i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Show message function
        function showMessage(type, message) {
            const container = document.getElementById('messageContainer');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            
            container.innerHTML = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        }
        
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const btn = document.getElementById('loginBtn');
    const text = document.getElementById('loginText');
    const spinner = document.getElementById('loginSpinner');

    // Save original button state
    const originalText = text.textContent;
    const originalHtml = btn.innerHTML;
    
    // Update button to loading state
    btn.disabled = true;
    btn.innerHTML = `
        <span class="spinner-border spinner-border-sm me-2" role="status"></span>
        Signing in...
    `;

    const formData = new FormData(this);

    try {
        const response = await fetch('../../api/auth.php?action=login', {
            method: 'POST',
            body: formData
        });

        // Get raw response first
        const rawResponse = await response.text();
        console.log('RAW API RESPONSE:', rawResponse);
        
        // Try to parse JSON
        let data;
        try {
            data = JSON.parse(rawResponse);
        } catch (jsonError) {
            console.error('JSON Parse Error at position:', jsonError.message);
            console.error('Raw response (first 200 chars):', rawResponse.substring(0, 200));
            throw new Error('Server returned invalid JSON. Check console for details.');
        }

        // Handle API response
if (data.success) {
    if (data.role === 'doctor' && !data.is_approved) {
        // Use the redirect from API or fallback
        const pendingUrl = data.redirect || '/healthConnect/views/auth/pending-approval.php';
        window.location.href = pendingUrl;
        return;
    }
    
    // ALWAYS use the redirect from API
    if (data.redirect) {
        // Smooth transition
        document.body.style.opacity = '0.7';
        document.body.style.transition = 'opacity 0.3s ease';
        setTimeout(() => {
            window.location.href = data.redirect;
        }, 300);
    } else {
        // If no redirect in response, show error
        showMessage('error', 'No redirect URL provided by server.');
        resetButton(btn, originalText, originalHtml);
    }
} else {
            // Show error message
            showMessage('error', data.message || 'Login failed. Please check your credentials.');
            
            // Reset button
            resetButton(btn, originalText, originalHtml);
        }
    } catch (error) {
        console.error('Login Error:', error);
        showMessage('error', error.message || 'Connection error. Please try again.');
        resetButton(btn, originalText, originalHtml);
    }
});
    </script>
</body>
</html>