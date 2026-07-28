<?php
require_once("../middleware/user.php");
require_once("../core.php");
require_once("../includes/grading.php");
require_once("../includes/achievements.php");

$user_id = $_SESSION['user_id'];

// Get current user data
$stmt = $conn->prepare("SELECT level, xp FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$currentLevel = $user['level'];
$xp = $user['xp'];

// Check if user has access to Level 6
if ($currentLevel < 6) {
    header("Location: learn.php");
    exit();
}

// Initialize session state
if (!isset($_SESSION['level6_started'])) {
    $_SESSION['level6_started'] = false;
    $_SESSION['level6_completed'] = false;
    $_SESSION['level6_score'] = 0;
    $_SESSION['level6_answers'] = [];
}

// Start level action
if (isset($_POST['start_level6'])) {
    $_SESSION['level6_started'] = true;
    $_SESSION['level6_completed'] = false;
    $_SESSION['level6_score'] = 0;
    $_SESSION['level6_answers'] = [];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_level6'])) {
    $answers = [];
    $score = 0;
    $totalQuestions = 10;

    $mcqAnswers = [
        'q1' => 'a',
        'q2' => 'a',
        'q3' => 'b',
        'q4' => 'b',
        'q5' => 'b',
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
        'q6' => ['false information', 'incorrect information', 'inaccurate information', 'wrong information'],
        'q7' => ['fact-check', 'check the source', 'verify', 'cross-check', 'reliable source', 'fact-checking website'],
        'q8' => ['credible source', 'verified source', 'official source', 'trusted source', 'reputable source'],
        'q9' => ['panic', 'confusion', 'distrust', 'harm', 'damage reputation', 'spread fear'],
        'q10' => ['verify it', 'check facts', 'research', 'confirm', 'fact-check first'],
    ];

    for ($i = 6; $i <= 10; $i++) {
        $questionKey = 'q' . $i;
        if (isset($_POST[$questionKey])) {
            $userAnswer = strtolower(trim($_POST[$questionKey]));
            $answers[$questionKey] = $userAnswer;
            if (isset($structuredAnswers[$questionKey])) {
                if (is_close_answer($userAnswer, $structuredAnswers[$questionKey])) {
                    $score++;
                }
            }
        }
    }

    $percentage = round(($score / $totalQuestions) * 100);
    $passMark = 70;
    $passed = $percentage >= $passMark;

    $_SESSION['level6_score'] = $score;
    $_SESSION['level6_answers'] = $answers;
    $_SESSION['level6_completed'] = true;
    $_SESSION['level6_percentage'] = $percentage;
    $_SESSION['level6_passed'] = $passed;

    if ($passed) {
        $xpEarned = 150 + ($score * 5);
        $newXp = $xp + $xpEarned;

        // Unlock the next level when the user passes
        $newLevel = $currentLevel;
        if ($currentLevel < 7) {
            $newLevel = 7;
        }

        $stmt = $conn->prepare("UPDATE users SET xp = ?, level = ? WHERE id = ?");
        $stmt->bind_param("iii", $newXp, $newLevel, $user_id);
        $stmt->execute();

        if ($percentage >= 90) {
            $badgeCheck = $conn->prepare("SELECT id FROM user_badges WHERE user_id = ? AND badge_id = 6");
            $badgeCheck->bind_param("i", $user_id);
            $badgeCheck->execute();
            if ($badgeCheck->get_result()->num_rows == 0) {
                $badgeStmt = $conn->prepare("INSERT INTO user_badges (user_id, badge_id, earned_at) VALUES (?, 6, NOW())");
                $badgeStmt->bind_param("i", $user_id);
                $badgeStmt->execute();
            }
        }

        // Log completion in lesson_progress (links this level to the dashboard's progress tracking)
        $lessonRow = $conn->query("SELECT id FROM lessons WHERE title LIKE 'Level 6:%' LIMIT 1")->fetch_assoc();
        if ($lessonRow) {
            $lessonId = $lessonRow['id'];
            $lpStmt = $conn->prepare("INSERT INTO lesson_progress (user_id, lesson_id, status, completed_at)
                VALUES (?, ?, 'completed', NOW())
                ON DUPLICATE KEY UPDATE status = 'completed', completed_at = NOW()");
            $lpStmt->bind_param("ii", $user_id, $lessonId);
            $lpStmt->execute();
            $scoreStmt = $conn->prepare("UPDATE lesson_progress SET score = ? WHERE user_id = ? AND lesson_id = ?");
            $scoreStmt->bind_param("iii", $percentage, $user_id, $lessonId);
            $scoreStmt->execute();
        }

        // Evaluate achievements (lesson/level completion, XP, badges, etc.) and award XP for any newly unlocked ones
        evaluate_achievements($conn, $user_id);

        $message = "🎉 Great work! You scored $percentage% and earned $xpEarned XP! Level 7 is now unlocked.";
        $messageType = "success";
    } else {
        $message = "ooops! You scored $percentage%. You need $passMark% to pass. Try again!";
        $messageType = "error";
        $_SESSION['level6_completed'] = false;
    }
}

if (isset($_SESSION['level6_completed']) && $_SESSION['level6_completed'] && isset($_SESSION['level6_passed']) && $_SESSION['level6_passed']) {
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
    <title>Level 6: Misinformation & Digital Literacy</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php require_once("../includes/sidebar.php"); ?>
    
    <div class="level-container">
        <?php require_once("../includes/navbar.php"); ?>
        
        <div class="level-content">
            <div class="level-header">
                <div class="header-left">
                    <h1>🔍 Level 6: Misinformation & Digital Literacy</h1>
                    <p>Learn how to spot fake news and evaluate information critically.</p>
                </div>
                <div class="header-right">
                    <div class="level-badge">Level 6</div>
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
                <span><?php echo $messageType == 'success' ? '✅' : ''; ?></span>
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!$_SESSION['level6_started'] && !$_SESSION['level6_completed']): ?>
            <div class="intro-section">
                <div class="intro-icon">🔍</div>
                <h2>Welcome to Level 6: Misinformation & Digital Literacy</h2>
                <p>Learn how to spot fake news and evaluate information critically.</p>
                <ul class="intro-list">
                    <li>✅ Tell the difference between misinformation and disinformation</li>
                    <li>✅ Learn techniques for spotting fake news</li>
                    <li>✅ Understand deepfakes and manipulated media</li>
                    <li>✅ Recognise echo chambers</li>
                    <li>✅ Practise responsible sharing habits</li>
                </ul>
                <p style="margin-top: 20px; color: #666;">Read the modules below, then complete the assessment. You need 70% to pass and unlock Level 7.</p>
            </div>

            <div class="intro-section" style="text-align:left;">
                <h2>Module 1: Misinformation vs Disinformation</h2>
                <p>Both spread false information, but the intent behind them differs.</p>
                <ul class="intro-list">
                    <li>Misinformation is false information shared without intent to deceive.</li>
                    <li>Disinformation is false information deliberately created to mislead.</li>
                    <li>Both can spread quickly through social media.</li>
                </ul>

                <h2>Module 2: Spotting Fake News</h2>
                <p>A few checks can help you evaluate whether a story is trustworthy.</p>
                <ul class="intro-list">
                    <li>Check the source - is it a known, credible outlet?</li>
                    <li>Look for supporting evidence from other reliable sources.</li>
                    <li>Be wary of sensational headlines designed to provoke strong reactions.</li>
                </ul>

                <h2>Module 3: Fact-Checking Techniques</h2>
                <p>Verifying information takes just a few extra steps.</p>
                <ul class="intro-list">
                    <li>Use fact-checking websites to confirm claims.</li>
                    <li>Search for the same story on multiple credible outlets.</li>
                    <li>Check the date - old stories are sometimes recirculated as if new.</li>
                </ul>

                <h2>Module 4: Deepfakes &amp; Manipulated Media</h2>
                <p>Technology can now create highly realistic fake videos and audio.</p>
                <ul class="intro-list">
                    <li>A deepfake uses AI to make a video or audio recording look/sound real when it isn&#039;t.</li>
                    <li>Look for unnatural movements, mismatched audio, or unusual lighting as warning signs.</li>
                </ul>

                <h2>Module 5: Echo Chambers</h2>
                <p>Online platforms can trap us in environments that reinforce only our own views.</p>
                <ul class="intro-list">
                    <li>An echo chamber is where you mostly see opinions that match your own.</li>
                    <li>This can make misinformation feel more believable and normal.</li>
                    <li>Actively seeking varied, credible sources helps balance your perspective.</li>
                </ul>

                <h2>Module 6: Being a Responsible Sharer</h2>
                <p>Before sharing, a moment of caution can prevent the spread of false information.</p>
                <ul class="intro-list">
                    <li>Verify before you share, especially anything sensational.</li>
                    <li>Correct yourself politely if you accidentally shared something false.</li>
                    <li>Encourage others to fact-check too.</li>
                </ul>

            </div>

            <form method="POST" action="" style="display: inline-block; margin-top: 20px;">
                <input type="hidden" name="start_level6" value="1">
                <button type="submit" class="btn-start"> Start Level</button>
            </form>
            <?php endif; ?>
            
            <?php if ($_SESSION['level6_started'] && !$_SESSION['level6_completed']): ?>
            <form method="POST" action="" id="quizForm" class="quiz-form">

                <div class="question-card" data-question="1">
                    <div class="question-header">
                        <span class="q-number">Question 1</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What is &quot;misinformation&quot;?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q1" value="a">
                            <span class="option-text">A. False information shared without intent to deceive</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="b">
                            <span class="option-text">B. All news reporting</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="c">
                            <span class="option-text">C. Only information from government sources</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="d">
                            <span class="option-text">D. A type of computer virus</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="2">
                    <div class="question-header">
                        <span class="q-number">Question 2</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What is &quot;disinformation&quot;?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q2" value="a">
                            <span class="option-text">A. False information deliberately spread to mislead people</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="b">
                            <span class="option-text">B. Accurate, verified reporting</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="c">
                            <span class="option-text">C. A social media app</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="d">
                            <span class="option-text">D. A type of software update</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="3">
                    <div class="question-header">
                        <span class="q-number">Question 3</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What is a good first step before sharing a news story online?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q3" value="a">
                            <span class="option-text">A. Share it immediately to be first</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="b">
                            <span class="option-text">B. Verify the source and check the facts</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="c">
                            <span class="option-text">C. Ignore the headline entirely</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="d">
                            <span class="option-text">D. Share it to as many people as possible</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="4">
                    <div class="question-header">
                        <span class="q-number">Question 4</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What is a &quot;deepfake&quot;?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q4" value="a">
                            <span class="option-text">A. A very deep, thoughtful online post</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="b">
                            <span class="option-text">B. AI-generated fake video or audio that looks/sounds real</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="c">
                            <span class="option-text">C. A type of firewall</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="d">
                            <span class="option-text">D. A genuine, unedited interview</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="5">
                    <div class="question-header">
                        <span class="q-number">Question 5</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What is an &quot;echo chamber&quot;?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q5" value="a">
                            <span class="option-text">A. A room designed for sound effects</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="b">
                            <span class="option-text">B. An environment where you mostly see opinions that reinforce your own</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="c">
                            <span class="option-text">C. A search engine</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="d">
                            <span class="option-text">D. A type of malware</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="6">
                    <div class="question-header">
                        <span class="q-number">Question 6</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>What is misinformation?</h3>
                    <input type="text" class="structured-input" name="q6" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="7">
                    <div class="question-header">
                        <span class="q-number">Question 7</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>Name one way to check if a news story is true.</h3>
                    <input type="text" class="structured-input" name="q7" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="8">
                    <div class="question-header">
                        <span class="q-number">Question 8</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>What is a reliable source of information?</h3>
                    <input type="text" class="structured-input" name="q8" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="9">
                    <div class="question-header">
                        <span class="q-number">Question 9</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>Give one danger of spreading misinformation.</h3>
                    <input type="text" class="structured-input" name="q9" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="10">
                    <div class="question-header">
                        <span class="q-number">Question 10</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>What should you do before sharing a sensational headline?</h3>
                    <input type="text" class="structured-input" name="q10" placeholder="Type your answer...">
                </div>

                <div class="form-actions">
                    <button type="submit" name="submit_level6" class="btn-submit"> Submit Answers</button>
                </div>
            </form>
            <?php endif; ?>
            
            <?php if ($_SESSION['level6_completed'] && isset($_SESSION['level6_passed'])): ?>
            <div class="results-section <?php echo $_SESSION['level6_passed'] ? 'passed' : 'failed'; ?>">
                <div class="results-icon">
                    <?php echo $_SESSION['level6_passed'] ? '🎉' : ''; ?>
                </div>
                <h2><?php echo $_SESSION['level6_passed'] ? 'Congratulations!' : 'Keep Trying!'; ?></h2>
                <div class="score-display">
                    <div class="score-circle">
                        <span class="score-number"><?php echo $_SESSION['level6_percentage'] ?? 0; ?>%</span>
                    </div>
                </div>
                <p>You scored <?php echo $_SESSION['level6_score'] ?? 0; ?> out of 10</p>
                <?php if ($_SESSION['level6_passed']): ?>
                    <p class="success-message">🌟 Level 7 is now unlocked! You earned <?php echo 150 + ($_SESSION['level6_score'] * 5); ?> XP!</p>
                    <p style="color: #999; font-size: 14px;">Redirecting to the Learn page in 5 seconds...</p>
                <?php else: ?>
                    <p class="error-message">You need 70% to pass. Please try again.</p>
                    <button class="btn-retry" onclick="location.reload()"> Try Again</button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../javascript/script.js"></script>
</body>
</html>
