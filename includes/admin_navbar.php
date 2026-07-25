<?php
// Get admin name from session
$admin_name = $_SESSION['fullname'] ?? 'Admin';
$admin_initial = strtoupper(substr($admin_name, 0, 1));
?>

<div class="admin-topbar">
    <div class="topbar-left">
        <button class="toggle-sidebar" id="toggleSidebar">
            ☰
        </button>
        <span class="page-title"><?php echo $currentPage ? ucfirst(str_replace('.php', '', $currentPage)) : 'Dashboard'; ?></span>
    </div>

    <div class="topbar-right">
        <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">
            <span id="themeIcon">🌙</span>
            <span id="themeLabel">Dark Mode</span>
        </button>

        <!-- Notifications -->
        <div class="notification-wrapper">
            <span class="notification-icon">🔔</span>
            <span class="notification-badge" id="notificationBadge">3</span>
            
            <!-- Notification Dropdown -->
            <div class="notification-dropdown" id="notificationDropdown">
                <div class="dropdown-header">
                    <h4>Notifications</h4>
                    <span class="mark-all-read">Mark all as read</span>
                </div>
                <div class="dropdown-body">
                    <div class="notification-item unread">
                        <span class="notif-icon">📚</span>
                        <div class="notif-content">
                            <p>New lesson added: "Internet Privacy"</p>
                            <small>5 minutes ago</small>
                        </div>
                    </div>
                    <div class="notification-item unread">
                        <span class="notif-icon">👤</span>
                        <div class="notif-content">
                            <p>New user registered: John Doe</p>
                            <small>15 minutes ago</small>
                        </div>
                    </div>
                    <div class="notification-item">
                        <span class="notif-icon">💬</span>
                        <div class="notif-content">
                            <p>New feedback from Sarah</p>
                            <small>1 hour ago</small>
                        </div>
                    </div>
                </div>
                <div class="dropdown-footer">
                    <a href="#">View all notifications</a>
                </div>
            </div>
        </div>

        <!-- Admin Profile -->
        <div class="admin-profile">
            <div class="admin-avatar"><?php echo $admin_initial; ?></div>
            <div class="admin-info">
                <span class="admin-name"><?php echo htmlspecialchars($admin_name); ?></span>
                <span class="admin-role">Administrator</span>
            </div>
        </div>
    </div>
</div>