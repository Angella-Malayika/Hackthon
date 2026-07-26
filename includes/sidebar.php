<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">

    <div class="logo">

        <div class="logo-circle">🌐</div>

        <h2>INTERNET</h2>

        <span>Governance & Awareness</span>

    </div>

    <ul class="menu">

        <li class="<?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">
            <a href="../user/dashboard.php">🏠 Dashboard</a>
        </li>

        <li class="<?= $currentPage == 'learn.php' ? 'active' : '' ?>">
            <a href="../user/learn.php">📚 Learn</a>
        </li>

        <li class="<?= $currentPage == 'quests.php' ? 'active' : '' ?>">
            <a href="../user/quests.php">🎯 Quests</a>
        </li>

        <li class="<?= $currentPage == 'profile.php' ? 'active' : '' ?>">
            <a href="../user/profile.php">👤 Profile</a>
        </li>

        <li class="<?= $currentPage == 'more.php' ? 'active' : '' ?>">
            <a href="../user/more.php">📖 More</a>
        </li>

    </ul>

    <div class="logout">

        <a href="../auth/logout.php">🚪 Logout</a>

    </div>

</div>

