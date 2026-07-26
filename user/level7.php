<?php
require_once("../middleware/user.php");
require_once("../core.php");

$user_id = $_SESSION['user_id'];

// Get current user data
$stmt = $conn->prepare("SELECT level, xp FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$currentLevel = $user['level'];
$xp = $user['xp'];

// Check if user has access to Level 7
if ($currentLevel < 7) {
    header("Location: learn.php");
    exit();
}

// Initialize session state
if (!isset($_SESSION['level7_started'])) {
    $_SESSION['level7_started'] = false;
    $_SESSION['level7_completed'] = false;
    $_SESSION['level7_score'] = 0;
    $_SESSION['level7_answers'] = [];
}

// Start level action
if (isset($_POST['start_level7'])) {
    $_SESSION['level7_started'] = true;
    $_SESSION['level7_completed'] = false;
    $_SESSION['level7_score'] = 0;
    $_SESSION['level7_answers'] = [];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_level7'])) {
    $answers = [];
    $score = 0;
    $totalQuestions = 10;

    $mcqAnswers = [
        'q1' => 'a',
        'q2' => 'a',
        'q3' => 'a',
        'q4' => 'b',
        'q5' => 'a',
    ];

    for ($i = 1; $i <= 5; $i++) {
        $questionKey = 'q' . $i;
        if (isset($_POST[$questionKey])) {
            $userAnswer = $_POST[$questionKey];
            $answers[$questionKey] = $userAnswer;
            if (isset($mcqAnswers[$questionKey]) && $userAnswer === $mcqAnswers[$questionKey]) {
                $score++;
            }
        }
    }

    $structuredAnswers = [
        'q6' => ['internet corporation for assigned names and numbers', 'icann'],
        'q7' => ['icann', 'igf', 'ietf', 'itu', 'internet governance forum', 'un'],
        'q8' => ['dialogue', 'discussion platform', 'multi-stakeholder discussion', 'policy discussion', 'forum for discussion'],
        'q9' => ['government', 'private sector', 'civil society', 'technical community', 'academia', 'international organisations'],
        'q10' => ['internet is global', 'cross-border', 'global network', 'shared resource', 'affects everyone worldwide'],
    ];

    for ($i = 6; $i <= 10; $i++) {
        $questionKey = 'q' . $i;
        if (isset($_POST[$questionKey])) {
            $userAnswer = strtolower(trim($_POST[$questionKey]));
            $answers[$questionKey] = $userAnswer;
            if (isset($structuredAnswers[$questionKey])) {
                foreach ($structuredAnswers[$questionKey] as $correct) {
                    if (strpos($userAnswer, $correct) !== false) {
                        $score++;
                        break;
                    }
                }
            }
        }
    }

    $percentage = round(($score / $totalQuestions) * 100);
    $passMark = 80;
    $passed = $percentage >= $passMark;

    $_SESSION['level7_score'] = $score;
    $_SESSION['level7_answers'] = $answers;
    $_SESSION['level7_completed'] = true;
    $_SESSION['level7_percentage'] = $percentage;
    $_SESSION['level7_passed'] = $passed;

    if ($passed) {
        $xpEarned = 170 + ($score * 5);
        $newXp = $xp + $xpEarned;

        // Unlock the next level when the user passes
        $newLevel = $currentLevel;
        if ($currentLevel < 8) {
            $newLevel = 8;
        }

        $stmt = $conn->prepare("UPDATE users SET xp = ?, level = ? WHERE id = ?");
        $stmt->bind_param("iii", $newXp, $newLevel, $user_id);
        $stmt->execute();

        if ($percentage >= 90) {
            $badgeCheck = $conn->prepare("SELECT id FROM user_badges WHERE user_id = ? AND badge_id = 7");
            $badgeCheck->bind_param("i", $user_id);
            $badgeCheck->execute();
            if ($badgeCheck->get_result()->num_rows == 0) {
                $badgeStmt = $conn->prepare("INSERT INTO user_badges (user_id, badge_id, earned_at) VALUES (?, 7, NOW())");
                $badgeStmt->bind_param("i", $user_id);
                $badgeStmt->execute();
            }
        }

        // Log completion in lesson_progress (links this level to the dashboard's progress tracking)
        $lessonRow = $conn->query("SELECT id FROM lessons WHERE title LIKE 'Level 7:%' LIMIT 1")->fetch_assoc();
        if ($lessonRow) {
            $lessonId = $lessonRow['id'];
            $lpStmt = $conn->prepare("INSERT INTO lesson_progress (user_id, lesson_id, status, completed_at)
                VALUES (?, ?, 'completed', NOW())
                ON DUPLICATE KEY UPDATE status = 'completed', completed_at = NOW()");
            $lpStmt->bind_param("ii", $user_id, $lessonId);
            $lpStmt->execute();
        }

        $message = "🎉 Great work! You scored $percentage% and earned $xpEarned XP! Level 8 is now unlocked.";
        $messageType = "success";
    } else {
        $message = "😅 You scored $percentage%. You need $passMark% to pass. Try again!";
        $messageType = "error";
        $_SESSION['level7_completed'] = false;
    }
}

if (isset($_SESSION['level7_completed']) && $_SESSION['level7_completed'] && isset($_SESSION['level7_passed']) && $_SESSION['level7_passed']) {
    echo "<script>
        setTimeout(function() {
            window.location.href = 'learn.php';
        }, 5000);
    </script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Level 7: Internet Governance Structures</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php require_once("../includes/sidebar.php"); ?>
    
    <div class="level-container">
        <?php require_once("../includes/navbar.php"); ?>
        
        <div class="level-content">
            <div class="level-header">
                <div class="header-left">
                    <h1>🏛️ Level 7: Internet Governance Structures</h1>
                    <p>Explore the organisations and policies that keep the Internet running.</p>
                </div>
                <div class="header-right">
                    <div class="level-badge">Level 7</div>
                    <div class="xp-display">⭐ <?php echo $xp; ?> XP</div>
                </div>
            </div>
            
            <div class="progress-section">
                <div class="progress-bar-container">
                    <div class="progress-bar-fill" id="progressBar" style="width: 0%;"></div>
                </div>
                <div class="progress-text">
                    <span id="progressLabel">0% Complete</span>
                    <span id="questionCounter">Question 1 of 10</span>
                </div>
            </div>
            
            <?php if ($message): ?>
            <div class="toast-message <?php echo $messageType; ?>">
                <span><?php echo $messageType == 'success' ? '✅' : '😅'; ?></span>
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!$_SESSION['level7_started'] && !$_SESSION['level7_completed']): ?>
            <div class="intro-section">
                <div class="intro-icon">🏛️</div>
                <h2>Welcome to Level 7: Internet Governance Structures</h2>
                <p>Explore the organisations and policies that keep the Internet running.</p>
                <ul class="intro-list">
                    <li>✅ Understand the multistakeholder model in more depth</li>
                    <li>✅ Learn what ICANN does</li>
                    <li>✅ Understand the role of the Internet Governance Forum (IGF)</li>
                    <li>✅ Learn about technical standards bodies like the IETF</li>
                    <li>✅ Understand the role of civil society and the private sector</li>
                </ul>
                <p style="margin-top: 20px; color: #666;">Read the modules below, then complete the assessment. You need 80% to pass and unlock Level 8.</p>
            </div>

            <div class="intro-section" style="text-align:left;">
                <h2>Module 1: Revisiting the Multistakeholder Model</h2>
                <p>No single entity controls the Internet - it is managed through cooperation between many groups.</p>
                <ul class="intro-list">
                    <li>Governments, private sector, technical community, civil society, and academia all play a role.</li>
                    <li>This model helps keep the Internet open, stable, and globally accessible.</li>
                </ul>

                <h2>Module 2: ICANN and Domain Names</h2>
                <p>ICANN (Internet Corporation for Assigned Names and Numbers) coordinates the Internet&#039;s naming system.</p>
                <ul class="intro-list">
                    <li>It manages domain names (like .com, .org) and IP address allocation.</li>
                    <li>It helps ensure every device and website can be found reliably online.</li>
                </ul>

                <h2>Module 3: The Internet Governance Forum (IGF)</h2>
                <p>The IGF is a global platform for dialogue on Internet policy issues.</p>
                <ul class="intro-list">
                    <li>It brings together governments, companies, civil society, and technical experts.</li>
                    <li>Discussions cover topics like access, security, human rights, and emerging tech.</li>
                </ul>

                <h2>Module 4: Technical Standards Bodies</h2>
                <p>Organisations like the IETF develop the technical standards that make the Internet work.</p>
                <ul class="intro-list">
                    <li>The IETF (Internet Engineering Task Force) develops open technical standards.</li>
                    <li>These standards ensure different networks and devices can communicate with each other.</li>
                </ul>

                <h2>Module 5: Regional &amp; National Initiatives</h2>
                <p>Many countries and regions have their own Internet governance discussions.</p>
                <ul class="intro-list">
                    <li>National IGFs adapt global conversations to local contexts and needs.</li>
                    <li>Regional bodies help coordinate policy across neighbouring countries.</li>
                </ul>

                <h2>Module 6: Civil Society &amp; the Private Sector</h2>
                <p>Both groups play a crucial role in shaping how the Internet is governed.</p>
                <ul class="intro-list">
                    <li>Civil society advocates for digital rights, inclusion, and the public interest.</li>
                    <li>The private sector builds and maintains much of the Internet&#039;s infrastructure and services.</li>
                </ul>

            </div>

            <form method="POST" action="" style="display: inline-block; margin-top: 20px;">
                <input type="hidden" name="start_level7" value="1">
                <button type="submit" class="btn-start">🚀 Start Level</button>
            </form>
            <?php endif; ?>
            
            <?php if ($_SESSION['level7_started'] && !$_SESSION['level7_completed']): ?>
            <form method="POST" action="" id="quizForm" class="quiz-form">

                <div class="question-card" data-question="1">
                    <div class="question-header">
                        <span class="q-number">Question 1</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What does ICANN primarily manage?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q1" value="a">
                            <span class="option-text">A. Domain names and IP address allocation</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="b">
                            <span class="option-text">B. Social media content moderation</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="c">
                            <span class="option-text">C. Email server maintenance</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="d">
                            <span class="option-text">D. Mobile phone networks</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="2">
                    <div class="question-header">
                        <span class="q-number">Question 2</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What is the Internet Governance Forum (IGF)?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q2" value="a">
                            <span class="option-text">A. A global platform for multi-stakeholder dialogue on Internet policy</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="b">
                            <span class="option-text">B. A search engine</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="c">
                            <span class="option-text">C. An antivirus company</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="d">
                            <span class="option-text">D. A social media application</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="3">
                    <div class="question-header">
                        <span class="q-number">Question 3</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What does IETF stand for?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q3" value="a">
                            <span class="option-text">A. Internet Engineering Task Force</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="b">
                            <span class="option-text">B. International Email Task Force</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="c">
                            <span class="option-text">C. Internet Ethics Task Force</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="d">
                            <span class="option-text">D. Information Exchange Task Force</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="4">
                    <div class="question-header">
                        <span class="q-number">Question 4</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>Which model best describes how the Internet is governed?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q4" value="a">
                            <span class="option-text">A. Control by a single government</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="b">
                            <span class="option-text">B. A multistakeholder model involving governments, the private sector, technical community, and civil society</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="c">
                            <span class="option-text">C. No governance exists at all</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="d">
                            <span class="option-text">D. Only technology companies decide the rules</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="5">
                    <div class="question-header">
                        <span class="q-number">Question 5</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What is one role of civil society in Internet governance?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q5" value="a">
                            <span class="option-text">A. Advocating for rights, inclusion, and the public interest</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="b">
                            <span class="option-text">B. Controlling all servers worldwide</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="c">
                            <span class="option-text">C. Setting all laws by themselves</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="d">
                            <span class="option-text">D. Managing domain names alone</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="6">
                    <div class="question-header">
                        <span class="q-number">Question 6</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>What does ICANN stand for?</h3>
                    <p class="hint">Hint: The organisation behind domain names.</p>
                    <input type="text" class="structured-input" name="q6" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="7">
                    <div class="question-header">
                        <span class="q-number">Question 7</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>Name one body involved in Internet governance.</h3>
                    <p class="hint">Hint: ICANN, IGF, IETF, ITU...</p>
                    <input type="text" class="structured-input" name="q7" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="8">
                    <div class="question-header">
                        <span class="q-number">Question 8</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>What is the purpose of the Internet Governance Forum?</h3>
                    <p class="hint">Hint: A dialogue platform.</p>
                    <input type="text" class="structured-input" name="q8" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="9">
                    <div class="question-header">
                        <span class="q-number">Question 9</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>Name one stakeholder group in Internet governance.</h3>
                    <p class="hint">Hint: Government, private sector, civil society...</p>
                    <input type="text" class="structured-input" name="q9" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="10">
                    <div class="question-header">
                        <span class="q-number">Question 10</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>Why is international cooperation important for Internet governance?</h3>
                    <p class="hint">Hint: The Internet is global.</p>
                    <input type="text" class="structured-input" name="q10" placeholder="Type your answer...">
                </div>

                <div class="form-actions">
                    <button type="submit" name="submit_level7" class="btn-submit">📤 Submit Answers</button>
                </div>
            </form>
            <?php endif; ?>
            
            <?php if ($_SESSION['level7_completed'] && isset($_SESSION['level7_passed'])): ?>
            <div class="results-section <?php echo $_SESSION['level7_passed'] ? 'passed' : 'failed'; ?>">
                <div class="results-icon">
                    <?php echo $_SESSION['level7_passed'] ? '🎉' : '😅'; ?>
                </div>
                <h2><?php echo $_SESSION['level7_passed'] ? 'Congratulations!' : 'Keep Trying!'; ?></h2>
                <div class="score-display">
                    <div class="score-circle">
                        <span class="score-number"><?php echo $_SESSION['level7_percentage'] ?? 0; ?>%</span>
                    </div>
                </div>
                <p>You scored <?php echo $_SESSION['level7_score'] ?? 0; ?> out of 10</p>
                <?php if ($_SESSION['level7_passed']): ?>
                    <p class="success-message">🌟 Level 8 is now unlocked! You earned <?php echo 170 + ($_SESSION['level7_score'] * 5); ?> XP!</p>
                    <p style="color: #999; font-size: 14px;">Redirecting to the Learn page in 5 seconds...</p>
                <?php else: ?>
                    <p class="error-message">You need 80% to pass. Please try again.</p>
                    <button class="btn-retry" onclick="location.reload()">🔄 Try Again</button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../javascript/script.js"></script>
</body>
</html>