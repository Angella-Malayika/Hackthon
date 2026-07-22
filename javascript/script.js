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
<<<<<<< Updated upstream
});
=======
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

/**
 * Admin Panel JavaScript
 * Handles admin-specific functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize admin components
    initSidebarToggle();
    initNotifications();
    initCharts();
    
});

/**
 * Toggle sidebar on mobile
 */
function initSidebarToggle() {
    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);
    
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });
    }
    
    // Close sidebar when clicking overlay
    overlay.addEventListener('click', function() {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
    });
    
    // Close sidebar on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        }
    });
}

/**
 * Notification dropdown toggle
 */
function initNotifications() {
    const notificationWrapper = document.querySelector('.notification-wrapper');
    const dropdown = document.getElementById('notificationDropdown');
    const badge = document.getElementById('notificationBadge');
    
    if (notificationWrapper && dropdown) {
        notificationWrapper.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.toggle('show');
            
            // If dropdown is shown and there are unread notifications, mark them as read
            if (dropdown.classList.contains('show')) {
                // In a real implementation, you'd make an AJAX call here
                // markNotificationsAsRead();
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!notificationWrapper.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
        
        // Mark all as read
        const markAllRead = document.querySelector('.mark-all-read');
        if (markAllRead) {
            markAllRead.addEventListener('click', function(e) {
                e.stopPropagation();
                markAllNotificationsAsRead();
            });
        }
    }
}

/**
 * Mark all notifications as read
 */
function markAllNotificationsAsRead() {
    // In a real implementation, this would be an AJAX call
    console.log('Marking all notifications as read...');
    
    // Remove unread class from all items
    document.querySelectorAll('.notification-item.unread').forEach(item => {
        item.classList.remove('unread');
    });
    
    // Update badge
    const badge = document.getElementById('notificationBadge');
    if (badge) {
        badge.textContent = '0';
        badge.style.display = 'none';
    }
    
    // Show success message
    showAdminToast('All notifications marked as read ✅');
}

/**
 * Initialize charts using Chart.js or simple CSS charts
 */
function initCharts() {
    // This is a placeholder for chart initialization
    // When you add Chart.js library, you can initialize charts here
    
    // Example: Simple progress bars for demo
    const stats = document.querySelectorAll('.stat-number');
    stats.forEach(stat => {
        const number = parseInt(stat.textContent);
        if (number > 0) {
            // Animate counting
            animateCounter(stat, number);
        }
    });
}

/**
 * Animate counter for stats
 */
function animateCounter(element, target) {
    let current = 0;
    const increment = target / 30;
    const duration = 800; // ms
    const stepTime = duration / 30;
    
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            element.textContent = target;
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(current);
        }
    }, stepTime);
}

/**
 * Show toast notification in admin panel
 */
function showAdminToast(message, type = 'success') {
    // Create toast container if it doesn't exist
    let container = document.querySelector('.admin-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'admin-toast-container';
        container.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        `;
        document.body.appendChild(container);
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
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        border-left: 4px solid ${colors[type] || colors.success};
        animation: slideInRight 0.3s ease;
        min-width: 280px;
        font-family: 'Poppins', sans-serif;
        display: flex;
        align-items: center;
        gap: 12px;
    `;
    
    // Add icon
    const icons = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };
    
    toast.innerHTML = `
        <span style="font-size: 20px;">${icons[type] || '✅'}</span>
        <span style="color: #1a1a2e; font-size: 14px;">${message}</span>
    `;
    
    container.appendChild(toast);
    
    // Remove toast after 4 seconds
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 4000);
}

// Add animations
const styleSheet = document.createElement('style');
styleSheet.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
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
document.head.appendChild(styleSheet);

/**
 * Fetch admin stats via AJAX (for real-time updates)
 */
function fetchAdminStats() {
    // This would be an AJAX call to get real-time stats
    console.log('Fetching admin stats...');
    
    /*
    // Example implementation:
    fetch('../api/admin_stats.php')
        .then(response => response.json())
        .then(data => {
            // Update stat cards
            document.querySelector('.stat-number[data-stat="users"]').textContent = data.totalUsers;
            document.querySelector('.stat-number[data-stat="lessons"]').textContent = data.totalLessons;
            // ... update other stats
        })
        .catch(error => {
            console.error('Error fetching stats:', error);
        });
    */
}

// Example: Fetch stats every 60 seconds
// setInterval(fetchAdminStats, 60000);

/**
 * Admin User Management JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize user management functions
    initDeleteConfirm();
    initBlockConfirm();
    initSearchAutoSubmit();
    initPaginationLinks();
    
});

/**
 * Initialize delete confirmation
 */
function initDeleteConfirm() {
    const deleteLinks = document.querySelectorAll('.action-btn.delete');
    deleteLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const confirmDelete = confirm('Are you sure you want to delete this user? This action cannot be undone!');
            if (!confirmDelete) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Initialize block/unblock confirmation
 */
function initBlockConfirm() {
    const blockLinks = document.querySelectorAll('.action-btn.block, .action-btn.unblock');
    blockLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const action = this.classList.contains('block') ? 'block' : 'unblock';
            const confirmAction = confirm(`Are you sure you want to ${action} this user?`);
            if (!confirmAction) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Auto-submit search when typing (with delay)
 */
function initSearchAutoSubmit() {
    const searchInput = document.querySelector('input[name="search"]');
    const statusSelect = document.querySelector('select[name="status"]');
    const searchForm = document.querySelector('.table-controls');
    
    if (searchInput && searchForm) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchForm.submit();
            }, 500);
        });
    }
    
    if (statusSelect && searchForm) {
        statusSelect.addEventListener('change', function() {
            searchForm.submit();
        });
    }
}

/**
 * Initialize pagination links with AJAX (optional)
 */
function initPaginationLinks() {
    const paginationLinks = document.querySelectorAll('.pagination a');
    paginationLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // If you want AJAX pagination, implement here
            // For now, we'll let it work with normal page reloads
            // This is just a placeholder for future enhancement
        });
    });
}

/**
 * Format date for display
 */
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

/**
 * Get user initials from name
 */
function getUserInitials(name) {
    if (!name) return '?';
    const parts = name.trim().split(' ');
    if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
}

/**
 * Generate random avatar color based on name
 */
function getAvatarColor(name) {
    if (!name) return '#03a60c';
    const colors = [
        '#03a60c', '#3b82f6', '#8b5cf6', '#f59e0b', 
        '#ef4444', '#ec4899', '#14b8a6', '#f97316'
    ];
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    return colors[Math.abs(hash) % colors.length];
}

/**
 * Show toast notification
 */
function showToast(message, type = 'success') {
    const toastContainer = document.querySelector('.toast-message');
    if (toastContainer) {
        toastContainer.className = `toast-message ${type}`;
        toastContainer.innerHTML = `
            <span>${type === 'success' ? '✅' : '❌'}</span>
            ${message}
        `;
        toastContainer.style.display = 'flex';
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            toastContainer.style.display = 'none';
        }, 5000);
    }
}

/**
 * Export users table to CSV
 */
function exportUsersToCSV() {
    const table = document.querySelector('table');
    if (!table) return;
    
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    // Get headers
    const headers = [];
    const headerCells = rows[0].querySelectorAll('th');
    headerCells.forEach(th => {
        headers.push(th.textContent.trim());
    });
    csv.push(headers.join(','));
    
    // Get data rows
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.querySelectorAll('td');
        const rowData = [];
        cells.forEach(td => {
            // Clean up the data
            let text = td.textContent.trim();
            // Remove extra whitespace
            text = text.replace(/\s+/g, ' ');
            rowData.push(text);
        });
        csv.push(rowData.join(','));
    }
    
    // Download CSV
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `users_${new Date().toISOString().slice(0,10)}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

// Export functions for use in other scripts if needed
window.exportUsersToCSV = exportUsersToCSV;
window.showToast = showToast;

// Auto-hide toast messages after 5 seconds
        setTimeout(function() {
            const toast = document.querySelector('.toast-message');
            if (toast) {
                toast.style.display = 'none';
            }
        }, 5000);
        
        // Validate XP reward input
        document.querySelector('form').addEventListener('submit', function(e) {
            const xpInput = document.getElementById('xp_reward');
            if (xpInput) {
                const value = parseInt(xpInput.value);
                if (isNaN(value) || value < 1) {
                    e.preventDefault();
                    alert('XP Reward must be at least 1');
                    xpInput.focus();
                    return false;
                }
                if (value > 1000) {
                    e.preventDefault();
                    alert('XP Reward cannot exceed 1000');
                    xpInput.focus();
                    return false;
                }
            }
        });
        // Auto-hide toast messages after 5 seconds
        setTimeout(function() {
            const toast = document.querySelector('.toast-message');
            if (toast) {
                toast.style.display = 'none';
            }
        }, 5000);
        
        // Select icon from grid
        function selectIcon(icon) {
            document.getElementById('image').value = icon;
            document.getElementById('previewIcon').textContent = icon;
            
            // Update selected state
            document.querySelectorAll('.icon-option').forEach(el => {
                el.classList.remove('selected');
                if (el.textContent.trim() === icon) {
                    el.classList.add('selected');
                }
            });
        }
        //Reports
         // Auto-hide toast messages after 5 seconds
        setTimeout(function() {
            const toast = document.querySelector('.toast-message');
            if (toast) {
                toast.style.display = 'none';
            }
        }, 5000);
        
        // Sync color inputs
        document.getElementById('primary_color').addEventListener('input', function() {
            document.getElementById('primary_color_text').value = this.value;
        });
        
        document.getElementById('primary_color_text').addEventListener('input', function() {
            document.getElementById('primary_color').value = this.value;
        });
        
        document.getElementById('secondary_color').addEventListener('input', function() {
            document.getElementById('secondary_color_text').value = this.value;
        });
        
        document.getElementById('secondary_color_text').addEventListener('input', function() {
            document.getElementById('secondary_color').value = this.value;
        });
        
        // Reset settings to defaults
        function resetSettings() {
            if (confirm('Are you sure you want to reset all settings to default values?')) {
                // Collect all default values
                const defaultValues = {
                    site_name: 'Internet Governance & Awareness Platform',
                    site_tagline: 'Empowering Digital Citizens',
                    site_logo: '🌐',
                    primary_color: '#03a60c',
                    secondary_color: '#028c0a',
                    default_avatar: 'default.png',
                    xp_per_lesson: '50',
                    xp_per_quest: '30',
                    certificate_template: 'default',
                    maintenance_mode: '0',
                    allow_registration: '1',
                    facebook_url: '',
                    twitter_url: '',
                    linkedin_url: '',
                    youtube_url: '',
                    footer_text: '© 2026 Internet Governance & Awareness Platform. All rights reserved.'
                };
                
                // Set form values
                document.getElementById('site_name').value = defaultValues.site_name;
                document.getElementById('site_tagline').value = defaultValues.site_tagline;
                document.getElementById('site_logo').value = defaultValues.site_logo;
                document.getElementById('primary_color').value = defaultValues.primary_color;
                document.getElementById('primary_color_text').value = defaultValues.primary_color;
                document.getElementById('secondary_color').value = defaultValues.secondary_color;
                document.getElementById('secondary_color_text').value = defaultValues.secondary_color;
                document.getElementById('default_avatar').value = defaultValues.default_avatar;
                document.getElementById('xp_per_lesson').value = defaultValues.xp_per_lesson;
                document.getElementById('xp_per_quest').value = defaultValues.xp_per_quest;
                document.getElementById('certificate_template').value = defaultValues.certificate_template;
                document.getElementById('facebook_url').value = defaultValues.facebook_url;
                document.getElementById('twitter_url').value = defaultValues.twitter_url;
                document.getElementById('linkedin_url').value = defaultValues.linkedin_url;
                document.getElementById('youtube_url').value = defaultValues.youtube_url;
                document.getElementById('footer_text').value = defaultValues.footer_text;
                
                // Update checkboxes
                document.querySelector('input[name="maintenance_mode"]').checked = defaultValues.maintenance_mode == '1';
                document.querySelector('input[name="allow_registration"]').checked = defaultValues.allow_registration == '1';
                
                // Update logo preview
                document.getElementById('logoPreview').textContent = defaultValues.site_logo;
                document.getElementById('logoText').textContent = defaultValues.site_logo;
                
                // Show notification
                showToast('Settings reset to defaults. Click "Save All Settings" to apply.', 'info');
            }
        }
        
        // Show toast notification
        function showToast(message, type = 'success') {
            const container = document.querySelector('.toast-message');
            if (container) {
                container.className = `toast-message ${type}`;
                container.innerHTML = `
                    <span>${type === 'success' ? '✅' : type === 'info' ? 'ℹ️' : '❌'}</span>
                    ${message}
                `;
                container.style.display = 'flex';
                
                setTimeout(() => {
                    container.style.display = 'none';
                }, 5000);
            }
        }

        // Auto-hide toast messages after 5 seconds
        setTimeout(function() {
            const toast = document.querySelector('.toast-message');
            if (toast) {
                toast.style.display = 'none';
            }
        }, 5000);
        
        // Toggle message expansion
        function toggleMessage(id) {
            const content = document.getElementById('msg_' + id);
            const btn = content.parentElement.querySelector('.toggle-btn');
            
            if (content.classList.contains('expanded')) {
                content.classList.remove('expanded');
                btn.textContent = 'Show more';
            } else {
                content.classList.add('expanded');
                btn.textContent = 'Show less';
            }
        }
        
        // Open reply modal
        function openReplyModal(id) {
            const modal = document.getElementById('replyModal');
            const feedbackId = document.getElementById('feedback_id');
            feedbackId.value = id;
            
            // Fetch feedback details
            fetch(`../api/get_feedback.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('feedbackUser').textContent = data.user;
                        document.getElementById('feedbackMessage').textContent = data.message;
                        document.getElementById('feedbackDate').textContent = 'Submitted: ' + data.date;
                    }
                })
                .catch(() => {
                    // Fallback - use data from table
                    const row = document.querySelector(`tr.unread:has(.action-btn.reply[onclick*="${id}"])`);
                    if (row) {
                        const name = row.querySelector('.name')?.textContent || 'User';
                        const message = row.querySelector('.feedback-message .content')?.textContent || 'No message';
                        document.getElementById('feedbackUser').textContent = name;
                        document.getElementById('feedbackMessage').textContent = message;
                    }
                });
            
            modal.classList.add('show');
            document.getElementById('reply').value = '';
            document.getElementById('reply').focus();
        }
        
        // Close reply modal
        function closeReplyModal() {
            document.getElementById('replyModal').classList.remove('show');
        }
        
        // Close modal on outside click
        document.getElementById('replyModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeReplyModal();
            }
        });
        
        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeReplyModal();
            }
        });
>>>>>>> Stashed changes
