<?php
require_once("../middleware/user.php");
require_once("../core.php");
require_once("../includes/grading.php");
require_once("../includes/achievements.php");
require_once("../includes/quest_content.php");

$user_id = $_SESSION['user_id'];

// Current user XP/level
$stmt = $conn->prepare("SELECT xp, level FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userRow = $stmt->get_result()->fetch_assoc();
$xp = (int) $userRow['xp'];

$questLevels = get_quest_levels();

// Which quest levels has this user already completed?
$completedQuestLevels = [];
$res = $conn->prepare("SELECT quest_level, score FROM quest_progress WHERE user_id = ? AND status = 'completed'");
$res->bind_param("i", $user_id);
$res->execute();
$rows = $res->get_result();
while ($r = $rows->fetch_assoc()) {
    $completedQuestLevels[(int) $r['quest_level']] = (int) $r['score'];
}
$highestUnlocked = 1;
for ($i = 1; $i <= 8; $i++) {
    if (isset($completedQuestLevels[$i]) && $highestUnlocked == $i) {
        $highestUnlocked = $i + 1;
    }
}
if ($highestUnlocked > 8) $highestUnlocked = 8;

$message = '';
$messageType = '';

// ------------------------------------------------------------------
// Legacy "bonus quest" mark-as-complete handling (admin-managed quests)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_quest_id'])) {
    $questId = intval($_POST['complete_quest_id']);
    $qStmt = $conn->prepare("SELECT id, title, xp_reward FROM quests WHERE id = ? AND status = 'active'");
    $qStmt->bind_param("i", $questId);
    $qStmt->execute();
    $quest = $qStmt->get_result()->fetch_assoc();

    if ($quest) {
        $checkStmt = $conn->prepare("SELECT id, status FROM user_quests WHERE user_id = ? AND quest_id = ?");
        $checkStmt->bind_param("ii", $user_id, $questId);
        $checkStmt->execute();
        $existing = $checkStmt->get_result()->fetch_assoc();

        if ($existing && $existing['status'] === 'completed') {
            $message = "You've already completed this bonus quest.";
            $messageType = "error";
        } else {
            $upsert = $conn->prepare("INSERT INTO user_quests (user_id, quest_id, status, completed_at)
                VALUES (?, ?, 'completed', NOW())
                ON DUPLICATE KEY UPDATE status = 'completed', completed_at = NOW()");
            $upsert->bind_param("ii", $user_id, $questId);
            $upsert->execute();

            $reward = intval($quest['xp_reward']);
            $xpStmt = $conn->prepare("UPDATE users SET xp = xp + ? WHERE id = ?");
            $xpStmt->bind_param("ii", $reward, $user_id);
            $xpStmt->execute();
            $xp += $reward;

            evaluate_achievements($conn, $user_id);

            $message = "🎉 Bonus quest completed! You earned $reward XP.";
            $messageType = "success";
        }
    }
}

// ------------------------------------------------------------------
// Which quest level are we viewing?
// ------------------------------------------------------------------
$viewLevel = isset($_GET['level']) ? intval($_GET['level']) : 0;
if ($viewLevel < 1 || $viewLevel > 8 || !isset($questLevels[$viewLevel])) {
    $viewLevel = 0; // 0 = map view
}

// Guard: can't view a level you haven't unlocked yet
if ($viewLevel > 0 && $viewLevel > $highestUnlocked) {
    header("Location: quests.php");
    exit();
}

$sessionKeyStarted   = "quest{$viewLevel}_started";
$sessionKeyCompleted = "quest{$viewLevel}_completed";
$sessionKeyResult    = "quest{$viewLevel}_result";

if ($viewLevel > 0) {
    if (!isset($_SESSION[$sessionKeyStarted])) {
        $_SESSION[$sessionKeyStarted] = false;
        $_SESSION[$sessionKeyCompleted] = isset($completedQuestLevels[$viewLevel]);
        $_SESSION[$sessionKeyResult] = null;
    }

    // Start the challenge
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['start_quest_level'])) {
        $_SESSION[$sessionKeyStarted] = true;
        $_SESSION[$sessionKeyCompleted] = false;
        $_SESSION[$sessionKeyResult] = null;
        header("Location: quests.php?level=$viewLevel");
        exit();
    }

    // Submit the challenge (10 objectives + 1 scenario)
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_quest_level'])) {
        $content = $questLevels[$viewLevel];
        $objectivePoints = 0;
        $objectiveResults = [];
        foreach ($content['objectives'] as $idx => $obj) {
            $qKey = 'obj' . ($idx + 1);
            $userChoice = $_POST[$qKey] ?? null;
            $isCorrect = ($userChoice === $obj['correct']);
            if ($isCorrect) $objectivePoints += 8; // 10 questions x 8 = 80
            $objectiveResults[] = $isCorrect;
        }

        $scenarioAnswer = trim($_POST['scenario_answer'] ?? '');
        $scenarioRaw = grade_scenario_answer($scenarioAnswer, $content['scenario']['points']); // 0-100
        $scenarioPoints = (int) round(($scenarioRaw / 100) * 20); // scaled to 20

        $totalPercentage = min(100, $objectivePoints + $scenarioPoints);
        $passed = $totalPercentage >= 80;

        $_SESSION[$sessionKeyCompleted] = true;
        $_SESSION[$sessionKeyStarted] = false;
        $_SESSION[$sessionKeyResult] = [
            'percentage' => $totalPercentage,
            'passed' => $passed,
            'objective_correct' => array_sum($objectiveResults),
            'scenario_score' => $scenarioRaw,
        ];

        if ($passed) {
            $baseXp = 30 + ($viewLevel * 10); // escalates: 40, 50, ... 110
            $xpEarned = $baseXp + (int) round($totalPercentage / 5);

            $upsert = $conn->prepare("
                INSERT INTO quest_progress (user_id, quest_level, status, score, completed_at)
                VALUES (?, ?, 'completed', ?, NOW())
                ON DUPLICATE KEY UPDATE status = 'completed', score = GREATEST(score, VALUES(score)), completed_at = NOW()
            ");
            $upsert->bind_param("iii", $user_id, $viewLevel, $totalPercentage);
            $upsert->execute();

            $xpStmt = $conn->prepare("UPDATE users SET xp = xp + ? WHERE id = ?");
            $xpStmt->bind_param("ii", $xpEarned, $user_id);
            $xpStmt->execute();
            $xp += $xpEarned;

            $completedQuestLevels[$viewLevel] = $totalPercentage;
            if ($highestUnlocked == $viewLevel) $highestUnlocked = min(8, $viewLevel + 1);

            $newlyUnlocked = evaluate_achievements($conn, $user_id);

            $_SESSION[$sessionKeyResult]['xp_earned'] = $xpEarned;
        } else {
            // allow retry
            $_SESSION[$sessionKeyCompleted] = false;
        }
    }
}

// Fetch legacy bonus quests for the map view
$legacyQuests = [];
$result = $conn->query("
    SELECT q.id, q.title, q.description, q.xp_reward, uq.status AS user_status
    FROM quests q
    LEFT JOIN user_quests uq ON uq.quest_id = q.id AND uq.user_id = " . intval($user_id) . "
    WHERE q.status = 'active' OR q.status IS NULL
    ORDER BY (uq.status = 'completed') ASC, q.created_at DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $legacyQuests[] = $row;
    }
}
$totalLegacy = count($legacyQuests);
$completedLegacy = 0;
foreach ($legacyQuests as $q) {
    if (($q['user_status'] ?? '') === 'completed') $completedLegacy++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quests - Internet Governance Platform</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .quest-map-container{ max-width: 900px; margin: 0 auto; }
        .quest-path{ display:flex; flex-direction:column; align-items:center; gap:28px; padding: 20px 0; }
        .quest-node{
            width:100%; max-width: 620px; display:flex; align-items:center; gap:18px;
            background:white; border-radius:16px; padding:18px 22px;
            box-shadow:0 4px 16px rgba(0,0,0,.06); text-decoration:none; transition:.25s;
            border: 2px solid transparent;
        }
        .quest-node.unlocked{ border-color:#d4edda; }
        .quest-node.unlocked:hover{ transform: translateY(-3px); box-shadow:0 8px 22px rgba(3,166,12,.18); }
        .quest-node.completed{ border-color:#03a60c; background:#f5fff5; }
        .quest-node.locked{ opacity:.55; cursor:not-allowed; }
        .quest-node .qn-icon{
            width:56px; height:56px; border-radius:50%; display:flex; align-items:center; justify-content:center;
            font-size:28px; background:#f0fdf1; flex:0 0 auto;
        }
        .quest-node.locked .qn-icon{ background:#f1f1f1; }
        .quest-node .qn-body{ flex:1; min-width:0; }
        .quest-node .qn-body h3{ margin:0 0 4px; color:#1a1a2e; font-size:16px; }
        .quest-node .qn-body p{ margin:0; color:#888; font-size:13px; }
        .quest-node .qn-status{ flex:0 0 auto; font-size:13px; font-weight:700; color:#03a60c; }
        .quest-node.locked .qn-status{ color:#999; }
        .quest-path-line{ width:4px; height:26px; background:#d4edda; }

        .quest-reading{ background:white; border-radius:16px; padding:28px 32px; box-shadow:0 4px 16px rgba(0,0,0,.06); margin-bottom:24px; }
        .quest-reading h3{ color:#03a60c; margin-top:22px; }
        .quest-reading h3:first-child{ margin-top:0; }
        .quest-reading p{ color:#444; line-height:1.7; }
        .scenario-box{ background:#fffaf0; border-left:4px solid #f59e0b; border-radius:10px; padding:18px 22px; margin-top:18px; }
        .scenario-box h4{ margin-top:0; color:#b8860b; }
        .scenario-textarea{ width:100%; min-height:120px; max-width:100%; padding:14px; border:2px solid #ddd; border-radius:8px; font-size:15px; font-family:inherit; resize:vertical; }
        .scenario-textarea:focus{ border-color:#03a60c; outline:none; box-shadow:0 0 0 3px rgba(3,166,12,.1); }
        .obj-breakdown{ display:flex; flex-wrap:wrap; gap:8px; margin: 16px 0; justify-content:center; }
        .obj-pill{ width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; color:white; }
        .obj-pill.correct{ background:#22c55e; }
        .obj-pill.wrong{ background:#e5534b; }
        .back-link{ display:inline-block; margin-bottom:16px; color:#03a60c; text-decoration:none; font-weight:600; }
    </style>
</head>
<body>
    <?php require_once("../includes/sidebar.php"); ?>

    <div class="page-container">
        <?php require_once("../includes/navbar.php"); ?>

        <div class="page-content">

            <?php if ($viewLevel === 0): ?>
                <!-- ============== QUEST MAP VIEW ============== -->
                <div class="page-header">
                    <h1> Quests</h1>
                    <p>Deep-dive challenges for each Learning level. Read the material, complete 10 objectives, then answer a scenario question. Score 80%+ to pass and unlock the next quest.</p>
                </div>

                <?php if ($message): ?>
                <div class="toast-message <?php echo $messageType; ?>">
                    <span><?php echo $messageType == 'success' ? '✅' : '⚠️'; ?></span>
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <div class="quests-stats">
                    <div class="stat-card">
                        <h2><?php echo count($completedQuestLevels); ?>/8</h2>
                        <p>Quest Levels Passed</p>
                    </div>
                    <div class="stat-card">
                        <h2><?php echo $xp; ?></h2>
                        <p>Total XP</p>
                    </div>
                    <div class="stat-card">
                        <h2>Level <?php echo (int) $userRow['level']; ?></h2>
                        <p>Current Learning Level</p>
                    </div>
                </div>

                <div class="quest-map-container">
                    <div class="quest-path">
                        <?php for ($i = 1; $i <= 8; $i++):
                            $isCompleted = isset($completedQuestLevels[$i]);
                            $isUnlocked = $i <= $highestUnlocked;
                            $qc = $questLevels[$i];
                        ?>
                            <?php if ($isUnlocked): ?>
                            <a href="quests.php?level=<?php echo $i; ?>" class="quest-node <?php echo $isCompleted ? 'completed' : 'unlocked'; ?>">
                                <div class="qn-icon"><?php echo $isCompleted ? '✅' : $qc['icon']; ?></div>
                                <div class="qn-body">
                                    <h3><?php echo htmlspecialchars($qc['title']); ?></h3>
                                    <p><?php echo htmlspecialchars($qc['topic']); ?></p>
                                </div>
                                <div class="qn-status"><?php echo $isCompleted ? $completedQuestLevels[$i] . '%' : 'Start →'; ?></div>
                            </a>
                            <?php else: ?>
                            <div class="quest-node locked">
                                <div class="qn-icon">🔒</div>
                                <div class="qn-body">
                                    <h3><?php echo htmlspecialchars($qc['title']); ?></h3>
                                    <p>Complete the previous quest level to unlock.</p>
                                </div>
                                <div class="qn-status">Locked</div>
                            </div>
                            <?php endif; ?>
                            <?php if ($i < 8): ?><div class="quest-path-line"></div><?php endif; ?>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Bonus Quests (admin-managed) -->
                <div class="page-header" style="margin-top:36px;">
                    <h1 style="font-size:22px;"> Bonus Quests</h1>
                    <p>Extra quests posted by the platform team. Complete them any time for bonus XP.</p>
                </div>

            <?php else: ?>
                <!-- ============== QUEST LEVEL DETAIL VIEW ============== -->
                <?php $content = $questLevels[$viewLevel]; ?>
                <a href="quests.php" class="back-link">← Back to Quests</a>

                <div class="level-header" style="margin-bottom:20px;">
                    <div class="header-left">
                        <h1><?php echo htmlspecialchars($content['icon'] . ' ' . $content['title']); ?></h1>
                        <p>Deep-dive quest based on: <?php echo htmlspecialchars($content['topic']); ?></p>
                    </div>
                    <div class="header-right">
                        <div class="level-badge">Quest <?php echo $viewLevel; ?></div>
                        <div class="xp-display">⭐ <?php echo $xp; ?> XP</div>
                    </div>
                </div>

                <?php if (!$_SESSION[$sessionKeyStarted] && !$_SESSION[$sessionKeyCompleted]): ?>
                    <!-- Reading material -->
                    <div class="quest-reading">
                        <?php echo $content['reading']; ?>
                    </div>
                    <form method="POST" action="quests.php?level=<?php echo $viewLevel; ?>">
                        <input type="hidden" name="start_quest_level" value="1">
                        <button type="submit" class="btn-start">Begin Challenge (10 Objectives + Scenario)</button>
                    </form>

                <?php elseif ($_SESSION[$sessionKeyStarted] && !$_SESSION[$sessionKeyCompleted]): ?>
                    <!-- Challenge form -->
                    <form method="POST" action="quests.php?level=<?php echo $viewLevel; ?>" class="quiz-form">
                        <?php foreach ($content['objectives'] as $idx => $obj): $n = $idx + 1; ?>
                            <div class="question-card" data-question="<?php echo $n; ?>">
                                <div class="question-header">
                                    <span class="q-number">Objective <?php echo $n; ?></span>
                                    <span class="q-type">Multiple Choice</span>
                                </div>
                                <h3><?php echo htmlspecialchars($obj['q']); ?></h3>
                                <div class="options">
                                    <?php foreach ($obj['options'] as $letter => $text): ?>
                                    <label class="option">
                                        <input type="radio" name="obj<?php echo $n; ?>" value="<?php echo $letter; ?>" required>
                                        <span class="option-text"><?php echo strtoupper($letter) . '. ' . htmlspecialchars($text); ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="scenario-box">
                            <h4></h4>Scenario Challenge</h4>
                            <p><?php echo htmlspecialchars($content['scenario']['prompt']); ?></p>
                            <textarea class="scenario-textarea" name="scenario_answer" placeholder="Write your response here (a few sentences)..." required></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="submit_quest_level" class="btn-submit"> Submit Quest</button>
                        </div>
                    </form>

                <?php elseif ($_SESSION[$sessionKeyCompleted]): ?>
                    <?php $result = $_SESSION[$sessionKeyResult]; ?>
                    <div class="results-section <?php echo $result['passed'] ? 'passed' : 'failed'; ?>">
                        <div class="results-icon"><?php echo $result['passed'] ? '🎉' : 'sorry , try again!'; ?></div>
                        <h2><?php echo $result['passed'] ? 'Quest Passed!' : 'Not Quite - Try Again!'; ?></h2>
                        <div class="score-display">
                            <div class="score-circle">
                                <span class="score-number"><?php echo $result['percentage']; ?>%</span>
                            </div>
                        </div>
                        <p><?php echo $result['objective_correct']; ?>/10 objectives correct · Scenario score: <?php echo $result['scenario_score']; ?>%</p>
                        <?php if ($result['passed']): ?>
                            <p class="success-message">🌟 You earned <?php echo $result['xp_earned'] ?? 0; ?> XP! <?php echo $viewLevel < 8 ? 'The next quest level is now unlocked.' : 'You have completed every Quest level!'; ?></p>
                            <a href="quests.php"><button class="btn-retry" type="button">← Back to Quests</button></a>
                        <?php else: ?>
                            <p class="error-message">You need 80% to pass this quest. Review the material and try again.</p>
                            <form method="POST" action="quests.php?level=<?php echo $viewLevel; ?>">
                                <input type="hidden" name="start_quest_level" value="1">
                                <button type="submit" class="btn-retry">🔄 Try Again</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </div>

    <script src="../javascript/script.js"></script>
</body>
</html>
