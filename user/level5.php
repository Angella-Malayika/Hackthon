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

// Check if user has access to Level 5
if ($currentLevel < 5) {
    header("Location: learn.php");
    exit();
}

// Initialize session state
if (!isset($_SESSION['level5_started'])) {
    $_SESSION['level5_started'] = false;
    $_SESSION['level5_completed'] = false;
    $_SESSION['level5_score'] = 0;
    $_SESSION['level5_answers'] = [];
}

// Start level action
if (isset($_POST['start_level5'])) {
    $_SESSION['level5_started'] = true;
    $_SESSION['level5_completed'] = false;
    $_SESSION['level5_score'] = 0;
    $_SESSION['level5_answers'] = [];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_level5'])) {
    $answers = [];
    $score = 0;
    $totalQuestions = 10;

    $mcqAnswers = [
        'q1' => 'b',
        'q2' => 'b',
        'q3' => 'b',
        'q4' => 'a',
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
        'q6' => ['trail of data', 'online activity record', 'record of online actions', 'data trail', 'online history'],
        'q7' => ['identity theft', 'stalking', 'privacy loss', 'safety risk', 'scams', 'exposure', 'burglary'],
        'q8' => ['online harassment', 'bullying online', 'harassment through internet', 'online abuse'],
        'q9' => ['think before posting', 'privacy settings', 'avoid inappropriate content', 'be respectful', 'monitor what you post'],
        'q10' => ['better opportunities', 'safety', 'privacy', 'good reputation', 'avoid risks'],
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

    $_SESSION['level5_score'] = $score;
    $_SESSION['level5_answers'] = $answers;
    $_SESSION['level5_completed'] = true;
    $_SESSION['level5_percentage'] = $percentage;
    $_SESSION['level5_passed'] = $passed;

    if ($passed) {
        $xpEarned = 130 + ($score * 5);
        $newXp = $xp + $xpEarned;

        // Unlock the next level when the user passes
        $newLevel = $currentLevel;
        if ($currentLevel < 6) {
            $newLevel = 6;
        }

        $stmt = $conn->prepare("UPDATE users SET xp = ?, level = ? WHERE id = ?");
        $stmt->bind_param("iii", $newXp, $newLevel, $user_id);
        $stmt->execute();

        if ($percentage >= 90) {
            $badgeCheck = $conn->prepare("SELECT id FROM user_badges WHERE user_id = ? AND badge_id = 5");
            $badgeCheck->bind_param("i", $user_id);
            $badgeCheck->execute();
            if ($badgeCheck->get_result()->num_rows == 0) {
                $badgeStmt = $conn->prepare("INSERT INTO user_badges (user_id, badge_id, earned_at) VALUES (?, 5, NOW())");
                $badgeStmt->bind_param("i", $user_id);
                $badgeStmt->execute();
            }
        }

        // Log completion in lesson_progress (links this level to the dashboard's progress tracking)
        $lessonRow = $conn->query("SELECT id FROM lessons WHERE title LIKE 'Level 5:%' LIMIT 1")->fetch_assoc();
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

        $message = "🎉 Great work! You scored $percentage% and earned $xpEarned XP! Level 6 is now unlocked.";
        $messageType = "success";
    } else {
        $message = "Ooops! You scored $percentage%. You need $passMark% to pass. Try again!";
        $messageType = "error";
        $_SESSION['level5_completed'] = false;
    }
}

if (isset($_SESSION['level5_completed']) && $_SESSION['level5_completed'] && isset($_SESSION['level5_passed']) && $_SESSION['level5_passed']) {
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
    <title>Level 5: Social Media Safety & Digital Footprint</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php require_once("../includes/sidebar.php"); ?>
    
    <div class="level-container">
        <?php require_once("../includes/navbar.php"); ?>
        
        <div class="level-content">
            <div class="level-header">
                <div class="header-left">
                    <h1>📱 Level 5: Social Media Safety & Digital Footprint</h1>
                    <p>Learn how to manage your online presence and reputation.</p>
                </div>
                <div class="header-right">
                    <div class="level-badge">Level 5</div>
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
            
            <?php if (!$_SESSION['level5_started'] && !$_SESSION['level5_completed']): ?>
            <div class="intro-section">
                <div class="intro-icon">📱</div>
                <h2>Welcome to Level 5: Social Media Safety & Digital Footprint</h2>
                <p>Learn how to manage your online presence and reputation.</p>
                <ul class="intro-list">
                    <li>✅ Understand what a digital footprint is</li>
                    <li>✅ Recognise the risks of oversharing</li>
                    <li>✅ Manage social media privacy settings</li>
                    <li>✅ Respond appropriately to cyberbullying</li>
                    <li>✅ Build healthy, safe social media habits</li>
                </ul>
                <p style="margin-top: 20px; color: #666;">Read the modules below, then complete the assessment. You need 70% to pass and unlock Level 6.</p>
            </div>

            <div class="intro-section" style="text-align:left;">
                <h2>Module 1: What is a Digital Footprint?</h2>
                <p>Your digital footprint is the trail of data you leave behind through your online activity.</p>
                <ul class="intro-list">
                    <li>It includes posts, comments, likes, and searches.</li>
                    <li>It can be seen by employers, schools, and even strangers.</li>
                    <li>It can last far longer than you expect, even after deleting a post.</li>
                </ul>

                <h2>Module 2: The Risks of Oversharing</h2>
                <p>Sharing too much personal information online can put you at risk.</p>
                <ul class="intro-list">
                    <li>Sharing your location or daily routine can enable stalking or burglary.</li>
                    <li>Personal details can be used for identity theft or scams.</li>
                    <li>Old posts can resurface and affect your reputation later.</li>
                </ul>

                <h2>Module 3: Managing Privacy Settings</h2>
                <p>Most social platforms let you control who sees your content.</p>
                <ul class="intro-list">
                    <li>Set your profile to private if you only want approved followers.</li>
                    <li>Review who can tag you or comment on your posts.</li>
                    <li>Regularly check what information is visible to the public.</li>
                </ul>

                <h2>Module 4: Cyberbullying Awareness</h2>
                <p>Cyberbullying is harassment or bullying that happens online.</p>
                <ul class="intro-list">
                    <li>It can include mean comments, threats, or spreading rumours.</li>
                    <li>If it happens to you: don&#039;t respond, save evidence, block the person, and report it.</li>
                    <li>Always tell a trusted adult or authority if you experience or witness it.</li>
                </ul>

                <h2>Module 5: Managing Your Online Reputation</h2>
                <p>What you post online contributes to how others see you.</p>
                <ul class="intro-list">
                    <li>Think before you post - would you be comfortable if anyone saw it?</li>
                    <li>Be respectful and avoid posting things in anger.</li>
                    <li>Regularly review your old posts and tagged content.</li>
                </ul>

                <h2>Module 6: Healthy Social Media Habits</h2>
                <p>Balanced use of social media supports both safety and wellbeing.</p>
                <ul class="intro-list">
                    <li>Take regular breaks from social media.</li>
                    <li>Follow accounts that add value, and unfollow ones that don&#039;t.</li>
                    <li>Verify information before sharing it.</li>
                </ul>

            </div>

            <form method="POST" action="" style="display: inline-block; margin-top: 20px;">
                <input type="hidden" name="start_level5" value="1">
                <button type="submit" class="btn-start"> Start Level</button>
            </form>
            <?php endif; ?>
            
            <?php if ($_SESSION['level5_started'] && !$_SESSION['level5_completed']): ?>
            <form method="POST" action="" id="quizForm" class="quiz-form">

                <div class="question-card" data-question="1">
                    <div class="question-header">
                        <span class="q-number">Question 1</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What is a &quot;digital footprint&quot;?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q1" value="a">
                            <span class="option-text">A. Physical footprints left on the ground</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="b">
                            <span class="option-text">B. The trail of data you leave behind through online activity</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="c">
                            <span class="option-text">C. A type of malware</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="d">
                            <span class="option-text">D. A social media app</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="2">
                    <div class="question-header">
                        <span class="q-number">Question 2</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>Which of the following is risky to share publicly online?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q2" value="a">
                            <span class="option-text">A. Your favourite movie</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="b">
                            <span class="option-text">B. Your home address and daily schedule</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="c">
                            <span class="option-text">C. Your favourite colour</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="d">
                            <span class="option-text">D. A general hobby</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="3">
                    <div class="question-header">
                        <span class="q-number">Question 3</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What should you do if you experience cyberbullying?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q3" value="a">
                            <span class="option-text">A. Ignore it and retaliate online</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="b">
                            <span class="option-text">B. Report it, block the person, and tell a trusted adult</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="c">
                            <span class="option-text">C. Share it publicly to get sympathy</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="d">
                            <span class="option-text">D. Delete your account and say nothing</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="4">
                    <div class="question-header">
                        <span class="q-number">Question 4</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>Why should you review your social media privacy settings?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q4" value="a">
                            <span class="option-text">A. To control who can see your content</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="b">
                            <span class="option-text">B. It&#039;s not necessary</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="c">
                            <span class="option-text">C. Settings don&#039;t affect your safety</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="d">
                            <span class="option-text">D. To get more followers automatically</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="5">
                    <div class="question-header">
                        <span class="q-number">Question 5</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What is &quot;oversharing&quot;?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q5" value="a">
                            <span class="option-text">A. Sharing too much personal information online</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="b">
                            <span class="option-text">B. Never posting anything</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="c">
                            <span class="option-text">C. Only sharing with close friends</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="d">
                            <span class="option-text">D. A type of privacy setting</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="6">
                    <div class="question-header">
                        <span class="q-number">Question 6</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>What is a digital footprint?</h3>
                    <input type="text" class="structured-input" name="q6" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="7">
                    <div class="question-header">
                        <span class="q-number">Question 7</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>Name one risk of oversharing online.</h3>
                    <input type="text" class="structured-input" name="q7" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="8">
                    <div class="question-header">
                        <span class="q-number">Question 8</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>What is cyberbullying?</h3>
                    <input type="text" class="structured-input" name="q8" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="9">
                    <div class="question-header">
                        <span class="q-number">Question 9</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>Give one way to protect your online reputation.</h3>
                   
                    <input type="text" class="structured-input" name="q9" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="10">
                    <div class="question-header">
                        <span class="q-number">Question 10</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>Name one benefit of managing your digital footprint well.</h3>
                    <input type="text" class="structured-input" name="q10" placeholder="Type your answer...">
                </div>

                <div class="form-actions">
                    <button type="submit" name="submit_level5" class="btn-submit"> Submit Answers</button>
                </div>
            </form>
            <?php endif; ?>
            
            <?php if ($_SESSION['level5_completed'] && isset($_SESSION['level5_passed'])): ?>
            <div class="results-section <?php echo $_SESSION['level5_passed'] ? 'passed' : 'failed'; ?>">
                <div class="results-icon">
                    <?php echo $_SESSION['level5_passed'] ? '🎉' : ''; ?>
                </div>
                <h2><?php echo $_SESSION['level5_passed'] ? 'Congratulations!' : 'Keep Trying!'; ?></h2>
                <div class="score-display">
                    <div class="score-circle">
                        <span class="score-number"><?php echo $_SESSION['level5_percentage'] ?? 0; ?>%</span>
                    </div>
                </div>
                <p>You scored <?php echo $_SESSION['level5_score'] ?? 0; ?> out of 10</p>
                <?php if ($_SESSION['level5_passed']): ?>
                    <p class="success-message">🌟 Level 6 is now unlocked! You earned <?php echo 130 + ($_SESSION['level5_score'] * 5); ?> XP!</p>
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
