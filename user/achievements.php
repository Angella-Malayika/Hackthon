<?php
require_once("../middleware/user.php");
require_once("../core.php");
require_once("../includes/achievements.php");

$user_id = $_SESSION['user_id'];

// Catch up on anything newly unlocked before rendering
evaluate_achievements($conn, $user_id);

$stmt = $conn->prepare("SELECT xp, level FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// All 100 achievements, with this user's unlocked status, grouped by category
$stmt = $conn->prepare("
    SELECT a.*, (ua.status = 'completed') AS is_unlocked, ua.completed_at
    FROM achievements a
    LEFT JOIN user_achievements ua ON ua.achievement_id = a.id AND ua.user_id = ?
    ORDER BY a.criteria_type ASC, a.criteria_value ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$all = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$grouped = [];
$totalUnlocked = 0;
$totalXpFromAch = 0;
foreach ($all as $row) {
    $label = achievement_category_label($row['criteria_type']);
    $grouped[$label][] = $row;
    if ($row['is_unlocked']) {
        $totalUnlocked++;
        $totalXpFromAch += (int) $row['xp_reward'];
    }
}
ksort($grouped);

$totalCount = count($all);
$filter = $_GET['filter'] ?? 'all'; // all | unlocked | locked
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Achievements - Internet Governance Platform</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php require_once("../includes/sidebar.php"); ?>

    <div class="main">
        <?php require_once("../includes/navbar.php"); ?>

        <div class="content">
            <div class="dashboard-hero">
                <h1> Achievements</h1>
                <p>Unlock all 100 achievements by learning, questing, and staying active on the platform.</p>
            </div>

            <div class="ach-summary-bar">
                <div class="stat-card green">
                    <div class="number"><?php echo $totalUnlocked; ?>/<?php echo $totalCount; ?></div>
                    <div class="label">Unlocked</div>
                </div>
                <div class="stat-card gold">
                    <div class="number"><?php echo round(($totalUnlocked / max(1,$totalCount)) * 100); ?>%</div>
                    <div class="label">Completion</div>
                </div>
                <div class="stat-card blue">
                    <div class="number"><?php echo $totalXpFromAch; ?></div>
                    <div class="label">XP from Achievements</div>
                </div>
                <div class="stat-card purple">
                    <div class="number"><?php echo (int)$user['xp']; ?></div>
                    <div class="label">Total XP</div>
                </div>
            </div>

            <div class="quick-actions" style="margin-bottom: 10px;">
                <a href="?filter=all" class="action-btn <?php echo $filter=='all'?'primary':'secondary'; ?>">All</a>
                <a href="?filter=unlocked" class="action-btn <?php echo $filter=='unlocked'?'primary':'secondary'; ?>">Unlocked</a>
                <a href="?filter=locked" class="action-btn <?php echo $filter=='locked'?'primary':'secondary'; ?>">Locked</a>
            </div>

            <?php foreach ($grouped as $category => $items): ?>
                <?php
                    $visible = array_filter($items, function($it) use ($filter) {
                        if ($filter === 'unlocked') return (bool)$it['is_unlocked'];
                        if ($filter === 'locked') return !$it['is_unlocked'];
                        return true;
                    });
                    if (empty($visible)) continue;
                ?>
                <h3 class="ach-category-title"> <?php echo htmlspecialchars($category); ?></h3>
                <div class="ach-grid">
                    <?php foreach ($visible as $a): ?>
                        <div class="ach-card <?php echo $a['is_unlocked'] ? 'unlocked' : 'locked'; ?>">
                            <div class="ach-icon-wrap"><?php echo htmlspecialchars($a['icon']); ?></div>
                            <div class="ach-body">
                                <h4><?php echo htmlspecialchars($a['title']); ?></h4>
                                <p><?php echo htmlspecialchars($a['description']); ?></p>
                                <div class="ach-footer">
                                    <span class="difficulty-tag <?php echo htmlspecialchars($a['difficulty']); ?>"><?php echo htmlspecialchars($a['difficulty']); ?></span>
                                    <span class="ach-xp">+<?php echo (int)$a['xp_reward']; ?> XP</span>
                                    <span class="ach-status">
                                        <?php echo $a['is_unlocked'] ? '✅ ' . date('M j, Y', strtotime($a['completed_at'])) : '🔒 Locked'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="../javascript/script.js"></script>
</body>
</html>
