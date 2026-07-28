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

// Check if user has access to Level 4
if ($currentLevel < 4) {
    header("Location: learn.php");
    exit();
}

// Initialize session state
if (!isset($_SESSION['level4_started'])) {
    $_SESSION['level4_started'] = false;
    $_SESSION['level4_completed'] = false;
    $_SESSION['level4_score'] = 0;
    $_SESSION['level4_answers'] = [];
}

// Start level action
if (isset($_POST['start_level4'])) {
    $_SESSION['level4_started'] = true;
    $_SESSION['level4_completed'] = false;
    $_SESSION['level4_score'] = 0;
    $_SESSION['level4_answers'] = [];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_level4'])) {
    $answers = [];
    $score = 0;
    $totalQuestions = 10;

    $mcqAnswers = [
        'q1' => 'b',
        'q2' => 'c',
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
        'q6' => ['protecting systems', 'protect data', 'protection against attacks', 'protecting networks', 'safety measures', 'protecting devices'],
        'q7' => ['malware', 'phishing', 'virus', 'ransomware', 'hacking', 'spyware', 'trojan'],
        'q8' => ['length', 'complexity', 'uppercase', 'numbers', 'symbols', 'random', 'combination of characters'],
        'q9' => ['extra security', 'additional verification', 'second layer', 'verify identity', 'extra layer of protection'],
        'q10' => ['update software', 'antivirus', 'strong password', 'avoid suspicious links', 'backup data', 'use vpn', 'enable 2fa'],
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

    $_SESSION['level4_score'] = $score;
    $_SESSION['level4_answers'] = $answers;
    $_SESSION['level4_completed'] = true;
    $_SESSION['level4_percentage'] = $percentage;
    $_SESSION['level4_passed'] = $passed;

    if ($passed) {
        $xpEarned = 110 + ($score * 5);
        $newXp = $xp + $xpEarned;

        // Unlock the next level when the user passes
        $newLevel = $currentLevel;
        if ($currentLevel < 5) {
            $newLevel = 5;
        }

        $stmt = $conn->prepare("UPDATE users SET xp = ?, level = ? WHERE id = ?");
        $stmt->bind_param("iii", $newXp, $newLevel, $user_id);
        $stmt->execute();

        if ($percentage >= 90) {
            $badgeCheck = $conn->prepare("SELECT id FROM user_badges WHERE user_id = ? AND badge_id = 4");
            $badgeCheck->bind_param("i", $user_id);
            $badgeCheck->execute();
            if ($badgeCheck->get_result()->num_rows == 0) {
                $badgeStmt = $conn->prepare("INSERT INTO user_badges (user_id, badge_id, earned_at) VALUES (?, 4, NOW())");
                $badgeStmt->bind_param("i", $user_id);
                $badgeStmt->execute();
            }
        }

        // Log completion in lesson_progress (links this level to the dashboard's progress tracking)
        $lessonRow = $conn->query("SELECT id FROM lessons WHERE title LIKE 'Level 4:%' LIMIT 1")->fetch_assoc();
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

        $message = "🎉 Great work! You scored $percentage% and earned $xpEarned XP! Level 5 is now unlocked.";
        $messageType = "success";
    } else {
        $message = "Ooops! You scored $percentage%. You need $passMark% to pass. Try again!";
        $messageType = "error";
        $_SESSION['level4_completed'] = false;
    }
}

if (isset($_SESSION['level4_completed']) && $_SESSION['level4_completed'] && isset($_SESSION['level4_passed']) && $_SESSION['level4_passed']) {
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
    <title>Level 4: Cybersecurity Basics</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php require_once("../includes/sidebar.php"); ?>
    
    <div class="level-container">
        <?php require_once("../includes/navbar.php"); ?>
        
        <div class="level-content">
            <div class="level-header">
                <div class="header-left">
                    <h1>🛡️ Level 4: Cybersecurity Basics</h1>
                    <p>Learn how to recognise threats and keep your accounts and devices safe.</p>
                </div>
                <div class="header-right">
                    <div class="level-badge">Level 4</div>
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
            
            <?php if (!$_SESSION['level4_started'] && !$_SESSION['level4_completed']): ?>
            <div class="intro-section">
                <div class="intro-icon">🛡️</div>
                <h2>Welcome to Level 4: Cybersecurity Basics</h2>
                <p>Learn how to recognise threats and keep your accounts and devices safe.</p>
                <ul class="intro-list">
                    <li>✅ Identify common cyber threats</li>
                    <li>✅ Build strong passwords and use two-factor authentication</li>
                    <li>✅ Recognise phishing attempts</li>
                    <li>✅ Practise safe browsing habits</li>
                    <li>✅ Know what to do when something looks suspicious</li>
                </ul>
                <p style="margin-top: 20px; color: #666;">Read the modules below, then complete the assessment. You need 70% to pass and unlock Level 5.</p>
            </div>

            <div class="intro-section" style="text-align:left;">
                <h2>Module 1: Understanding Cyber Threats</h2>
                <p>Cybersecurity is about protecting devices, networks, and data from attacks.</p>
                <ul class="intro-list">
                    <li>Malware is malicious software designed to harm or exploit a system.</li>
                    <li>Phishing tricks people into revealing sensitive information.</li>
                    <li>Ransomware locks your files until a payment is made.</li>
                </ul>

                <h2>Module 2: Building Strong Passwords</h2>
                <p>Weak passwords are one of the easiest ways attackers gain access to accounts.</p>
                <ul class="intro-list">
                    <li>Use a mix of uppercase, lowercase, numbers, and symbols.</li>
                    <li>Avoid using personal details like birthdays or names.</li>
                    <li>Never reuse the same password across multiple accounts.</li>
                </ul>

                <h2>Module 3: Two-Factor Authentication (2FA)</h2>
                <p>2FA adds an extra layer of protection beyond just a password.</p>
                <ul class="intro-list">
                    <li>It usually combines something you know (password) with something you have (phone code).</li>
                    <li>Even if a password is stolen, 2FA can stop an attacker getting in.</li>
                </ul>

                <h2>Module 4: Recognising Phishing</h2>
                <p>Phishing messages try to trick you into clicking links or sharing information.</p>
                <ul class="intro-list">
                    <li>Watch for urgent or threatening language.</li>
                    <li>Check the sender&#039;s email address carefully.</li>
                    <li>Never click links or download attachments from unknown sources.</li>
                </ul>

                <h2>Module 5: Safe Browsing Habits</h2>
                <p>Small habits reduce your risk of running into cyber threats.</p>
                <ul class="intro-list">
                    <li>Keep your software and apps updated.</li>
                    <li>Use antivirus software and a firewall.</li>
                    <li>Avoid public Wi-Fi for sensitive tasks, or use a VPN.</li>
                </ul>

                <h2>Module 6: What To Do If Something Goes Wrong</h2>
                <p>Quick action limits the damage from a security incident.</p>
                <ul class="intro-list">
                    <li>Change your passwords immediately.</li>
                    <li>Report the incident to the platform or your IT/security team.</li>
                    <li>Monitor your accounts for unusual activity.</li>
                </ul>

            </div>

            <form method="POST" action="" style="display: inline-block; margin-top: 20px;">
                <input type="hidden" name="start_level4" value="1">
                <button type="submit" class="btn-start"> Start Level</button>
            </form>
            <?php endif; ?>
            
            <?php if ($_SESSION['level4_started'] && !$_SESSION['level4_completed']): ?>
            <form method="POST" action="" id="quizForm" class="quiz-form">

                <div class="question-card" data-question="1">
                    <div class="question-header">
                        <span class="q-number">Question 1</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What is phishing?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q1" value="a">
                            <span class="option-text">A. A relaxing outdoor sport</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="b">
                            <span class="option-text">B. A fraudulent attempt to obtain sensitive information through fake messages</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="c">
                            <span class="option-text">C. A type of firewall</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="d">
                            <span class="option-text">D. Antivirus software</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="2">
                    <div class="question-header">
                        <span class="q-number">Question 2</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>Which of these is the strongest password?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q2" value="a">
                            <span class="option-text">A. 123456</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="b">
                            <span class="option-text">B. password</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="c">
                            <span class="option-text">C. Tr3e!Cloud92</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="d">
                            <span class="option-text">D. yourbirthdate</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="3">
                    <div class="question-header">
                        <span class="q-number">Question 3</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What does 2FA stand for?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q3" value="a">
                            <span class="option-text">A. Two-Factor Authentication</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="b">
                            <span class="option-text">B. Two File Access</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="c">
                            <span class="option-text">C. Total Firewall Application</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="d">
                            <span class="option-text">D. Two-Factor Access Only</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="4">
                    <div class="question-header">
                        <span class="q-number">Question 4</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What should you do if you get a suspicious link in an email?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q4" value="a">
                            <span class="option-text">A. Click it right away to see what it is</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="b">
                            <span class="option-text">B. Avoid clicking it and verify the sender first</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="c">
                            <span class="option-text">C. Reply with your personal information</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="d">
                            <span class="option-text">D. Forward it to all your friends</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="5">
                    <div class="question-header">
                        <span class="q-number">Question 5</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What is malware?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q5" value="a">
                            <span class="option-text">A. Malicious software designed to harm or exploit systems</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="b">
                            <span class="option-text">B. A hardware device for backups</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="c">
                            <span class="option-text">C. A social media application</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="d">
                            <span class="option-text">D. A type of web browser</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="6">
                    <div class="question-header">
                        <span class="q-number">Question 6</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>What is cybersecurity?</h3>
                    <input type="text" class="structured-input" name="q6" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="7">
                    <div class="question-header">
                        <span class="q-number">Question 7</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>Name one type of cyber threat.</h3>
                    <input type="text" class="structured-input" name="q7" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="8">
                    <div class="question-header">
                        <span class="q-number">Question 8</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>What makes a password strong?</h3>
                    <input type="text" class="structured-input" name="q8" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="9">
                    <div class="question-header">
                        <span class="q-number">Question 9</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>What is two-factor authentication used for?</h3>
                    <input type="text" class="structured-input" name="q9" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="10">
                    <div class="question-header">
                        <span class="q-number">Question 10</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>Give one way to stay safe online.</h3>
                    <input type="text" class="structured-input" name="q10" placeholder="Type your answer...">
                </div>

                <div class="form-actions">
                    <button type="submit" name="submit_level4" class="btn-submit"> Submit Answers</button>
                </div>
            </form>
            <?php endif; ?>
            
            <?php if ($_SESSION['level4_completed'] && isset($_SESSION['level4_passed'])): ?>
            <div class="results-section <?php echo $_SESSION['level4_passed'] ? 'passed' : 'failed'; ?>">
                <div class="results-icon">
                    <?php echo $_SESSION['level4_passed'] ? '🎉' : ''; ?>
                </div>
                <h2><?php echo $_SESSION['level4_passed'] ? 'Congratulations!' : 'Keep Trying!'; ?></h2>
                <div class="score-display">
                    <div class="score-circle">
                        <span class="score-number"><?php echo $_SESSION['level4_percentage'] ?? 0; ?>%</span>
                    </div>
                </div>
                <p>You scored <?php echo $_SESSION['level4_score'] ?? 0; ?> out of 10</p>
                <?php if ($_SESSION['level4_passed']): ?>
                    <p class="success-message">🌟 Level 5 is now unlocked! You earned <?php echo 110 + ($_SESSION['level4_score'] * 5); ?> XP!</p>
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
