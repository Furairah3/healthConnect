<?php
// healthconnect/api/auth.php

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start(); 
// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database configuration
require_once '../app/config/database.php';

// Get action from URL
$action = $_GET['action'] ?? '';

// Test endpoint
if ($action === 'test') {
    echo json_encode([
        'success' => true,
        'message' => 'API is working!',
        'timestamp' => date('Y-m-d H:i:s'),
        'session_id' => session_id(),
        'csrf_token' => $_SESSION['csrf_token'] ?? 'not_set'
    ]);
    exit();
}

// Handle login
if ($action === 'login') {
    try {
        // Get input data
        $input = $_POST;
        
        // Debug logging
        error_log("Login attempt: " . print_r($input, true));
        
        // Validate CSRF token
        if (!isset($input['csrf_token']) || !isset($_SESSION['csrf_token']) || $input['csrf_token'] !== $_SESSION['csrf_token']) {
            echo json_encode([
                'success' => false,
                'message' => 'Security token invalid or expired. Please refresh the page.',
                'debug' => [
                    'session_token' => $_SESSION['csrf_token'] ?? 'not_set',
                    'received_token' => $input['csrf_token'] ?? 'not_received'
                ]
            ]);
            exit();
        }
        
        // Validate input
        if (empty($input['email_address']) || empty($input['password'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Email and password are required'
            ]);
            exit();
        }
        
        $email = cleanInput($input['email_address']);
        $password = $input['password'];
        $remember_me = isset($input['remember_me']);
        
        // Check if user exists using prepared statement
        $sql = "SELECT user_id, full_name, email_address, password_hash, user_role, is_approved 
                FROM hc_users 
                WHERE email_address = :email";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid email or password'
            ]);
            exit();
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid email or password'
            ]);
            exit();
        }
        
       // Check if doctor is approved (ONLY for doctors who aren't approved)
if ($user['user_role'] === 'doctor' && !$user['is_approved']) {
    // ⭐⭐⭐ ADD THESE SESSION VARIABLES FOR UNAPPROVED DOCTORS TOO ⭐⭐⭐
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email_address'];
    $_SESSION['user_role'] = $user['user_role'];
    $_SESSION['is_approved'] = $user['is_approved'];
    $_SESSION['logged_in'] = true;
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful! Your account is pending admin approval.',
        'role' => 'doctor',
        'is_approved' => false,
        'redirect' => '/~foureiratou.idi/healthConnect/views/auth/pending-approval.php'
    ]);
    exit();
}
        
        // Create session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email_address'];
        $_SESSION['user_role'] = $user['user_role'];
        $_SESSION['is_approved'] = $user['is_approved'];
        $_SESSION['logged_in'] = true;
        
        // "Remember Me" feature
        if ($remember_me) {
            $token = bin2hex(random_bytes(32));
            $expiry = time() + (30 * 24 * 60 * 60); // 30 days
            
            // Store in database
            $sql = "INSERT INTO hc_user_sessions (session_id, user_id, login_time, last_activity, ip_address, user_agent, is_remembered) 
                    VALUES (:token, :user_id, NOW(), NOW(), :ip, :agent, TRUE)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':token' => $token,
                ':user_id' => $user['user_id'],
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            // Set cookie
            setcookie('healthconnect_remember', $token, $expiry, '/', '', false, true);
        }
        
        // Log activity
        logActivity($user['user_id'], 'login', 'User logged in');
        
        // Correct redirect paths from root
       
        $redirectMap = [
            'patient'   => '/~foureiratou.idi/healthConnect/views/auth/patient-dashboard.php',
            'doctor'    => '/~foureiratou.idi/healthConnect/views/auth/doctor-dashboard.php',
            'volunteer' => '/~foureiratou.idi/healthConnect/views/auth/volunteer-dashboard.php',
            'admin'     => '/~foureiratou.idi/healthConnect/views/auth/admin-dashboard.php'
        ];

        $redirect = $redirectMap[$user['user_role']] ?? '/~foureiratou.idi/healthConnect/views/auth/patient-dashboard.php';

        echo json_encode([
            'success' => true,
            'message' => 'Login successful!',
            'role' => $user['user_role'],
            'is_approved' => (bool)$user['is_approved'],
            'redirect' => $redirect
        ]);
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred. Please try again.',
            'error' => IS_DEV ? $e->getMessage() : null
        ]);
    }
    exit();
}

// Handle registration
if ($action === 'register') {
    try {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            echo json_encode([
                'success' => false,
                'message' => 'Security token invalid or expired. Please refresh the page.'
            ]);
            exit();
        }
        
        // Get form data
        $full_name = cleanInput($_POST['full_name'] ?? '');
        $email = cleanInput($_POST['email_address'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $user_role = cleanInput($_POST['user_role'] ?? 'patient');
        
        // Validation
        if (empty($full_name) || empty($email) || empty($password)) {
            echo json_encode([
                'success' => false,
                'message' => 'All fields are required'
            ]);
            exit();
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid email address'
            ]);
            exit();
        }
        
        if ($password !== $confirm_password) {
            echo json_encode([
                'success' => false,
                'message' => 'Passwords do not match'
            ]);
            exit();
        }
        
        // Password strength
        if (strlen($password) < 8) {
            echo json_encode([
                'success' => false,
                'message' => 'Password must be at least 8 characters'
            ]);
            exit();
        }
        
        if (!preg_match('/[A-Z]/', $password) || 
            !preg_match('/[a-z]/', $password) || 
            !preg_match('/[0-9]/', $password) || 
            !preg_match('/[^A-Za-z0-9]/', $password)) {
            echo json_encode([
                'success' => false,
                'message' => 'Password must include uppercase, lowercase, number, and special character'
            ]);
            exit();
        }
        
        // Check if email exists
        $sql = "SELECT COUNT(*) FROM hc_users WHERE email_address = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        
        if ($stmt->fetchColumn() > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Email already registered'
            ]);
            exit();
        }
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Handle file upload for doctors
        $certificate_filename = null;
        $certificate_error = null;
        
        if ($user_role === 'doctor') {
            if (isset($_FILES['certificate_file']) && $_FILES['certificate_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['certificate_file'];
                
                $allowed_types = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                    // Create uploads directory if it doesn't exist
                    $upload_dir = '../uploads/certificates/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Generate unique filename
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $certificate_filename = uniqid('cert_', true) . '.' . $extension;
                    $target_path = $upload_dir . $certificate_filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $target_path)) {
                        // File uploaded successfully
                    } else {
                        $certificate_error = 'Failed to upload certificate';
                    }
                } else {
                    $certificate_error = 'Invalid file type or size (max 5MB). Allowed: PDF, JPG, PNG';
                }
            } else {
                $certificate_error = 'Medical certificate is required for doctors';
            }
            
            if ($certificate_error) {
                echo json_encode([
                    'success' => false,
                    'message' => $certificate_error
                ]);
                exit();
            }
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Insert user
            $is_approved = ($user_role !== 'doctor'); // Doctors need approval
            $sql = "INSERT INTO hc_users (full_name, email_address, password_hash, user_role, is_approved, certificate_filename) 
                    VALUES (:name, :email, :hash, :role, :approved, :cert)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $full_name,
                ':email' => $email,
                ':hash' => $password_hash,
                ':role' => $user_role,
                ':approved' => $is_approved,
                ':cert' => $certificate_filename
            ]);
            
            $user_id = $pdo->lastInsertId();
            
            // If doctor, create verification record
            if ($user_role === 'doctor' && $certificate_filename) {
                $sql = "INSERT INTO hc_doctor_verifications (doctor_user_id, document_filename, verification_status) 
                        VALUES (:doctor_id, :filename, 'pending_review')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':doctor_id' => $user_id,
                    ':filename' => $certificate_filename
                ]);
            }
            
            // Log activity
            logActivity($user_id, 'registration', 'New user registered as ' . $user_role);
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Registration successful! ' . 
                            ($user_role === 'doctor' ? 'Your account is pending admin approval.' : 'You can now login.'),
                'redirect' => 'foureiratou.idi/healthConnect/views/auth/login.php'
            ]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Registration error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Registration process error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred during registration'
        ]);
    }
    exit();
}

// Check email availability
if ($action === 'check_email') {
    $email = cleanInput($_POST['email'] ?? '');
    
    if (empty($email)) {
        echo json_encode(['available' => false, 'message' => 'Email is required']);
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['available' => false, 'message' => 'Invalid email format']);
        exit();
    }
    
    try {
        $sql = "SELECT COUNT(*) FROM hc_users WHERE email_address = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $count = $stmt->fetchColumn();
        
        echo json_encode([
            'available' => $count === 0,
            'message' => $count === 0 ? 'Email is available' : 'Email already registered'
        ]);
    } catch (Exception $e) {
        echo json_encode(['available' => false, 'message' => 'Error checking email']);
    }
    exit();
}

// Helper function to log activities
function logActivity($user_id, $type, $description) {
    global $pdo;
    
    try {
        $sql = "INSERT INTO hc_activity_logs (user_id, activity_type, activity_description, ip_address, user_agent) 
                VALUES (:user_id, :type, :desc, :ip, :agent)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $user_id,
            ':type' => $type,
            ':desc' => $description,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        error_log("Activity log error: " . $e->getMessage());
    }
}

// Default response
echo json_encode([
    'success' => false,
    'message' => 'Invalid action',
    'available_actions' => ['test', 'login', 'register', 'check_email']
]);
