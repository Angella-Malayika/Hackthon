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