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

// Check if user has access to Level 2
if ($currentLevel < 2) {
    header("Location: learn.php");
    exit();
}

// Initialize session state
if (!isset($_SESSION['level2_started'])) {
    $_SESSION['level2_started'] = false;
    $_SESSION['level2_completed'] = false;
    $_SESSION['level2_score'] = 0;
    $_SESSION['level2_answers'] = [];
}

// Start level action
if (isset($_POST['start_level2'])) {
    $_SESSION['level2_started'] = true;
    $_SESSION['level2_completed'] = false;
    $_SESSION['level2_score'] = 0;
    $_SESSION['level2_answers'] = [];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_level2'])) {
    $answers = [];
    $score = 0;
    $totalQuestions = 10;

    $mcqAnswers = [
        'q1' => 'b',
        'q2' => 'a',
        'q3' => 'd',
        'q4' => 'c',
        'q5' => 'a'
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
        'q6' => ['digital citizenship', 'digital citizen', 'responsible', 'responsible online', 'online behaviour', 'online behavior'],
        'q7' => ['different cultures', 'different opinions', 'different beliefs', 'diverse', 'diversity'],
        'q8' => ['respect', 'responsibility', 'safe', 'empathetic', 'ethical', 'trustworthy'],
        'q9' => ['privacy settings', 'privacy controls', 'secure settings', 'strong privacy'],
        'q10' => ['bullying', 'cyberbullying', 'harm', 'unfair', 'privacy invasion', 'online abuse']
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

    $_SESSION['level2_score'] = $score;
    $_SESSION['level2_answers'] = $answers;
    $_SESSION['level2_completed'] = true;
    $_SESSION['level2_percentage'] = $percentage;
    $_SESSION['level2_passed'] = $passed;

    if ($passed) {
        $xpEarned = 70 + ($score * 5);
        $newXp = $xp + $xpEarned;
        $newLevel = $currentLevel;
        if ($currentLevel < 3) {
            $newLevel = 3;
        }

        $stmt = $conn->prepare("UPDATE users SET xp = ?, level = ? WHERE id = ?");
        $stmt->bind_param("iii", $newXp, $newLevel, $user_id);
        $stmt->execute();

        if ($percentage >= 90) {
            $badgeCheck = $conn->prepare("SELECT id FROM user_badges WHERE user_id = ? AND badge_id = 2");
            $badgeCheck->bind_param("i", $user_id);
            $badgeCheck->execute();
            if ($badgeCheck->get_result()->num_rows == 0) {
                $badgeStmt = $conn->prepare("INSERT INTO user_badges (user_id, badge_id, earned_at) VALUES (?, 2, NOW())");
                $badgeStmt->bind_param("i", $user_id);
                $badgeStmt->execute();
            }
        }

        // Log completion in lesson_progress (links this level to the dashboard's progress tracking)
        $lessonRow = $conn->query("SELECT id FROM lessons WHERE title LIKE 'Level 2:%' LIMIT 1")->fetch_assoc();
        if ($lessonRow) {
            $lessonId = $lessonRow['id'];
            $lpStmt = $conn->prepare("INSERT INTO lesson_progress (user_id, lesson_id, status, completed_at)
                VALUES (?, ?, 'completed', NOW())
                ON DUPLICATE KEY UPDATE status = 'completed', completed_at = NOW()");
            $lpStmt->bind_param("ii", $user_id, $lessonId);
            $lpStmt->execute();
        }

        $message = "🎉 Great work! You scored $percentage% and earned $xpEarned XP! Level 3 is now unlocked.";
        $messageType = "success";
    } else {
        $message = "😅 You scored $percentage%. You need $passMark% to pass. Try again!";
        $messageType = "error";
        $_SESSION['level2_completed'] = false;
    }
}

if (isset($_SESSION['level2_completed']) && $_SESSION['level2_completed'] && isset($_SESSION['level2_passed']) && $_SESSION['level2_passed']) {
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
    <title>Level 2 - Digital Citizenship</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php require_once("../includes/sidebar.php"); ?>
    
    <div class="level-container">
        <?php require_once("../includes/navbar.php"); ?>
        
        <div class="level-content">
            <div class="level-header">
                <div class="header-left">
                    <h1>🧑‍💻 Level 2: Digital Citizenship</h1>
                    <p>Learn how to behave safely, respectfully, and responsibly online.</p>
                </div>
                <div class="header-right">
                    <div class="level-badge">Level 2</div>
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

            <?php if (!$_SESSION['level2_started'] && !$_SESSION['level2_completed']): ?>
            <div class="intro-section">
                <div class="intro-icon">🧑‍💻</div>
                <h2>Welcome to Level 2: Digital Citizenship</h2>
                <p>In this level, you'll learn how to be a responsible, respectful, and safe digital citizen.</p>
                <ul class="intro-list">
                    <li>✅ Respect others online</li>
                    <li>✅ Protect your privacy and security</li>
                    <li>✅ Recognise cyberbullying and harmful content</li>
                    <li>✅ Understand digital rights and responsibilities</li>
                    <li>✅ Build a positive online reputation</li>
                </ul>
                <p style="margin-top: 20px; color: #666;">Complete the module and pass the quiz with 80% to unlock Level 3.</p>
            </div>

            <div class="intro-section" style="text-align:left;">
                <h2>Module 1: What is Digital Citizenship?</h2>
                <p>Digital citizenship means using technology responsibly and ethically. It includes online respect, safety, privacy, and making good decisions when interacting with others on the Internet.</p>

                <h2>Module 2: Respect and Behaviour Online</h2>
                <p>Respect others in comments, chats, and posts. Avoid posting harmful or rude content. Help make the online community a positive place.</p>

                <h2>Module 3: Privacy and Security</h2>
                <p>Use strong passwords and privacy settings. Be careful about the information you share. Protect personal details and account access.</p>

                <h2>Module 4: Recognising Harmful Content</h2>
                <p>Cyberbullying, scams, and hate speech are real problems. Learn to spot unsafe messages, report abuse, and avoid engaging with harmful content.</p>

                <h2>Module 5: Digital Rights and Responsibilities</h2>
                <p>You have a right to online safety and privacy, and a responsibility to act respectfully. Follow laws, respect others' rights, and do not share private information without permission.</p>

                <h2>Module 6: Building a Positive Digital Reputation</h2>
                <p>Your online actions shape how others see you. Share helpful content, be honest, and keep your behaviour positive so your digital reputation stays strong.</p>
            </div>

            <form method="POST" action="" style="display: inline-block; margin-top: 20px;">
                <button type="submit" name="start_level2" class="btn-start">🚀 Start Level 2</button>
            </form>
            <?php endif; ?>

            <?php if ($_SESSION['level2_started'] && !$_SESSION['level2_completed']): ?>
            <form method="POST" action="" id="quizForm" class="quiz-form">
                <div class="question-card" data-question="1">
                    <div class="question-header">
                        <span class="q-number">Question 1</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What does digital citizenship mean?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q1" value="a">
                            <span class="option-text">A. Knowing how to code</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="b">
                            <span class="option-text">B. Using technology responsibly and respectfully</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="c">
                            <span class="option-text">C. Gaming online every day</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="d">
                            <span class="option-text">D. Posting whatever you want</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="2">
                    <div class="question-header">
                        <span class="q-number">Question 2</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>Which behaviour is a good example of digital citizenship?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q2" value="a">
                            <span class="option-text">A. Respecting others online</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="b">
                            <span class="option-text">B. Ignoring safety warnings</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="c">
                            <span class="option-text">C. Sharing other people's passwords</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="d">
                            <span class="option-text">D. Posting rude comments</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="3">
                    <div class="question-header">
                        <span class="q-number">Question 3</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What is cyberbullying?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q3" value="a">
                            <span class="option-text">A. Helping someone online</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="b">
                            <span class="option-text">B. Posting positive feedback</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="c">
                            <span class="option-text">C. Keeping your passwords secret</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="d">
                            <span class="option-text">D. Using technology to harass or harm someone</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="4">
                    <div class="question-header">
                        <span class="q-number">Question 4</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>Which action helps protect privacy online?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q4" value="a">
                            <span class="option-text">A. Sharing your location publicly</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="b">
                            <span class="option-text">B. Using weak passwords</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="c">
                            <span class="option-text">C. Adjusting privacy settings</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="d">
                            <span class="option-text">D. Posting all personal details</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="5">
                    <div class="question-header">
                        <span class="q-number">Question 5</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What should you do if you see harmful content online?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q5" value="a">
                            <span class="option-text">A. Report it or tell a trusted adult</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="b">
                            <span class="option-text">B. Share it with friends</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="c">
                            <span class="option-text">C. Ignore it and copy it</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="d">
                            <span class="option-text">D. Respond with anger</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="6">
                    <div class="question-header">
                        <span class="q-number">Question 6</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>What is a digital citizen?</h3>
                    <p class="hint">Hint: It is someone who uses the Internet responsibly.</p>
                    <input type="text" class="structured-input" name="q6" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="7">
                    <div class="question-header">
                        <span class="q-number">Question 7</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>Why is it important to respect different opinions online?</h3>
                    <p class="hint">Hint: Think about community and diversity.</p>
                    <input type="text" class="structured-input" name="q7" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="8">
                    <div class="question-header">
                        <span class="q-number">Question 8</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>What is one responsible behaviour when using the Internet?</h3>
                    <p class="hint">Hint: It involves being safe, honest, or kind.</p>
                    <input type="text" class="structured-input" name="q8" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="9">
                    <div class="question-header">
                        <span class="q-number">Question 9</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>How can privacy settings help you?</h3>
                    <p class="hint">Hint: They control who sees your information.</p>
                    <input type="text" class="structured-input" name="q9" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="10">
                    <div class="question-header">
                        <span class="q-number">Question 10</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>Give one example of harmful online behaviour.</h3>
                    <p class="hint">Hint: It may involve mean messages or sharing private content.</p>
                    <input type="text" class="structured-input" name="q10" placeholder="Type your answer...">
                </div>

                <div class="form-actions">
                    <button type="submit" name="submit_level2" class="btn-submit">📤 Submit Answers</button>
                </div>
            </form>
            <?php endif; ?>

            <?php if ($_SESSION['level2_completed'] && isset($_SESSION['level2_passed'])): ?>
            <div class="results-section <?php echo $_SESSION['level2_passed'] ? 'passed' : 'failed'; ?>">
                <div class="results-icon">
                    <?php echo $_SESSION['level2_passed'] ? '🎉' : '😅'; ?>
                </div>
                <h2><?php echo $_SESSION['level2_passed'] ? 'Well done!' : 'Try Again'; ?></h2>
                <div class="score-display">
                    <div class="score-circle">
                        <span class="score-number"><?php echo $_SESSION['level2_percentage'] ?? 0; ?>%</span>
                    </div>
                </div>
                <p>You scored <?php echo $_SESSION['level2_score'] ?? 0; ?> out of 10</p>
                <?php if ($_SESSION['level2_passed']): ?>
                    <p class="success-message">🌟 Level 3 is now unlocked! You earned <?php echo 70 + ($_SESSION['level2_score'] * 5); ?> XP!</p>
                    <p style="color: #999; font-size: 14px;">Redirecting to Learn page in 5 seconds...</p>
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


