<?php
require_once("../middleware/user.php");
require_once("../core.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>More - Internet Governance</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>
    <?php require_once("../includes/user_navbar.php"); ?>

    <div class="user-page-container">
        <div class="user-card">
            <h2>🌍 About the System</h2>
            <p>This platform is designed to help users learn internet governance, privacy, cyber awareness, and responsible digital citizenship.</p>
            <p>The system works in a simple flow:</p>
            <ul>
                <li><strong>Register or log in</strong> to create your account.</li>
                <li><strong>Open the dashboard</strong> to see your learning progress and quick links.</li>
                <li><strong>Complete lessons and quests</strong> to gain XP and move forward.</li>
                <li><strong>Visit your profile</strong> to track your learning journey.</li>
                <li><strong>Use the admin panel</strong> to manage users, lessons, categories, badges, reports, and settings.</li>
            </ul>
        </div>

        <div class="user-card">
            <h2>⚙️ How to Access the Settings Panel</h2>
            <p>If you are logged in as the administrator, you can open the settings panel by going to:</p>
            <p><strong>admin/settings.php</strong></p>
            <p>You can also reach it from the admin sidebar by selecting <strong>Settings</strong> from the left menu.</p>
            <p>Once opened, you can update:</p>
            <ul>
                <li>site name and tagline</li>
                <li>theme mode</li>
                <li>primary and secondary colors</li>
                <li>XP values</li>
                <li>social links</li>
                <li>footer text</li>
                <li>maintenance and registration options</li>
            </ul>
            <a href="../admin/settings.php" class="btn-primary">Open Admin Settings</a>
            <a href="learn.php" class="btn-secondary">Back to Learn</a>
        </div>

        <div class="user-card">
            <div class="theme-setting-card">
                <div>
                    <h2>🌗 Theme Preferences</h2>
                    <p>Switch between light and dark mode instantly. Your preference is saved automatically for your next visit.</p>
                </div>
                <button type="button" class="theme-toggle-card" data-theme-toggle aria-label="Switch to dark mode">
                    <span class="theme-toggle-label" data-theme-label>Dark Mode</span>
                    <span class="theme-toggle-switch" aria-hidden="true">
                        <span class="theme-toggle-thumb"></span>
                    </span>
                </button>
            </div>
        </div>
    </div>

    <script src="../javascript/script.js"></script>
</body>
</html>
