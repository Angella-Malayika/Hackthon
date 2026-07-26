<?php
require_once("../middleware/user.php");
require_once("../core.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>More</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php require_once("../includes/sidebar.php"); ?>

    <div class="page-container">
        <?php require_once("../includes/navbar.php"); ?>

        <div class="page-content">
            <div class="page-header">
                <h1>⋯ More</h1>
                <p>Everything else you might need.</p>
            </div>

            <div class="more-links">
                <a href="profile.php" class="more-link-card">
                    <span class="icon">👤</span>
                    <h4>My Profile</h4>
                    <p>Update your details and change your password.</p>
                </a>
                <a href="certificates.php" class="more-link-card">
                    <span class="icon">🎓</span>
                    <h4>My Certificates</h4>
                    <p>View certificates earned by completing the course.</p>
                </a>
                <a href="feedback.php" class="more-link-card">
                    <span class="icon">💬</span>
                    <h4>Feedback</h4>
                    <p>Send feedback or questions to the admin team.</p>
                </a>
                <a href="quests.php" class="more-link-card">
                    <span class="icon">🧭</span>
                    <h4>Quests</h4>
                    <p>Complete quests to earn bonus XP.</p>
                </a>
                <a href="learn.php" class="more-link-card">
                    <span class="icon">📚</span>
                    <h4>Learn</h4>
                    <p>Continue your 8-level learning journey.</p>
                </a>
                <a href="../auth/logout.php" class="more-link-card">
                    <span class="icon">🚪</span>
                    <h4>Log Out</h4>
                    <p>Sign out of your account.</p>
                </a>
            </div>
        </div>
    </div>

    <script src="../javascript/script.js"></script>
</body>
</html>
