// healthconnect/assets/js/main.js

// Main application JavaScript

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(tooltipTriggerEl => {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.forEach(popoverTriggerEl => {
        new bootstrap.Popover(popoverTriggerEl);
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href === '#' || href === '#!') return;

            e.preventDefault();
            const targetElement = document.querySelector(href);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });

    // Navbar scroll effect
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('navbar-scrolled');
            } else {
                navbar.classList.remove('navbar-scrolled');
            }
        });
    }

    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

// Toast notification system
function showToast(message, type = 'success', duration = 5000) {
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }

    // Create toast
    const toastId = 'toast-' + Date.now();
    const toast = document.createElement('div');
    toast.id = toastId;
    toast.className = `toast align-items-center text-bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');

    // Toast content
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-${getToastIcon(type)} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;

    toastContainer.appendChild(toast);

    // Initialize and show toast
    const bsToast = new bootstrap.Toast(toast, {
        animation: true,
        autohide: true,
        delay: duration
    });

    bsToast.show();

    // Remove toast after it's hidden
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });

    return toastId;
}

function getToastIcon(type) {
    switch (type) {
        case 'success':
            return 'check-circle';
        case 'danger':
            return 'exclamation-circle';
        case 'warning':
            return 'exclamation-triangle';
        case 'info':
            return 'info-circle';
        default:
            return 'info-circle';
    }
}

// Form validation helper
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('[required]');

    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
        }
    });

    return isValid;
}

// Password toggle function
function togglePassword(inputId, toggleIconId) {
    const passwordInput = document.getElementById(inputId);
    const toggleIcon = document.getElementById(toggleIconId);

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

// Character counter
function setupCharCounter(textareaId, counterId, maxLength) {
    const textarea = document.getElementById(textareaId);
    const counter = document.getElementById(counterId);

    if (!textarea || !counter) return;

    function updateCounter() {
        const length = textarea.value.length;
        counter.textContent = `${length}/${maxLength}`;

        if (length > maxLength * 0.9) {
            counter.style.color = '#dc3545';
        } else if (length > maxLength * 0.7) {
            counter.style.color = '#ffc107';
        } else {
            counter.style.color = '#6c757d';
        }
    }

    textarea.addEventListener('input', updateCounter);
    updateCounter(); // Initial update
}

// File upload preview
function setupFilePreview(inputId, previewId) {
    const fileInput = document.getElementById(inputId);
    const preview = document.getElementById(previewId);

    if (!fileInput || !preview) return;

    fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            const reader = new FileReader();

            reader.onload = function(e) {
                preview.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-file me-2"></i>
                        <strong>${file.name}</strong> (${(file.size / 1024).toFixed(1)} KB)
                    </div>
                `;
            };

            if (file.type.startsWith('image/')) {
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-file me-2"></i>
                        <strong>${file.name}</strong> (${(file.size / 1024).toFixed(1)} KB)
                    </div>
                `;
            }
        }
    });
}

// API request helper
async function apiRequest(endpoint, method = 'GET', data = null) {
    const headers = {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    };

    // Add CSRF token if available
    const csrfToken = document.querySelector('[name="csrf_token"]');
    if (csrfToken) {
        headers['X-CSRF-Token'] = csrfToken.value;
    }

    const options = {
        method: method,
        headers: headers,
        credentials: 'same-origin'
    };

    if (data && (method === 'POST' || method === 'PUT')) {
        options.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(endpoint, options);
        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || 'API request failed');
        }

        return result;
    } catch (error) {
        console.error('API request error:', error);
        throw error;
    }
}

// Session timeout warning
let sessionTimeoutWarning;

function setupSessionTimeout(warningMinutes = 5, logoutMinutes = 30) {
    const warningTime = warningMinutes * 60 * 1000;
    const logoutTime = logoutMinutes * 60 * 1000;

    let lastActivity = Date.now();

    function resetTimer() {
        lastActivity = Date.now();
        clearTimeout(sessionTimeoutWarning);

        sessionTimeoutWarning = setTimeout(() => {
            // Show warning
            showToast(`Your session will expire in ${warningMinutes} minutes. Click to extend.`, 'warning', 10000);

            // Set logout timer
            setTimeout(() => {
                window.location.href = '/healthconnect/views/auth/login.php?error=timeout';
            }, logoutTime);
        }, warningTime);
    }

    // Reset timer on user activity
    ['click', 'mousemove', 'keypress', 'scroll'].forEach(event => {
        document.addEventListener(event, resetTimer, { passive: true });
    });

    resetTimer();
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set up session timeout warning for authenticated pages
    if (document.body.classList.contains('authenticated')) {
        setupSessionTimeout(5, 30);
    }

    // Add animation to cards
    const cards = document.querySelectorAll('.card, .stat-card, .request-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('animate-fade-in');
    });

    // Initialize any character counters
    document.querySelectorAll('[data-char-counter]').forEach(element => {
        const targetId = element.getAttribute('data-char-counter');
        const maxLength = element.getAttribute('maxlength') || 255;
        const counterId = `${targetId}-counter`;

        setupCharCounter(targetId, counterId, maxLength);
    });
});

// Export functions for use in other files
window.HealthConnect = {
    showToast,
    validateForm,
    togglePassword,
    apiRequest,
    setupCharCounter,
    setupFilePreview
};