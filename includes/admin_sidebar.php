<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="admin-sidebar">
    <div class="admin-logo">
        <div class="logo-icon">🛡️</div>
        <h2>ADMIN</h2>
        <span>Control Panel</span>
    </div>

    <ul class="admin-menu">
        <li class="<?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">
            <a href="../admin/dashboard.php">
                <span class="menu-icon">📊</span> Dashboard
            </a>
        </li>

        <li class="<?php echo ($currentPage == 'users.php') ? 'active' : ''; ?>">
            <a href="../admin/users.php">
                <span class="menu-icon">👥</span> Users
            </a>
        </li>

        <li class="<?php echo ($currentPage == 'categories.php') ? 'active' : ''; ?>">
            <a href="../admin/categories.php">
                <span class="menu-icon">📂</span> Categories
            </a>
        </li>

        <li class="<?php echo ($currentPage == 'lessons.php') ? 'active' : ''; ?>">
            <a href="../admin/lessons.php">
                <span class="menu-icon">📚</span> Lessons
            </a>
        </li>

        <li class="<?php echo ($currentPage == 'quests.php') ? 'active' : ''; ?>">
            <a href="../admin/quests.php">
                <span class="menu-icon">🎯</span> Quests
            </a>
        </li>

        <li class="<?php echo ($currentPage == 'badges.php') ? 'active' : ''; ?>">
            <a href="../admin/badges.php">
                <span class="menu-icon">🏅</span> Badges
            </a>
        </li>

        <li class="<?php echo ($currentPage == 'reports.php') ? 'active' : ''; ?>">
            <a href="../admin/reports.php">
                <span class="menu-icon">📈</span> Reports
            </a>
        </li>

        <li class="<?php echo ($currentPage == 'feedback.php') ? 'active' : ''; ?>">
            <a href="../admin/feedback.php">
                <span class="menu-icon">💬</span> Feedback
            </a>
        </li>

        <li class="<?php echo ($currentPage == 'settings.php') ? 'active' : ''; ?>">
            <a href="../admin/settings.php">
                <span class="menu-icon">⚙️</span> Settings
            </a>
        </li>
    </ul>

    <div class="admin-logout">
        <a href="../auth/logout.php">
            <span class="menu-icon">🚪</span> Logout
        </a>
    </div>
</div>