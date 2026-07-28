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

// Check if user has access to Level 3
if ($currentLevel < 3) {
    header("Location: learn.php");
    exit();
}

// Initialize session state
if (!isset($_SESSION['level3_started'])) {
    $_SESSION['level3_started'] = false;
    $_SESSION['level3_completed'] = false;
    $_SESSION['level3_score'] = 0;
    $_SESSION['level3_answers'] = [];
}

// Start level action
if (isset($_POST['start_level3'])) {
    $_SESSION['level3_started'] = true;
    $_SESSION['level3_completed'] = false;
    $_SESSION['level3_score'] = 0;
    $_SESSION['level3_answers'] = [];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_level3'])) {
    $answers = [];
    $score = 0;
    $totalQuestions = 10;

    $mcqAnswers = [
        'q1' => 'a',
        'q2' => 'b',
        'q3' => 'b',
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
        'q6' => ['control over personal information', 'right to control', 'protecting personal data', 'keep information private', 'controlling what you share'],
        'q7' => ['cookies', 'tracking', 'forms', 'apps', 'social media', 'browsing history', 'sign-up forms'],
        'q8' => ['document', 'policy explains', 'how data is used', 'data usage', 'terms', 'explains how data is collected'],
        'q9' => ['privacy settings', 'strong password', 'limit sharing', 'two-factor', 'avoid public wifi', 'read privacy policy', 'review permissions'],
        'q10' => ['prevent misuse', 'protect identity', 'avoid identity theft', 'security', 'trust', 'safety', 'prevent scams'],
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

    $_SESSION['level3_score'] = $score;
    $_SESSION['level3_answers'] = $answers;
    $_SESSION['level3_completed'] = true;
    $_SESSION['level3_percentage'] = $percentage;
    $_SESSION['level3_passed'] = $passed;

    if ($passed) {
        $xpEarned = 90 + ($score * 5);
        $newXp = $xp + $xpEarned;

        // Unlock the next level when the user passes
        $newLevel = $currentLevel;
        if ($currentLevel < 4) {
            $newLevel = 4;
        }

        $stmt = $conn->prepare("UPDATE users SET xp = ?, level = ? WHERE id = ?");
        $stmt->bind_param("iii", $newXp, $newLevel, $user_id);
        $stmt->execute();

        if ($percentage >= 90) {
            $badgeCheck = $conn->prepare("SELECT id FROM user_badges WHERE user_id = ? AND badge_id = 3");
            $badgeCheck->bind_param("i", $user_id);
            $badgeCheck->execute();
            if ($badgeCheck->get_result()->num_rows == 0) {
                $badgeStmt = $conn->prepare("INSERT INTO user_badges (user_id, badge_id, earned_at) VALUES (?, 3, NOW())");
                $badgeStmt->bind_param("i", $user_id);
                $badgeStmt->execute();
            }
        }

        // Log completion in lesson_progress (links this level to the dashboard's progress tracking)
        $lessonRow = $conn->query("SELECT id FROM lessons WHERE title LIKE 'Level 3:%' LIMIT 1")->fetch_assoc();
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

        $message = "🎉 Great work! You scored $percentage% and earned $xpEarned XP! Level 4 is now unlocked.";
        $messageType = "success";
    } else {
        $message = "😅 You scored $percentage%. You need $passMark% to pass. Try again!";
        $messageType = "error";
        $_SESSION['level3_completed'] = false;
    }
}

if (isset($_SESSION['level3_completed']) && $_SESSION['level3_completed'] && isset($_SESSION['level3_passed']) && $_SESSION['level3_passed']) {
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
    <title>Level 3: Online Privacy & Data Protection</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php require_once("../includes/sidebar.php"); ?>
    
    <div class="level-container">
        <?php require_once("../includes/navbar.php"); ?>
        
        <div class="level-content">
            <div class="level-header">
                <div class="header-left">
                    <h1>🔒 Level 3: Online Privacy & Data Protection</h1>
                    <p>Learn how personal data is collected, used, and protected online.</p>
                </div>
                <div class="header-right">
                    <div class="level-badge">Level 3</div>
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
            
            <?php if (!$_SESSION['level3_started'] && !$_SESSION['level3_completed']): ?>
            <div class="intro-section">
                <div class="intro-icon">🔒</div>
                <h2>Welcome to Level 3: Online Privacy & Data Protection</h2>
                <p>Learn how personal data is collected, used, and protected online.</p>
                <ul class="intro-list">
                    <li>✅ Define personal data and online privacy</li>
                    <li>✅ Understand how companies collect and use your data</li>
                    <li>✅ Learn about privacy settings and app permissions</li>
                    <li>✅ Understand basic data protection principles</li>
                    <li>✅ Apply practical tips to protect your privacy online</li>
                </ul>
                <p style="margin-top: 20px; color: #666;">Read the modules below, then complete the assessment. You need 70% to pass and unlock Level 4.</p>
            </div>

            <div class="intro-section" style="text-align:left;">
                <h2>Module 1: What is Online Privacy?</h2>
                <p>Online privacy is your ability to control what personal information you share and who can access it. It covers everything from your name and email to your location and browsing habits.</p>
                <ul class="intro-list">
                    <li>Privacy means having control over your personal information.</li>
                    <li>It affects how safe and comfortable you feel online.</li>
                    <li>Loss of privacy can lead to identity theft, scams, or harassment.</li>
                </ul>

                <h2>Module 2: Types of Personal Data</h2>
                <p>Personal data includes anything that can identify you, directly or indirectly.</p>
                <ul class="intro-list">
                    <li>Basic details: name, email, phone number.</li>
                    <li>Sensitive data: location, financial info, biometric data (fingerprints, face ID).</li>
                    <li>Behavioural data: browsing history, likes, search history.</li>
                </ul>

                <h2>Module 3: How Companies Collect Data</h2>
                <p>Websites and apps gather data in many ways, often without users noticing.</p>
                <ul class="intro-list">
                    <li>Cookies track your browsing activity across sites.</li>
                    <li>Sign-up forms collect the details you type in.</li>
                    <li>Apps request permissions to access contacts, location, or camera.</li>
                    <li>Social media tracks likes, shares, and time spent on content.</li>
                </ul>

                <h2>Module 4: Privacy Settings &amp; Permissions</h2>
                <p>Most platforms let you control what you share and with whom.</p>
                <ul class="intro-list">
                    <li>Review app permissions regularly and remove ones you don&#039;t need.</li>
                    <li>Set social media profiles to private where possible.</li>
                    <li>Turn off location sharing when it isn&#039;t necessary.</li>
                </ul>

                <h2>Module 5: Data Protection Principles</h2>
                <p>Good data protection laws (such as GDPR) are built on a few key principles.</p>
                <ul class="intro-list">
                    <li>Consent: companies should ask before collecting your data.</li>
                    <li>Purpose limitation: data should only be used for the reason it was collected.</li>
                    <li>Security: companies must protect the data they hold.</li>
                </ul>

                <h2>Module 6: Protecting Your Privacy</h2>
                <p>A few simple habits can make a big difference in protecting your privacy online.</p>
                <ul class="intro-list">
                    <li>Read privacy policies before agreeing to them.</li>
                    <li>Limit how much personal information you share publicly.</li>
                    <li>Use strong, unique passwords and enable extra security where offered.</li>
                    <li>Think twice before granting an app full access to your data.</li>
                </ul>

            </div>

            <form method="POST" action="" style="display: inline-block; margin-top: 20px;">
                <input type="hidden" name="start_level3" value="1">
                <button type="submit" class="btn-start">🚀 Start Level</button>
            </form>
            <?php endif; ?>
            
            <?php if ($_SESSION['level3_started'] && !$_SESSION['level3_completed']): ?>
            <form method="POST" action="" id="quizForm" class="quiz-form">

                <div class="question-card" data-question="1">
                    <div class="question-header">
                        <span class="q-number">Question 1</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What best describes &quot;personal data&quot;?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q1" value="a">
                            <span class="option-text">A. Any information that can identify an individual</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="b">
                            <span class="option-text">B. Only your full name</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="c">
                            <span class="option-text">C. Only your bank details</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="d">
                            <span class="option-text">D. Only information that is already public</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="2">
                    <div class="question-header">
                        <span class="q-number">Question 2</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What are &quot;cookies&quot; in a web browsing context?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q2" value="a">
                            <span class="option-text">A. A type of computer virus</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="b">
                            <span class="option-text">B. Small files that track your browsing activity</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="c">
                            <span class="option-text">C. A kind of online advertisement</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="d">
                            <span class="option-text">D. A social media feature</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="3">
                    <div class="question-header">
                        <span class="q-number">Question 3</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What is the safest way to share personal information online?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q3" value="a">
                            <span class="option-text">A. Share it with everyone, always</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="b">
                            <span class="option-text">B. Only share it when necessary, with trusted and verified sites</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="c">
                            <span class="option-text">C. Share it with random strangers to be friendly</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="d">
                            <span class="option-text">D. Post it publicly so it&#039;s easy to find</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="4">
                    <div class="question-header">
                        <span class="q-number">Question 4</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>Data protection laws like GDPR mainly exist to...</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q4" value="a">
                            <span class="option-text">A. Slow down the internet</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="b">
                            <span class="option-text">B. Protect people&#039;s personal data and privacy rights</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="c">
                            <span class="option-text">C. Increase advertising revenue</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="d">
                            <span class="option-text">D. Replace passwords entirely</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="5">
                    <div class="question-header">
                        <span class="q-number">Question 5</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>Why should you review the permissions an app requests?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q5" value="a">
                            <span class="option-text">A. To prevent the app from accessing data it doesn&#039;t actually need</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="b">
                            <span class="option-text">B. It&#039;s not necessary, apps always need everything</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="c">
                            <span class="option-text">C. Permissions don&#039;t affect your privacy</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="d">
                            <span class="option-text">D. To make the app run faster</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="6">
                    <div class="question-header">
                        <span class="q-number">Question 6</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>What is online privacy?</h3>
                    <input type="text" class="structured-input" name="q6" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="7">
                    <div class="question-header">
                        <span class="q-number">Question 7</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>Name one way companies collect your data.</h3>
                    <input type="text" class="structured-input" name="q7" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="8">
                    <div class="question-header">
                        <span class="q-number">Question 8</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>What is a privacy policy?</h3>
                    <input type="text" class="structured-input" name="q8" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="9">
                    <div class="question-header">
                        <span class="q-number">Question 9</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>Give one tip to protect your online privacy.</h3>
                    <input type="text" class="structured-input" name="q9" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="10">
                    <div class="question-header">
                        <span class="q-number">Question 10</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>Why is data protection important?</h3>
                    <input type="text" class="structured-input" name="q10" placeholder="Type your answer...">
                </div>

                <div class="form-actions">
                    <button type="submit" name="submit_level3" class="btn-submit"> Submit Answers</button>
                </div>
            </form>
            <?php endif; ?>
            
            <?php if ($_SESSION['level3_completed'] && isset($_SESSION['level3_passed'])): ?>
            <div class="results-section <?php echo $_SESSION['level3_passed'] ? 'passed' : 'failed'; ?>">
                <div class="results-icon">
                    <?php echo $_SESSION['level3_passed'] ? '🎉' : ''; ?>
                </div>
                <h2><?php echo $_SESSION['level3_passed'] ? 'Congratulations!' : 'Keep Trying!'; ?></h2>
                <div class="score-display">
                    <div class="score-circle">
                        <span class="score-number"><?php echo $_SESSION['level3_percentage'] ?? 0; ?>%</span>
                    </div>
                </div>
                <p>You scored <?php echo $_SESSION['level3_score'] ?? 0; ?> out of 10</p>
                <?php if ($_SESSION['level3_passed']): ?>
                    <p class="success-message">🌟 Level 4 is now unlocked! You earned <?php echo 90 + ($_SESSION['level3_score'] * 5); ?> XP!</p>
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
