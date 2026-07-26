<?php
require_once("../middleware/admin.php");
require_once("../core.php");

$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: users.php");
    exit();
}

// --- Level-by-level progress (based on lesson_progress for the 8 seeded level lessons) ---
$levelProgress = [];
for ($i = 1; $i <= 8; $i++) {
    $like = "Level $i:%";
    $stmt = $conn->prepare("
        SELECT l.title, lp.status, lp.completed_at
        FROM lessons l
        LEFT JOIN lesson_progress lp ON lp.lesson_id = l.id AND lp.user_id = ?
        WHERE l.title LIKE ?
        LIMIT 1
    ");
    $stmt->bind_param("is", $userId, $like);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $levelProgress[$i] = [
        'title' => $row['title'] ?? "Level $i",
        'status' => $row['status'] ?? null,
        'completed_at' => $row['completed_at'] ?? null,
        'unlocked' => $user['level'] >= $i,
    ];
}

// --- Badges earned ---
$stmt = $conn->prepare("
    SELECT b.name, b.image, b.requirement, ub.earned_at
    FROM user_badges ub JOIN badges b ON b.id = ub.badge_id
    WHERE ub.user_id = ? ORDER BY ub.earned_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$badges = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// --- Quests ---
$stmt = $conn->prepare("
    SELECT q.title, q.xp_reward, uq.status, uq.completed_at
    FROM user_quests uq JOIN quests q ON q.id = uq.quest_id
    WHERE uq.user_id = ? ORDER BY uq.completed_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$quests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// --- Certificates ---
$stmt = $conn->prepare("SELECT certificate_no, issued_at FROM certificates WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$certificates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// --- Feedback submitted by this user ---
$stmt = $conn->prepare("SELECT subject, message, status, created_at FROM feedback WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $userId);
$stmt->execute();
$feedbackItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$completedLevels = 0;
foreach ($levelProgress as $lp) {
    if ($lp['status'] === 'completed') $completedLevels++;
}
$progressPct = round(($completedLevels / 8) * 100);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress - <?php echo htmlspecialchars($user['fullname']); ?></title>
    <link rel="stylesheet" href="../assets/admin.css">
    <style>
        .up-header{display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px;}
        .up-profile{display:flex; align-items:center; gap:16px; background:#fff; padding:20px; border-radius:15px; box-shadow:0 5px 15px rgba(0,0,0,.08); margin-bottom:20px;}
        .up-avatar{width:64px; height:64px; border-radius:50%; background:#03a60c; color:#fff; display:flex; align-items:center; justify-content:center; font-size:26px; font-weight:700;}
        .up-stats{display:flex; gap:15px; flex-wrap:wrap; margin-bottom:20px;}
        .up-stat{flex:1; min-width:140px; background:#fff; padding:18px; border-radius:12px; box-shadow:0 5px 15px rgba(0,0,0,.08); text-align:center;}
        .up-stat h3{margin:0; color:#03a60c; font-size:24px;}
        .up-stat p{margin:4px 0 0; color:#777; font-size:13px;}
        .up-section{background:#fff; padding:20px 25px; border-radius:15px; box-shadow:0 5px 15px rgba(0,0,0,.08); margin-bottom:20px;}
        .up-section h3{margin-top:0;}
        .level-row{display:flex; align-items:center; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f0f0f0;}
        .level-row:last-child{border-bottom:none;}
        .lvl-tag{width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; color:#fff;}
        .lvl-tag.completed{background:#03a60c;}
        .lvl-tag.locked{background:#ccc;}
        .lvl-tag.in-progress{background:#f59e0b;}
        .up-pill{padding:3px 12px; border-radius:20px; font-size:12px; font-weight:600;}
        .up-pill.completed{background:#d4edda; color:#155724;}
        .up-pill.locked{background:#eee; color:#999;}
        .up-pill.pending{background:#fff3cd; color:#856404;}
        .empty-note{color:#999; font-size:14px;}
    </style>
</head>
<body>
    <?php require_once("../includes/admin_sidebar.php"); ?>

    <div class="admin-content">
        <?php require_once("../includes/admin_navbar.php"); ?>

        <div style="margin-top: 20px;">
            <div class="up-header">
                <h2 style="margin:0; color:#1a1a2e;">User Progress</h2>
                <div style="display:flex; gap:10px;">
                    <a href="user_form.php?id=<?php echo $user['id']; ?>" class="btn-secondary" style="padding:8px 20px; font-size:14px;">✏️ Edit User</a>
                    <a href="users.php" class="btn-secondary" style="padding:8px 20px; font-size:14px;">← Back to Users</a>
                </div>
            </div>

            <div class="up-profile">
                <div class="up-avatar"><?php echo strtoupper(substr($user['fullname'],0,1)); ?></div>
                <div>
                    <h3 style="margin:0;"><?php echo htmlspecialchars($user['fullname']); ?></h3>
                    <p style="margin:2px 0; color:#777;"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p style="margin:0; color:#999; font-size:13px;">
                        Status: <?php echo ucfirst($user['status']); ?> ·
                        Last login: <?php echo $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?>
                    </p>
                </div>
            </div>

            <div class="up-stats">
                <div class="up-stat"><h3><?php echo $completedLevels; ?>/8</h3><p>Levels Completed</p></div>
                <div class="up-stat"><h3><?php echo $progressPct; ?>%</h3><p>Course Progress</p></div>
                <div class="up-stat"><h3>⭐ <?php echo $user['xp']; ?></h3><p>Total XP</p></div>
                <div class="up-stat"><h3>🏅 <?php echo count($badges); ?></h3><p>Badges Earned</p></div>
                <div class="up-stat"><h3>🎓 <?php echo count($certificates); ?></h3><p>Certificates</p></div>
            </div>

            <div class="up-section">
                <h3>📊 Level-by-Level Progress</h3>
                <?php foreach ($levelProgress as $i => $lp): ?>
                    <?php
                        if ($lp['status'] === 'completed') { $tagClass = 'completed'; $pillClass='completed'; $pillText='Completed'; }
                        elseif ($lp['unlocked']) { $tagClass = 'in-progress'; $pillClass='pending'; $pillText='In Progress'; }
                        else { $tagClass = 'locked'; $pillClass='locked'; $pillText='Locked'; }
                    ?>
                    <div class="level-row">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div class="lvl-tag <?php echo $tagClass; ?>"><?php echo $i; ?></div>
                            <span><?php echo htmlspecialchars($lp['title']); ?></span>
                        </div>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <?php if ($lp['completed_at']): ?>
                                <span style="font-size:12px; color:#999;"><?php echo date('M j, Y', strtotime($lp['completed_at'])); ?></span>
                            <?php endif; ?>
                            <span class="up-pill <?php echo $pillClass; ?>"><?php echo $pillText; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="up-section">
                <h3>🏅 Badges Earned</h3>
                <?php if (empty($badges)): ?>
                    <p class="empty-note">No badges earned yet.</p>
                <?php else: ?>
                    <?php foreach ($badges as $b): ?>
                        <div class="level-row">
                            <span><?php echo htmlspecialchars($b['image']); ?> <?php echo htmlspecialchars($b['name']); ?></span>
                            <span style="font-size:12px; color:#999;"><?php echo date('M j, Y', strtotime($b['earned_at'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="up-section">
                <h3>🧭 Quests</h3>
                <?php if (empty($quests)): ?>
                    <p class="empty-note">No quest activity yet.</p>
                <?php else: ?>
                    <?php foreach ($quests as $q): ?>
                        <div class="level-row">
                            <span><?php echo htmlspecialchars($q['title']); ?> <small style="color:#999;">(+<?php echo $q['xp_reward']; ?> XP)</small></span>
                            <span class="up-pill <?php echo $q['status'] === 'completed' ? 'completed' : 'pending'; ?>"><?php echo ucfirst($q['status']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="up-section">
                <h3>💬 Recent Feedback</h3>
                <?php if (empty($feedbackItems)): ?>
                    <p class="empty-note">This user hasn't submitted any feedback.</p>
                <?php else: ?>
                    <?php foreach ($feedbackItems as $f): ?>
                        <div class="level-row" style="align-items:flex-start;">
                            <div>
                                <strong><?php echo htmlspecialchars($f['subject'] ?: 'General Feedback'); ?></strong>
                                <p style="margin:4px 0 0; color:#666; font-size:13px;"><?php echo htmlspecialchars(mb_strimwidth($f['message'], 0, 120, '...')); ?></p>
                            </div>
                            <span class="up-pill <?php echo $f['status'] === 'replied' ? 'completed' : 'pending'; ?>"><?php echo ucfirst($f['status']); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <a href="feedback.php" style="font-size:13px;">View all feedback →</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
