<?php
require_once("../middleware/user.php");
require_once("../core.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quests - Internet Governance</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>
    <?php require_once("../includes/user_navbar.php"); ?>

    <div class="user-page-container">
        <div class="user-card">
            <h2>🎯 Quests</h2>
            <p>Complete quests to build your knowledge and earn rewards.</p>
            <a href="learn.php" class="btn-secondary">Back to Learn</a>
        </div>
    </div>

    <script src="../javascript/script.js"></script>
</body>
</html>
