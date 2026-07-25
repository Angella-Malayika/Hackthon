<?php
require_once("../middleware/user.php");
require_once("../core.php");

$user_name = $_SESSION['fullname'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learn - Internet Governance</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>
    <?php require_once("../includes/user_navbar.php"); ?>

    <div class="user-page-container">
        <div class="user-hero-card">
            <h1>🌐 Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
            <p>Continue your journey through internet governance, privacy, and digital safety.</p>
        </div>

        <div class="user-grid">
            <div class="user-card">
                <h3>📚 Continue Learning</h3>
                <p>Explore lessons, complete activities, and strengthen your digital awareness.</p>
                <a href="more.php" class="btn-primary">Open Lessons</a>
            </div>

            <div class="user-card">
                <h3>🎯 Your Quests</h3>
                <p>Stay active and complete quests to earn XP and unlock your next level.</p>
                <a href="Quests.php" class="btn-primary">View Quests</a>
            </div>

            <div class="user-card">
                <h3>👤 Your Profile</h3>
                <p>Track your progress, XP, and account details from your personal dashboard.</p>
                <a href="profile.php" class="btn-primary">Open Profile</a>
            </div>

            <div class="user-card">
                <h3>🌍 Explore More</h3>
                <p>Visit additional resources and continue your internet governance learning path.</p>
                <a href="more.php" class="btn-primary">Open More</a>
            </div>
        </div>
    </div>

    <script src="../javascript/script.js"></script>
</body>
</html>
