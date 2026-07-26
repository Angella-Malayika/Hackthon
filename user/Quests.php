<?php
require_once("../middleware/user.php");
require_once("../core.php");

$user_id = $_SESSION['user_id'];

// Get current XP
$stmt = $conn->prepare("SELECT xp, level FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$xp = $user['xp'];

$message = '';
$messageType = '';

// Handle "Mark as Complete" action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_quest_id'])) {
    $questId = intval($_POST['complete_quest_id']);

    // Make sure the quest exists and is active
    $qStmt = $conn->prepare("SELECT id, title, xp_reward FROM quests WHERE id = ? AND status = 'active'");
    $qStmt->bind_param("i", $questId);
    $qStmt->execute();
    $quest = $qStmt->get_result()->fetch_assoc();

    if ($quest) {
        // Check if already completed
        $checkStmt = $conn->prepare("SELECT id, status FROM user_quests WHERE user_id = ? AND quest_id = ?");
        $checkStmt->bind_param("ii", $user_id, $questId);
        $checkStmt->execute();
        $existing = $checkStmt->get_result()->fetch_assoc();

        if ($existing && $existing['status'] === 'completed') {
            $message = "You've already completed this quest.";
            $messageType = "error";
        } else {
            $upsert = $conn->prepare("INSERT INTO user_quests (user_id, quest_id, status, completed_at)
                VALUES (?, ?, 'completed', NOW())
                ON DUPLICATE KEY UPDATE status = 'completed', completed_at = NOW()");
            $upsert->bind_param("ii", $user_id, $questId);
            $upsert->execute();

            $reward = intval($quest['xp_reward']);
            $newXp = $xp + $reward;
            $xpStmt = $conn->prepare("UPDATE users SET xp = ? WHERE id = ?");
            $xpStmt->bind_param("ii", $newXp, $user_id);
            $xpStmt->execute();

            $xp = $newXp;
            $message = "🎉 Quest completed! You earned $reward XP.";
            $messageType = "success";
        }
    }
}

// Fetch all active quests along with this user's status for each
$quests = [];
$result = $conn->query("
    SELECT q.id, q.title, q.description, q.xp_reward,
           uq.status AS user_status
    FROM quests q
    LEFT JOIN user_quests uq ON uq.quest_id = q.id AND uq.user_id = $user_id
    WHERE q.status = 'active' OR q.status IS NULL
    ORDER BY (uq.status = 'completed') ASC, q.created_at DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $quests[] = $row;
    }
}

$totalQuests = count($quests);
$completedCount = 0;
foreach ($quests as $q) {
    if (($q['user_status'] ?? '') === 'completed') $completedCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quests</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php require_once("../includes/sidebar.php"); ?>

    <div class="page-container">
        <?php require_once("../includes/navbar.php"); ?>

        <div class="page-content">
            <div class="page-header">
                <h1>🧭 Quests</h1>
                <p>Complete quests to earn bonus XP alongside your level progress.</p>
            </div>

            <?php if ($message): ?>
            <div class="toast-message <?php echo $messageType; ?>">
                <span><?php echo $messageType == 'success' ? '✅' : '⚠️'; ?></span>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <div class="quests-stats">
                <div class="stat-card">
                    <h2><?php echo $completedCount; ?>/<?php echo $totalQuests; ?></h2>
                    <p>Quests Completed</p>
                </div>
                <div class="stat-card">
                    <h2>⭐ <?php echo $xp; ?></h2>
                    <p>Total XP</p>
                </div>
                <div class="stat-card">
                    <h2>🏅 Level <?php echo $user['level']; ?></h2>
                    <p>Current Level</p>
                </div>
            </div>

            <div class="quests-grid">
                <?php if (empty($quests)): ?>
                    <div class="no-quests">No quests are available right now. Check back soon!</div>
                <?php else: ?>
                    <?php foreach ($quests as $q): ?>
                        <?php $isDone = ($q['user_status'] ?? '') === 'completed'; ?>
                        <div class="quest-card <?php echo $isDone ? 'completed' : ''; ?>">
                            <h3><?php echo htmlspecialchars($q['title']); ?></h3>
                            <p><?php echo htmlspecialchars($q['description']); ?></p>
                            <div class="quest-footer">
                                <span class="quest-xp">+<?php echo intval($q['xp_reward']); ?> XP</span>
                                <span class="quest-status <?php echo $isDone ? 'completed' : 'pending'; ?>">
                                    <?php echo $isDone ? '✔ Completed' : 'Pending'; ?>
                                </span>
                            </div>
                            <?php if (!$isDone): ?>
                            <form method="POST" action="">
                                <input type="hidden" name="complete_quest_id" value="<?php echo $q['id']; ?>">
                                <button type="submit" class="btn-quest">Mark as Complete</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../javascript/script.js"></script>
</body>
</html>
