<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$user_name = $_SESSION['fullname'] ?? 'User';
?>

<nav class="user-navbar">
    <div class="brand-area">
        <span class="brand-logo">🌐</span>
        <div>
            <strong>Internet Governance</strong>
            <small>Adventure Platform</small>
        </div>
    </div>

    <div class="nav-links">
        <a class="<?php echo ($currentPage == 'learn.php') ? 'active' : ''; ?>" href="../user/learn.php">Dashboard</a>
        <a class="<?php echo ($currentPage == 'Quests.php') ? 'active' : ''; ?>" href="../user/Quests.php">Quests</a>
        <a class="<?php echo ($currentPage == 'profile.php') ? 'active' : ''; ?>" href="../user/profile.php">Profile</a>
        <a class="<?php echo ($currentPage == 'more.php') ? 'active' : ''; ?>" href="../user/more.php">More</a>
    </div>

    <button class="theme-toggle" id="themeToggle" data-theme-toggle aria-label="Switch to dark mode">
        <span id="themeIcon">🌙</span>
        <span id="themeLabel">Dark Mode</span>
    </button>

    <div class="user-profile-box">
        <span class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></span>
        <span><?php echo htmlspecialchars($user_name); ?></span>
    </div>
</nav>
