<?php
// healthconnect/index.php (Updated navigation section)
require_once 'app/config/database.php';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthConnect - Bridging Rural Healthcare Gaps</title>
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="index.php">
                <i class="fas fa-heartbeat me-2"></i>HealthConnect
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item">
                        <a href="views/auth/login.php" class="btn btn-outline-primary btn-sm ms-2">Login</a>
                    </li>
                    <li class="nav-item ms-2">
                        <a href="views/auth/register.php" class="btn btn-primary">Get Started</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section pt-5 mt-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Connecting Rural Communities to Healthcare</h1>
                    <p class="lead mb-4">HealthConnect bridges the gap between remote patients and medical volunteers. Get medical advice, share health tips, and access healthcare resources—all in one platform.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="views/auth/register.php?role=patient" class="btn btn-primary btn-lg px-4">
                            <i class="fas fa-user-injured me-2"></i>I Need Help
                        </a>
                        <a href="views/auth/register.php?role=volunteer" class="btn btn-outline-primary btn-lg px-4">
                            <i class="fas fa-hands-helping me-2"></i>I Want to Help
                        </a>
                        <a href="views/auth/register.php?role=doctor" class="btn btn-success btn-lg px-4">
                            <i class="fas fa-user-md me-2"></i>I'm a Medical Professional
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-image text-center">
                        <img src="https://images.unsplash.com/photo-1579684385127-1ef15d508118?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" 
                             alt="Healthcare professionals" class="img-fluid rounded-3 shadow-lg">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5 bg-light">
        <div class="container">
            <div class="row mb-5">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="display-5 fw-bold mb-3">Why Choose HealthConnect?</h2>
                    <p class="lead">A comprehensive platform designed for rural healthcare access</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm hover-shadow">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-4">
                                <i class="fas fa-comments-medical fa-3x text-primary"></i>
                            </div>
                            <h4 class="card-title fw-bold">Direct Communication</h4>
                            <p class="card-text">Connect directly with healthcare volunteers and doctors for medical advice tailored to your situation.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm hover-shadow">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-4">
                                <i class="fas fa-shield-alt fa-3x text-success"></i>
                            </div>
                            <h4 class="card-title fw-bold">Verified Professionals</h4>
                            <p class="card-text">All medical professionals are thoroughly verified with proper credentials before they can offer advice.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm hover-shadow">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon mb-4">
                                <i class="fas fa-lightbulb fa-3x text-warning"></i>
                            </div>
                            <h4 class="card-title fw-bold">Health Tips & Resources</h4>
                            <p class="card-text">Access verified medical tips, preventive care information, and health resources curated by professionals.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="py-5">
        <div class="container">
            <div class="row mb-5">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="display-5 fw-bold mb-3">How HealthConnect Works</h2>
                    <p class="lead">Three simple steps to get the help you need</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="step-card text-center p-4">
                        <div class="step-number">1</div>
                        <h4 class="fw-bold mt-3">Create Your Account</h4>
                        <p>Register as a patient, volunteer, or medical professional. Doctors provide credentials for verification.</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="step-card text-center p-4">
                        <div class="step-number">2</div>
                        <h4 class="fw-bold mt-3">Submit or Respond to Requests</h4>
                        <p>Patients describe their health concerns. Volunteers and doctors provide guidance and support.</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="step-card text-center p-4">
                        <div class="step-number">3</div>
                        <h4 class="fw-bold mt-3">Access Health Resources</h4>
                        <p>Browse medical tips, preventive care information, and connect with verified healthcare providers.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Role-Based Sections -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card role-card h-100 text-center border-0 shadow">
                        <div class="card-header bg-primary text-white py-4">
                            <i class="fas fa-user-injured fa-3x"></i>
                            <h3 class="mt-3 fw-bold">For Patients</h3>
                        </div>
                        <div class="card-body p-4">
                            <ul class="list-unstyled text-start">
                                <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i>Submit health requests</li>
                                <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i>Get responses from volunteers</li>
                                <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i>Access medical tips</li>
                                <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i>Track request history</li>
                            </ul>
                            <a href="views/auth/register.php?role=patient" class="btn btn-outline-primary mt-3">Register as Patient</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card role-card h-100 text-center border-0 shadow">
                        <div class="card-header bg-success text-white py-4">
                            <i class="fas fa-hands-helping fa-3x"></i>
                            <h3 class="mt-3 fw-bold">For Volunteers</h3>
                        </div>
                        <div class="card-body p-4">
                            <ul class="list-unstyled text-start">
                                <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i>View patient requests</li>
                                <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i>Provide health advice</li>
                                <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i>Share health resources</li>
                                <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i>Help rural communities</li>
                            </ul>
                            <a href="views/auth/register.php?role=volunteer" class="btn btn-outline-success mt-3">Register as Volunteer</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card role-card h-100 text-center border-0 shadow">
                        <div class="card-header bg-info text-white py-4">
                            <i class="fas fa-user-md fa-3x"></i>
                            <h3 class="mt-3 fw-bold">For Doctors</h3>
                        </div>
                        <div class="card-body p-4">
                            <ul class="list-unstyled text-start">
                                <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i>Verified credentials required</li>
                                <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i>Provide medical advice</li>
                                <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i>Create health tips</li>
                                <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i>Admin approval system</li>
                            </ul>
                            <a href="views/auth/register.php?role=doctor" class="btn btn-outline-info mt-3">Register as Doctor</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <h4 class="fw-bold mb-4"><i class="fas fa-heartbeat me-2"></i>HealthConnect</h4>
                    <p>Bridging healthcare gaps in rural communities through technology and volunteerism.</p>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h5 class="fw-bold mb-4">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#home" class="text-white-50 text-decoration-none">Home</a></li>
                        <li class="mb-2"><a href="#features" class="text-white-50 text-decoration-none">Features</a></li>
                        <li class="mb-2"><a href="#how-it-works" class="text-white-50 text-decoration-none">How It Works</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5 class="fw-bold mb-4">Account</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="views/auth/login.php" class="text-white-50 text-decoration-none">Login</a></li>
                        <li class="mb-2"><a href="views/auth/register.php" class="text-white-50 text-decoration-none">Register</a></li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h5 class="fw-bold mb-4">Contact</h5>
                    <p class="text-white-50"><i class="fas fa-envelope me-2"></i>support@healthconnect.org</p>
                    <p class="text-white-50"><i class="fas fa-phone me-2"></i>+233 503 638 535</p>
                </div>
            </div>
            <hr class="bg-white my-4">
            <div class="text-center">
                <p class="mb-0">&copy; 2025 HealthConnect. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>