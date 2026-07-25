<?php
require_once("../middleware/user.php");
require_once("../core.php");

$user_id = $_SESSION['user_id'];

// Handle start level action
if (isset($_POST['start_level'])) {
    $_SESSION['level1_started'] = true;
    $_SESSION['level1_completed'] = false;
    $_SESSION['level1_score'] = 0;
    $_SESSION['level1_answers'] = [];
    
    // Redirect to refresh the page
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get current user data
$stmt = $conn->prepare("SELECT level, xp FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$currentLevel = $user['level'];
$xp = $user['xp'];

// Check if user has access to Level 1
if ($currentLevel < 1) {
    header("Location: learn.php");
    exit();
}

// Initialize session variables for the level
if (!isset($_SESSION['level1_started'])) {
    $_SESSION['level1_started'] = false;
    $_SESSION['level1_completed'] = false;
    $_SESSION['level1_score'] = 0;
    $_SESSION['level1_answers'] = [];
}

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_level1'])) {
    // Get answers
    $answers = [];
    $score = 0;
    $totalQuestions = 10;
    
    // Correct answers for multiple choice (5 questions)
    $mcqAnswers = [
        'q1' => 'c',
        'q2' => 'b',
        'q3' => 'c',
        'q4' => 'b',
        'q5' => 'c'
    ];
    
    // Check MCQ answers
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
    
    // Check structured answers (5 questions)
    $structuredAnswers = [
        'q6' => ['password', 'passwords'],
        'q7' => ['8', 'eight'],
        'q8' => ['special', 'symbols', 'special characters'],
        'q9' => ['two', '2', '2fa', 'two-factor'],
        'q10' => ['manager', 'password manager']
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
    
    // Calculate percentage
    $percentage = round(($score / $totalQuestions) * 100);
    $passMark = 70;
    $passed = $percentage >= $passMark;
    
    // Save results
    $_SESSION['level1_score'] = $score;
    $_SESSION['level1_answers'] = $answers;
    $_SESSION['level1_completed'] = true;
    $_SESSION['level1_percentage'] = $percentage;
    $_SESSION['level1_passed'] = $passed;
    
    // Update user XP and level if passed
    if ($passed) {
        // Calculate XP earned (50 XP base + bonus)
        $xpEarned = 50 + ($score * 5);
        
        // Update user XP
        $newXp = $xp + $xpEarned;
        
        // Check if user should level up (every 100 XP)
        $newLevel = floor($newXp / 100) + 1;
        
        $stmt = $conn->prepare("UPDATE users SET xp = ?, level = ? WHERE id = ?");
        $stmt->bind_param("iii", $newXp, $newLevel, $user_id);
        $stmt->execute();
        
        // Add badge if score is high
        if ($percentage >= 90) {
            // Check if user already has this badge
            $badgeCheck = $conn->prepare("SELECT id FROM user_badges WHERE user_id = ? AND badge_id = 1");
            $badgeCheck->bind_param("i", $user_id);
            $badgeCheck->execute();
            if ($badgeCheck->get_result()->num_rows == 0) {
                $badgeStmt = $conn->prepare("INSERT INTO user_badges (user_id, badge_id, earned_at) VALUES (?, 1, NOW())");
                $badgeStmt->bind_param("i", $user_id);
                $badgeStmt->execute();
            }
        }
        
        $message = "🎉 Congratulations! You scored $percentage% and earned $xpEarned XP!";
        $messageType = "success";
    } else {
        $message = "😅 You scored $percentage%. You need $passMark% to pass. Try again!";
        $messageType = "error";
        // Reset completion so user can retry
        $_SESSION['level1_completed'] = false;
    }
}

// If user has completed and passed, redirect to learn page after 5 seconds
if (isset($_SESSION['level1_completed']) && $_SESSION['level1_completed'] && isset($_SESSION['level1_passed']) && $_SESSION['level1_passed']) {
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
    <title>Level 1 - Password Security</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/level1.css">
</head>
<body>
    <?php require_once("../includes/sidebar.php"); ?>
    
    <div class="level1-container">
        <?php require_once("../includes/navbar.php"); ?>
        
        <div class="level1-content">
            <!-- Header -->
            <div class="level1-header">
                <div class="header-left">
                    <h1>🔐 Level 1: Password Security</h1>
                    <p>Learn how to create and manage strong passwords</p>
                </div>
                <div class="header-right">
                    <div class="level-badge">Level 1</div>
                    <div class="xp-display">⭐ <?php echo $xp; ?> XP</div>
                </div>
            </div>
            
            <!-- Progress Section -->
            <div class="progress-section">
                <div class="progress-bar-container">
                    <div class="progress-bar-fill" id="progressBar" style="width: 0%;"></div>
                </div>
                <div class="progress-text">
                    <span id="progressLabel">0% Complete</span>
                    <span id="questionCounter">Question 1 of 10</span>
                </div>
            </div>
            
            <!-- Display Message -->
            <?php if ($message): ?>
            <div class="toast-message <?php echo $messageType; ?>">
                <span><?php echo $messageType == 'success' ? '✅' : '😅'; ?></span>
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <!-- If not started, show intro -->
            <?php if (!$_SESSION['level1_started'] && !$_SESSION['level1_completed']): ?>
            <div class="intro-section">
                <div class="intro-icon">🛡️</div>
                <h2>Welcome to Password Security!</h2>
                <p>In this level, you'll learn:</p>
                <ul class="intro-list">
                    <li>✅ What makes a strong password</li>
                    <li>✅ How to create memorable passwords</li>
                    <li>✅ Why password managers are important</li>
                    <li>✅ Two-factor authentication basics</li>
                </ul>
                <p style="margin-top: 20px; color: #666;">You'll answer 10 questions. You need 70% to pass and unlock Level 2!</p>
                
                <!-- FIXED: Using form instead of button with onclick -->
                <form method="POST" action="" style="display: inline-block;">
                    <input type="hidden" name="start_level" value="1">
                    <button type="submit" class="btn-start">🚀 Start Level</button>
                </form>
            </div>
            <?php endif; ?>
            
            <!-- Quiz Section -->
            <?php if ($_SESSION['level1_started'] && !$_SESSION['level1_completed']): ?>
            <form method="POST" action="" id="quizForm" class="quiz-form">
                <!-- Question 1 - Multiple Choice -->
                <div class="question-card" data-question="1">
                    <div class="question-header">
                        <span class="q-number">Question 1</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What is the minimum recommended length for a strong password?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q1" value="a">
                            <span class="option-text">A. 6 characters</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="b">
                            <span class="option-text">B. 8 characters</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="c">
                            <span class="option-text">C. 12 characters</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="d">
                            <span class="option-text">D. 4 characters</span>
                        </label>
                    </div>
                </div>
                
                <!-- Question 2 - Multiple Choice -->
                <div class="question-card" data-question="2">
                    <div class="question-header">
                        <span class="q-number">Question 2</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>Which of the following is the strongest password?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q2" value="a">
                            <span class="option-text">A. password123</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="b">
                            <span class="option-text">B. P@ssw0rd!2024</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="c">
                            <span class="option-text">C. 12345678</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="d">
                            <span class="option-text">D. qwerty</span>
                        </label>
                    </div>
                </div>
                
                <!-- Question 3 - Multiple Choice -->
                <div class="question-card" data-question="3">
                    <div class="question-header">
                        <span class="q-number">Question 3</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What is a password manager?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q3" value="a">
                            <span class="option-text">A. A tool that hacks passwords</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="b">
                            <span class="option-text">B. A website that stores passwords</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="c">
                            <span class="option-text">C. A tool that creates and stores strong passwords</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="d">
                            <span class="option-text">D. A type of virus</span>
                        </label>
                    </div>
                </div>
                
                <!-- Question 4 - Multiple Choice -->
                <div class="question-card" data-question="4">
                    <div class="question-header">
                        <span class="q-number">Question 4</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What is two-factor authentication (2FA)?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q4" value="a">
                            <span class="option-text">A. Using two different passwords</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="b">
                            <span class="option-text">B. A second verification step like a code</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="c">
                            <span class="option-text">C. Two-factor login</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="d">
                            <span class="option-text">D. A type of password</span>
                        </label>
                    </div>
                </div>
                
                <!-- Question 5 - Multiple Choice -->
                <div class="question-card" data-question="5">
                    <div class="question-header">
                        <span class="q-number">Question 5</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>How often should you change your passwords?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q5" value="a">
                            <span class="option-text">A. Never</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="b">
                            <span class="option-text">B. Every week</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="c">
                            <span class="option-text">C. Every 3-6 months or if compromised</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="d">
                            <span class="option-text">D. Every day</span>
                        </label>
                    </div>
                </div>
                
                <!-- Question 6 - Structured -->
                <div class="question-card" data-question="6">
                    <div class="question-header">
                        <span class="q-number">Question 6</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>What word describes a strong password that is easy to remember?</h3>
                    <p class="hint">Hint: It's a combination of random words.</p>
                    <input type="text" class="structured-input" name="q6" placeholder="Type your answer...">
                </div>
                
                <!-- Question 7 - Structured -->
                <div class="question-card" data-question="7">
                    <div class="question-header">
                        <span class="q-number">Question 7</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>What is the minimum number of characters recommended for a secure password?</h3>
                    <p class="hint">Hint: It's a number between 8 and 15.</p>
                    <input type="text" class="structured-input" name="q7" placeholder="Type your answer...">
                </div>
                
                <!-- Question 8 - Structured -->
                <div class="question-card" data-question="8">
                    <div class="question-header">
                        <span class="q-number">Question 8</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>What type of characters should you include in a strong password?</h3>
                    <p class="hint">Hint: Think about symbols and numbers.</p>
                    <input type="text" class="structured-input" name="q8" placeholder="Type your answer...">
                </div>
                
                <!-- Question 9 - Structured -->
                <div class="question-card" data-question="9">
                    <div class="question-header">
                        <span class="q-number">Question 9</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>What is the name of the security feature that requires two verification steps?</h3>
                    <p class="hint">Hint: It's often abbreviated as 2FA.</p>
                    <input type="text" class="structured-input" name="q9" placeholder="Type your answer...">
                </div>
                
                <!-- Question 10 - Structured -->
                <div class="question-card" data-question="10">
                    <div class="question-header">
                        <span class="q-number">Question 10</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>What tool helps you generate and store strong passwords securely?</h3>
                    <p class="hint">Hint: It stores all your passwords in one place.</p>
                    <input type="text" class="structured-input" name="q10" placeholder="Type your answer...">
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="submit_level1" class="btn-submit">📤 Submit Answers</button>
                </div>
            </form>
            <?php endif; ?>
            
            <!-- Results Section -->
            <?php if ($_SESSION['level1_completed'] && isset($_SESSION['level1_passed'])): ?>
            <div class="results-section <?php echo $_SESSION['level1_passed'] ? 'passed' : 'failed'; ?>">
                <div class="results-icon">
                    <?php echo $_SESSION['level1_passed'] ? '🎉' : '😅'; ?>
                </div>
                <h2><?php echo $_SESSION['level1_passed'] ? 'Congratulations!' : 'Keep Trying!'; ?></h2>
                <div class="score-display">
                    <div class="score-circle">
                        <span class="score-number"><?php echo $_SESSION['level1_percentage'] ?? 0; ?>%</span>
                    </div>
                </div>
                <p>You scored <?php echo $_SESSION['level1_score'] ?? 0; ?> out of 10</p>
                <?php if ($_SESSION['level1_passed']): ?>
                    <p class="success-message">🌟 Level 2 is now unlocked! You earned <?php echo 50 + ($_SESSION['level1_score'] * 5); ?> XP!</p>
                    <p style="color: #999; font-size: 14px;">Redirecting to Learn page in 5 seconds...</p>
                <?php else: ?>
                    <p class="error-message">You need 70% to pass. Please try again.</p>
                    <button class="btn-retry" onclick="location.reload()">🔄 Try Again</button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../javascript/script.js"></script>
</body>
</html>