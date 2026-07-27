<?php
require_once("../middleware/user.php");
require_once("../core.php");

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id, certificate_no, issued_at FROM certificates WHERE user_id = ? ORDER BY issued_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$certs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$nameStmt = $conn->prepare("SELECT fullname, level FROM users WHERE id = ?");
$nameStmt->bind_param("i", $user_id);
$nameStmt->execute();
$user = $nameStmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Certificates</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php require_once("../includes/sidebar.php"); ?>

    <div class="page-container">
        <?php require_once("../includes/navbar.php"); ?>

        <div class="page-content">
            <div class="page-header">
                <h1>🎓 My Certificates</h1>
                <p>Earned by completing all 8 levels of the learning path.</p>
            </div>

            <?php if (empty($certs)): ?>
                <div class="no-quests">
                    You don't have a certificate yet.
                    <?php if ($user['level'] < 8): ?>
                        Keep going — you're on Level <?php echo $user['level']; ?> of 8. Finish Level 8 to earn your certificate!
                    <?php else: ?>
                        Complete Level 8's assessment to unlock it.
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="cert-grid">
                    <?php foreach ($certs as $c): ?>
                        <div class="cert-card">
                            <div class="cert-icon">🏆</div>
                            <h3>Course Completion Certificate</h3>
                            <p>Awarded to <strong><?php echo htmlspecialchars($user['fullname']); ?></strong></p>
                            <p style="color:#888; font-size:13px;">Issued <?php echo date('F j, Y', strtotime($c['issued_at'])); ?></p>
                            <div class="cert-no">Certificate No: <?php echo htmlspecialchars($c['certificate_no']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../javascript/script.js"></script>
</body>
</html>
