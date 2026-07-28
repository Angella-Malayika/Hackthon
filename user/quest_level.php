<?php
require_once("../middleware/user.php");
require_once("../core.php");
require_once("../includes/quest_content.php");

$user_id = $_SESSION['user_id'];
$questLevels = get_quest_levels();

// Which level? Default/only valid range is 1-8.
$level = isset($_GET['level']) ? intval($_GET['level']) : 1;
if ($level < 1 || $level > 8 || !isset($questLevels[$level])) {
    header("Location: quests.php");
    exit();
}
$data = $questLevels[$level];

// --- Work out how many quest levels this user has already passed, to gate access ---
$passedLevels = [];
$res = $conn->prepare("SELECT quest_level FROM quest_level_progress WHERE user_id = ? AND passed = 1");
$res->bind_param("i", $user_id);
$res->execute();
foreach ($res->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
    $passedLevels[(int)$row['quest_level']] = true;
}
$highestUnlocked = 1;
for ($n = 1; $n <= 8; $n++) {
    if (isset($passedLevels[$n])) {
        $highestUnlocked = max($highestUnlocked, $n + 1);
    }
}
$highestUnlocked = min($highestUnlocked, 8);

if ($level > $highestUnlocked) {
    header("Location: quests.php?locked=" . $level);
    exit();
}

// --- Session state for this specific quest level attempt ---
$sk = "quest_level_{$level}_"; // session key prefix
if (!isset($_SESSION[$sk . 'started'])) {
    $_SESSION[$sk . 'started'] = false;
    $_SESSION[$sk . 'completed'] = false;
}

if (isset($_POST['start_quest_level'])) {
    $_SESSION[$sk . 'started'] = true;
    $_SESSION[$sk . 'completed'] = false;
    header("Location: " . $_SERVER['PHP_SELF'] . "?level=" . $level);
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_quest_level'])) {
    $objectives = $data['objectives'];
    $correctCount = 0;
    $totalObjectives = count($objectives);

    foreach ($objectives as $i => $obj) {
        $key = 'obj' . $i;
        $correctIndex = $obj[2];
        if (isset($_POST[$key]) && intval($_POST[$key]) === $correctIndex) {
            $correctCount++;
        }
    }

    // Scenario: partial credit based on how many "likely possible answer"
    // concept clusters the learner's free-text response touches on.
    [$prompt, $hint, $concepts] = $data['scenario'];
    $scenarioAnswer = trim($_POST['scenario_answer'] ?? '');
    $conceptsHit = 0;
    foreach ($concepts as [$label, $phrases]) {
        $grading = grade_structured_answer($scenarioAnswer, $phrases);
        if ($grading['correct']) $conceptsHit++;
    }
    $maxConcepts = count($concepts);

    $totalScore = $correctCount + $conceptsHit;
    $totalMax = $totalObjectives + $maxConcepts;
    $percentage = $totalMax > 0 ? round(($totalScore / $totalMax) * 100) : 0;

    $passMark = 80;
    $passed = $percentage >= $passMark;

    $_SESSION[$sk . 'completed'] = true;
    $_SESSION[$sk . 'passed'] = $passed;
    $_SESSION[$sk . 'percentage'] = $percentage;
    $_SESSION[$sk . 'correctCount'] = $correctCount;
    $_SESSION[$sk . 'totalObjectives'] = $totalObjectives;
    $_SESSION[$sk . 'conceptsHit'] = $conceptsHit;
    $_SESSION[$sk . 'maxConcepts'] = $maxConcepts;

    // Save/update progress
    $upsert = $conn->prepare("
        INSERT INTO quest_level_progress (user_id, quest_level, score, percentage, passed, completed_at)
        VALUES (?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            score = GREATEST(score, VALUES(score)),
            percentage = GREATEST(percentage, VALUES(percentage)),
            passed = GREATEST(passed, VALUES(passed)),
            completed_at = NOW()
    ");
    $passedInt = $passed ? 1 : 0;
    $upsert->bind_param("iiiii", $user_id, $level, $totalScore, $percentage, $passedInt);
    $upsert->execute();

    $justUnlocked = [];
    if ($passed) {
        $difficultyMultiplier = 1 + (($level - 1) * 0.15); // deeper levels are worth a bit more
        $xpEarned = (int)round((80 + ($correctCount * 6) + ($conceptsHit * 10)) * $difficultyMultiplier);

        $conn->query("UPDATE users SET xp = xp + $xpEarned WHERE id = " . intval($user_id));

        $justUnlocked = evaluate_achievements($conn, $user_id);

        $nextLevel = $level + 1;
        if ($nextLevel <= 8) {
            $message = "🎉 Quest Level $level cleared! You scored $percentage% and earned $xpEarned XP. Quest Level $nextLevel is now unlocked.";
        } else {
            $message = "🏆 Quest Level $level cleared! You scored $percentage% and earned $xpEarned XP. You've completed every quest level!";
        }
        $messageType = "success";
        $_SESSION[$sk . 'xpEarned'] = $xpEarned;
    } else {
        $message = "😅 You scored $percentage%. You need $passMark% to pass this quest level. Review the content and try again!";
        $messageType = "error";
        $_SESSION[$sk . 'completed'] = false; // allow retry
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quest Level <?php echo $level; ?> - <?php echo htmlspecialchars($data['title']); ?></title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php require_once("../includes/sidebar.php"); ?>

    <div class="level-container">
        <?php require_once("../includes/navbar.php"); ?>

        <div class="level-content">
            <div class="level-header">
                <div class="header-left">
                    <h1><?php echo $data['icon']; ?> Quest Level <?php echo $level; ?>: <?php echo htmlspecialchars($data['title']); ?></h1>
                    <p><?php echo htmlspecialchars($data['subtitle']); ?></p>
                </div>
                <div class="header-right">
                    <div class="level-badge">Quest <?php echo $level; ?> / 8</div>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="toast-message <?php echo $messageType; ?>">
                <span><?php echo $messageType == 'success' ? '✅' : '😅'; ?></span>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($justUnlocked)): ?>
            <div class="toast-message success">
                <span>🎉</span>
                Achievement unlocked: <?php echo htmlspecialchars(implode(', ', array_map(fn($a) => $a['title'], $justUnlocked))); ?>!
            </div>
            <?php endif; ?>

            <?php if (!$_SESSION[$sk . 'started'] && !$_SESSION[$sk . 'completed']): ?>
            <div class="intro-section">
                <div class="intro-icon"><?php echo $data['icon']; ?></div>
                <h2>Welcome to Quest Level <?php echo $level; ?>: <?php echo htmlspecialchars($data['title']); ?></h2>
                <p>This quest goes deeper than the matching learning level. Read every section carefully, then answer 10 objectives and one scenario question. You need <strong>80%</strong> overall to pass.</p>
                <p style="margin-top: 20px; color: #666;">Your scenario answer is graded on how many of the likely, expected ideas it touches on - so explain your reasoning, don't just guess a single word.</p>
            </div>

            <div class="intro-section" style="text-align:left;">
                <?php foreach ($data['content'] as $section): ?>
                    <?php [$heading, $paragraphs] = $section; ?>
                    <h2><?php echo htmlspecialchars($heading); ?></h2>
                    <?php foreach ($paragraphs as $p): ?>
                        <p><?php echo htmlspecialchars($p); ?></p>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>

            <form method="POST" action="?level=<?php echo $level; ?>" style="display: inline-block; margin-top: 20px;">
                <input type="hidden" name="start_quest_level" value="1">
                <button type="submit" class="btn-start">🚀 Start Quest Level <?php echo $level; ?></button>
            </form>
            <?php endif; ?>

            <?php if ($_SESSION[$sk . 'started'] && !$_SESSION[$sk . 'completed']): ?>
            <form method="POST" action="?level=<?php echo $level; ?>" id="questForm" class="quiz-form">
                <?php foreach ($data['objectives'] as $i => $obj): ?>
                    <?php [$qText, $options, $correctIndex] = $obj; ?>
                    <div class="question-card" data-question="<?php echo $i + 1; ?>">
                        <div class="question-header">
                            <span class="q-number">Objective <?php echo $i + 1; ?></span>
                            <span class="q-type">Multiple Choice</span>
                        </div>
                        <h3><?php echo htmlspecialchars($qText); ?></h3>
                        <div class="options">
                            <?php foreach ($options as $oi => $optText): ?>
                                <label class="option">
                                    <input type="radio" name="obj<?php echo $i; ?>" value="<?php echo $oi; ?>">
                                    <span class="option-text"><?php echo chr(65 + $oi) . '. ' . htmlspecialchars($optText); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php [$prompt, $hint, $concepts] = $data['scenario']; ?>
                <div class="question-card" data-question="11">
                    <div class="question-header">
                        <span class="q-number">Scenario</span>
                        <span class="q-type">Open Response</span>
                    </div>
                    <h3><?php echo htmlspecialchars($prompt); ?></h3>
                    <p class="hint">Hint: <?php echo htmlspecialchars($hint); ?></p>
                    <textarea class="structured-input" name="scenario_answer" rows="5" style="height:auto; padding:14px;" placeholder="Explain your reasoning in a few sentences..."></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" name="submit_quest_level" class="btn-submit">📤 Submit Quest Level <?php echo $level; ?></button>
                </div>
            </form>
            <?php endif; ?>

            <?php if ($_SESSION[$sk . 'completed'] && isset($_SESSION[$sk . 'passed'])): ?>
            <div class="results-section <?php echo $_SESSION[$sk . 'passed'] ? 'passed' : 'failed'; ?>">
                <div class="results-icon"><?php echo $_SESSION[$sk . 'passed'] ? '🎉' : '😅'; ?></div>
                <h2><?php echo $_SESSION[$sk . 'passed'] ? 'Quest Cleared!' : 'Keep Trying!'; ?></h2>
                <div class="score-display">
                    <div class="score-circle">
                        <span class="score-number"><?php echo $_SESSION[$sk . 'percentage'] ?? 0; ?>%</span>
                    </div>
                </div>
                <p>
                    Objectives: <?php echo $_SESSION[$sk . 'correctCount'] ?? 0; ?> / <?php echo $_SESSION[$sk . 'totalObjectives'] ?? 10; ?> correct
                    &nbsp;|&nbsp;
                    Scenario: <?php echo $_SESSION[$sk . 'conceptsHit'] ?? 0; ?> / <?php echo $_SESSION[$sk . 'maxConcepts'] ?? 3; ?> key ideas covered
                </p>
                <?php if ($_SESSION[$sk . 'passed']): ?>
                    <p class="success-message">🌟 You earned <?php echo $_SESSION[$sk . 'xpEarned'] ?? 0; ?> XP!</p>
                    <a href="quests.php"><button class="btn-retry">← Back to Quests</button></a>
                <?php else: ?>
                    <p class="error-message">You need 80% to pass. Please review the content and try again.</p>
                    <button class="btn-retry" onclick="location.href='?level=<?php echo $level; ?>'">🔄 Try Again</button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../javascript/script.js"></script>
</body>
</html>
