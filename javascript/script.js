document.addEventListener("DOMContentLoaded", function () {
    
    // --- 1. SIGNUP PAGE FUNCTIONALITY ---
    const signupPassword = document.getElementById("password");
    const confirmPassword = document.getElementById("confirmPassword");
    const signupCheckbox = document.getElementById("showPassword");

    // Password guidelines color validation
    if (signupPassword) {
        signupPassword.addEventListener("keyup", function () {
            const value = signupPassword.value;

            document.getElementById("length").classList.toggle("valid", value.length >= 8);
            document.getElementById("max").classList.toggle("valid", value.length <= 16);
            document.getElementById("upper").classList.toggle("valid", /[A-Z]/.test(value));
            document.getElementById("lower").classList.toggle("valid", /[a-z]/.test(value));
            document.getElementById("number").classList.toggle("valid", /[0-9]/.test(value));
            document.getElementById("special").classList.toggle("valid", /[\W]/.test(value));
        });
    }

    // Toggle Show/Hide on Signup Page
    if (signupCheckbox) {
        signupCheckbox.addEventListener("change", function () {
            const type = this.checked ? "text" : "password";
            if (signupPassword) signupPassword.type = type;
            if (confirmPassword) confirmPassword.type = type;
        });
    }

    // --- 2. LOGIN PAGE FUNCTIONALITY ---
    const loginPassword = document.getElementById("loginPassword");
    const loginCheckbox = document.getElementById("showLoginPassword");

    // Toggle Show/Hide on Login Page
    if (loginCheckbox && loginPassword) {
        loginCheckbox.addEventListener("change", function () {
            loginPassword.type = this.checked ? "text" : "password";
        });
    }
});
/**
 * Dashboard JavaScript
 * Handles interactive elements on the dashboard
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Animate progress bar on load
    animateProgressBar();
    
    // Add hover effects to stat cards
    setupStatCards();
    
    // Handle notification clicks
    setupNotifications();
    
    // Auto-refresh dashboard stats every 30 seconds (optional)
    // setupAutoRefresh();
});

/**
 * Animate progress bar with a smooth transition
 */
function animateProgressBar() {
    const progressFill = document.querySelector('.progress-fill');
    if (progressFill) {
        // The width is already set inline, but we can trigger a reflow for animation
        const width = progressFill.style.width;
        progressFill.style.width = '0%';
        setTimeout(() => {
            progressFill.style.width = width;
        }, 100);
    }
}

/**
 * Setup stat card hover effects with sound or extra animation
 */
function setupStatCards() {
    const cards = document.querySelectorAll('.stat-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            // Add a subtle glow effect
            this.style.transition = 'all 0.3s ease';
        });
        
        card.addEventListener('mouseleave', function() {
            // Remove glow effect
            this.style.transition = 'all 0.3s ease';
        });
    });
}

/**
 * Setup notification bell
 */
function setupNotifications() {
    const notificationBell = document.querySelector('.notification');
    if (notificationBell) {
        notificationBell.addEventListener('click', function() {
            // Show notification dropdown or alert
            alert('🔔 You have 3 unread notifications!');
            
            // In a real implementation, you would fetch notifications via AJAX
            // and display them in a dropdown
            fetchNotifications();
        });
    }
}

/**
 * Fetch notifications via AJAX (placeholder)
 */
function fetchNotifications() {
    // This would be an AJAX call to get notifications
    // For now, just log to console
    console.log('Fetching notifications...');
    
    /*
    // Example AJAX implementation:
    fetch('../api/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            console.log('Notifications:', data);
            // Display notifications in a dropdown
        })
        .catch(error => {
            console.error('Error fetching notifications:', error);
        });
    */
}

/**
 * Optional: Auto-refresh dashboard stats
 */
function setupAutoRefresh() {
    // Refresh every 30 seconds to update stats
    setInterval(function() {
        // Only refresh if the page is visible
        if (!document.hidden) {
            refreshDashboardStats();
        }
    }, 30000);
}

/**
 * Refresh dashboard stats via AJAX
 */
function refreshDashboardStats() {
    // This would be an AJAX call to refresh stats without reloading the page
    console.log('Refreshing dashboard stats...');
    
    /*
    // Example implementation:
    fetch('../api/get_dashboard_stats.php')
        .then(response => response.json())
        .then(data => {
            // Update stat numbers
            document.querySelector('.stat-card.green .number').textContent = data.totalLessons;
            document.querySelector('.stat-card.gold .number').textContent = data.completedQuests;
            document.querySelector('.stat-card.blue .number').textContent = data.progress + '%';
            document.querySelector('.stat-card.purple .number').textContent = data.totalCertificates;
            
            // Update progress bar
            const progressFill = document.querySelector('.progress-fill');
            progressFill.style.width = data.progress + '%';
            
            // Update progress text
            document.querySelector('.progress-text span:last-child').textContent = data.progress + '% Complete';
        })
        .catch(error => {
            console.error('Error refreshing stats:', error);
        });
    */
}

/**
 * Utility: Format date for display
 */
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
}

/**
 * Utility: Show a toast notification
 */
function showToast(message, type = 'success') {
    // Create toast element if it doesn't exist
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container';
        toastContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        `;
        document.body.appendChild(toastContainer);
    }
    
    const toast = document.createElement('div');
    const colors = {
        success: '#03a60c',
        error: '#dc3545',
        warning: '#f59e0b',
        info: '#3b82f6'
    };
    
    toast.style.cssText = `
        background: white;
        padding: 15px 25px;
        margin-bottom: 10px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        border-left: 4px solid ${colors[type] || colors.success};
        animation: slideIn 0.3s ease;
        min-width: 250px;
    `;
    
    toast.textContent = message;
    toastContainer.appendChild(toast);
    
    // Remove toast after 3 seconds
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 3000);
}

// Add keyframe animations for toast
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Example: Show welcome toast
// showToast('Welcome back! 👋', 'success');