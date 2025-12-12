// assets/js/validation.js

// Email validation regex
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

// Password strength check
function checkPasswordStrength(password) {
    let strength = 0;

    // Length check
    if (password.length >= 8) strength += 25;
    if (password.length >= 12) strength += 10;

    // Character variety
    if (/[a-z]/.test(password)) strength += 20;
    if (/[A-Z]/.test(password)) strength += 20;
    if (/[0-9]/.test(password)) strength += 20;
    if (/[^A-Za-z0-9]/.test(password)) strength += 15;

    return Math.min(strength, 100);
}

// Update password strength meter
function updatePasswordStrength(password) {
    const strength = checkPasswordStrength(password);
    const bar = document.getElementById('passwordStrengthBar');

    // Set color based on strength
    if (strength < 40) {
        bar.style.background = '#dc3545'; // Red
    } else if (strength < 70) {
        bar.style.background = '#ffc107'; // Yellow
    } else {
        bar.style.background = '#28a745'; // Green
    }

    bar.style.width = strength + '%';
}

// Check email availability
async function checkEmailAvailability(email) {
    if (!emailRegex.test(email)) return false;

    try {
        const formData = new FormData();
        formData.append('email', email);
        formData.append('csrf_token', document.querySelector('[name="csrf_token"]').value);

        const response = await fetch('../../api/auth.php?action=check_email', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        return result;
    } catch (error) {
        console.error('Email check error:', error);
        return { available: false, message: 'Error checking email' };
    }
}

// Form validation for registration
function validateRegistrationForm(formData) {
    const errors = [];

    // Name validation
    if (!formData.get('full_name').trim()) {
        errors.push('Full name is required');
    }

    // Email validation
    const email = formData.get('email_address');
    if (!email) {
        errors.push('Email is required');
    } else if (!emailRegex.test(email)) {
        errors.push('Invalid email format');
    }

    // Password validation
    const password = formData.get('password');
    const confirmPassword = formData.get('confirm_password');

    if (!password) {
        errors.push('Password is required');
    } else if (password.length < 8) {
        errors.push('Password must be at least 8 characters');
    } else if (!/[A-Z]/.test(password) || !/[a-z]/.test(password) ||
        !/[0-9]/.test(password) || !/[^A-Za-z0-9]/.test(password)) {
        errors.push('Password must include uppercase, lowercase, number, and special character');
    }

    if (password !== confirmPassword) {
        errors.push('Passwords do not match');
    }

    // Role validation
    const role = formData.get('user_role');
    if (!['patient', 'volunteer', 'doctor'].includes(role)) {
        errors.push('Please select a valid role');
    }

    // Doctor certificate validation
    if (role === 'doctor') {
        const certFile = document.getElementById('certificate_file').files[0];
        if (!certFile) {
            errors.push('Medical certificate is required for doctors');
        }
    }

    return errors;
}

// Initialize validation on page load
document.addEventListener('DOMContentLoaded', function() {
    // Password strength indicator
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            updatePasswordStrength(this.value);
        });
    }

    // Email availability check
    const emailInput = document.getElementById('email_address');
    if (emailInput) {
        let timeout = null;

        emailInput.addEventListener('input', function() {
            clearTimeout(timeout);
            const emailStatus = document.getElementById('emailStatus');

            if (!emailRegex.test(this.value)) {
                emailStatus.innerHTML = '<span class="text-warning">Enter a valid email</span>';
                return;
            }

            emailStatus.innerHTML = '<span class="text-info">Checking availability...</span>';

            timeout = setTimeout(async() => {
                const result = await checkEmailAvailability(this.value);

                if (result.available) {
                    emailStatus.innerHTML = '<span class="text-success">✓ Email available</span>';
                } else {
                    emailStatus.innerHTML = '<span class="text-danger">✗ ' + result.message + '</span>';
                }
            }, 500);
        });
    }

    // Registration form submission
    const registrationForm = document.getElementById('registrationForm');
    if (registrationForm) {
        registrationForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const form = this;
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const loadingSpinner = document.getElementById('loadingSpinner');

            // Validate form
            const formData = new FormData(form);
            const errors = validateRegistrationForm(formData);

            if (errors.length > 0) {
                alert('Please fix the following errors:\n\n' + errors.join('\n'));
                return;
            }

            // Check terms
            if (!document.getElementById('terms').checked) {
                alert('You must agree to the terms and conditions');
                return;
            }

            // Show loading
            submitBtn.disabled = true;
            submitText.textContent = 'Creating Account...';
            loadingSpinner.classList.remove('d-none');

            try {
                const response = await fetch('../../api/auth.php?action=register', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    window.location.href = result.redirect;
                } else {
                    alert(result.message);

                    // Reset button
                    submitBtn.disabled = false;
                    submitText.textContent = 'Create Account';
                    loadingSpinner.classList.add('d-none');
                }
            } catch (error) {
                console.error('Registration error:', error);
                alert('An error occurred. Please try again.');

                // Reset button
                submitBtn.disabled = false;
                submitText.textContent = 'Create Account';
                loadingSpinner.classList.add('d-none');
            }
        });
    }
});