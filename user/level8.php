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

// Check if user has access to Level 8
if ($currentLevel < 8) {
    header("Location: learn.php");
    exit();
}

// Initialize session state
if (!isset($_SESSION['level8_started'])) {
    $_SESSION['level8_started'] = false;
    $_SESSION['level8_completed'] = false;
    $_SESSION['level8_score'] = 0;
    $_SESSION['level8_answers'] = [];
}

// Start level action
if (isset($_POST['start_level8'])) {
    $_SESSION['level8_started'] = true;
    $_SESSION['level8_completed'] = false;
    $_SESSION['level8_score'] = 0;
    $_SESSION['level8_answers'] = [];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_level8'])) {
    $answers = [];
    $score = 0;
    $totalQuestions = 10;

    $mcqAnswers = [
        'q1' => 'b',
        'q2' => 'b',
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
        'q6' => ['privacy', 'freedom of expression', 'access to information', 'right to privacy', 'freedom of speech'],
        'q7' => ['gap in access', 'inequality in access', 'access gap', 'unequal access'],
        'q8' => ['bias', 'privacy', 'job loss', 'discrimination', 'misuse', 'surveillance'],
        'q9' => ['using technology responsibly', 'respectful online behaviour', 'safe and ethical use', 'following online etiquette'],
        'q10' => ['technology changes', 'keeps evolving', 'stay informed', 'stay safe', 'adapt to change'],
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

    $_SESSION['level8_score'] = $score;
    $_SESSION['level8_answers'] = $answers;
    $_SESSION['level8_completed'] = true;
    $_SESSION['level8_percentage'] = $percentage;
    $_SESSION['level8_passed'] = $passed;

    if ($passed) {
        $xpEarned = 190 + ($score * 5);
        $newXp = $xp + $xpEarned;

        // Level 8 is the final level - mark the course complete and issue a certificate
        $newLevel = $currentLevel;
        if ($currentLevel < 8) {
            $newLevel = 8;
        }

        $stmt = $conn->prepare("UPDATE users SET xp = ?, level = ? WHERE id = ?");
        $stmt->bind_param("iii", $newXp, $newLevel, $user_id);
        $stmt->execute();

        // Issue a course-completion certificate if one doesn't already exist
        $certCheck = $conn->prepare("SELECT id FROM certificates WHERE user_id = ?");
        $certCheck->bind_param("i", $user_id);
        $certCheck->execute();
        if ($certCheck->get_result()->num_rows == 0) {
            $certNo = 'CERT-' . strtoupper(bin2hex(random_bytes(4))) . '-' . $user_id;
            $certStmt = $conn->prepare("INSERT INTO certificates (user_id, certificate_no, issued_at) VALUES (?, ?, NOW())");
            $certStmt->bind_param("is", $user_id, $certNo);
            $certStmt->execute();
        }

        if ($percentage >= 90) {
            $badgeCheck = $conn->prepare("SELECT id FROM user_badges WHERE user_id = ? AND badge_id = 8");
            $badgeCheck->bind_param("i", $user_id);
            $badgeCheck->execute();
            if ($badgeCheck->get_result()->num_rows == 0) {
                $badgeStmt = $conn->prepare("INSERT INTO user_badges (user_id, badge_id, earned_at) VALUES (?, 8, NOW())");
                $badgeStmt->bind_param("i", $user_id);
                $badgeStmt->execute();
            }
        }

        // Log completion in lesson_progress (links this level to the dashboard's progress tracking)
        $lessonRow = $conn->query("SELECT id FROM lessons WHERE title LIKE 'Level 8:%' LIMIT 1")->fetch_assoc();
        if ($lessonRow) {
            $lessonId = $lessonRow['id'];
            $lpStmt = $conn->prepare("INSERT INTO lesson_progress (user_id, lesson_id, status, completed_at)
                VALUES (?, ?, 'completed', NOW())
                ON DUPLICATE KEY UPDATE status = 'completed', completed_at = NOW()");
            $lpStmt->bind_param("ii", $user_id, $lessonId);
            $lpStmt->execute();
        }

        $message = "🏆 Congratulations! You scored $percentage% and earned $xpEarned XP! You've completed the whole course and unlocked your certificate!";
        $messageType = "success";
    } else {
        $message = "😅 You scored $percentage%. You need $passMark% to pass. Try again!";
        $messageType = "error";
        $_SESSION['level8_completed'] = false;
    }
}

if (isset($_SESSION['level8_completed']) && $_SESSION['level8_completed'] && isset($_SESSION['level8_passed']) && $_SESSION['level8_passed']) {
    echo "<script>
        setTimeout(function() {
            window.location.href = 'certificates.php';
        }, 5000);
    </script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Level 8: Digital Rights, Ethics & the Future</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php require_once("../includes/sidebar.php"); ?>
    
    <div class="level-container">
        <?php require_once("../includes/navbar.php"); ?>
        
        <div class="level-content">
            <div class="level-header">
                <div class="header-left">
                    <h1>⚖️ Level 8: Digital Rights, Ethics & the Future</h1>
                    <p>Explore digital rights, ethics, and where the Internet is heading.</p>
                </div>
                <div class="header-right">
                    <div class="level-badge">Level 8</div>
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
            
            <?php if (!$_SESSION['level8_started'] && !$_SESSION['level8_completed']): ?>
            <div class="intro-section">
                <div class="intro-icon">⚖️</div>
                <h2>Welcome to Level 8: Digital Rights, Ethics & the Future</h2>
                <p>Explore digital rights, ethics, and where the Internet is heading.</p>
                <ul class="intro-list">
                    <li>✅ Understand core digital rights</li>
                    <li>✅ Explore digital ethics and responsible technology use</li>
                    <li>✅ Consider ethical questions raised by AI and emerging tech</li>
                    <li>✅ Understand the digital divide and digital inclusion</li>
                    <li>✅ Reflect on what it means to be a responsible digital citizen</li>
                </ul>
                <p style="margin-top: 20px; color: #666;">Read the modules below, then complete the assessment. You need 80% to pass and complete the course.</p>
            </div>

            <div class="intro-section" style="text-align:left;">
                <h2>Module 1: Understanding Digital Rights</h2>
                <p>Digital rights extend familiar human rights into the online world.</p>
                <ul class="intro-list">
                    <li>These include the right to privacy, freedom of expression, and access to information.</li>
                    <li>Digital rights help ensure the Internet remains fair and open for everyone.</li>
                </ul>

                <h2>Module 2: Digital Ethics</h2>
                <p>Digital ethics is about using technology in ways that are responsible and fair.</p>
                <ul class="intro-list">
                    <li>It covers honesty online, respecting others, and considering the impact of your actions.</li>
                    <li>Ethical use of technology helps build trust and a safer digital environment.</li>
                </ul>

                <h2>Module 3: Ethics of AI &amp; Emerging Technology</h2>
                <p>New technologies raise new ethical questions.</p>
                <ul class="intro-list">
                    <li>AI systems can reinforce bias or discrimination if not carefully designed and checked.</li>
                    <li>Emerging tech raises questions about privacy, surveillance, and job impacts.</li>
                </ul>

                <h2>Module 4: The Digital Divide</h2>
                <p>Not everyone has equal access to the Internet and digital tools.</p>
                <ul class="intro-list">
                    <li>The digital divide is the gap between those with and without reliable access to technology.</li>
                    <li>Digital inclusion efforts aim to close this gap through infrastructure, affordability, and education.</li>
                </ul>

                <h2>Module 5: Being a Responsible Digital Citizen</h2>
                <p>Everything learned in this course comes together in how you act online.</p>
                <ul class="intro-list">
                    <li>Use technology safely, ethically, and respectfully.</li>
                    <li>Protect your privacy and respect the privacy of others.</li>
                    <li>Think critically about information before accepting or sharing it.</li>
                </ul>

                <h2>Module 6: Course Recap</h2>
                <p>Across this course, you&#039;ve built a well-rounded understanding of the Internet.</p>
                <ul class="intro-list">
                    <li>You explored the Internet&#039;s foundations, governance, and how it works.</li>
                    <li>You learned about privacy, cybersecurity, social media safety, and misinformation.</li>
                    <li>You now understand the structures that govern the Internet and the ethics that should guide its use.</li>
                </ul>

            </div>

            <form method="POST" action="" style="display: inline-block; margin-top: 20px;">
                <input type="hidden" name="start_level8" value="1">
                <button type="submit" class="btn-start">🚀 Start Level</button>
            </form>
            <?php endif; ?>
            
            <?php if ($_SESSION['level8_started'] && !$_SESSION['level8_completed']): ?>
            <form method="POST" action="" id="quizForm" class="quiz-form">

                <div class="question-card" data-question="1">
                    <div class="question-header">
                        <span class="q-number">Question 1</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>Which of these is considered a digital right?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q1" value="a">
                            <span class="option-text">A. The right to unlimited free mobile data</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="b">
                            <span class="option-text">B. The right to privacy and freedom of expression online</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="c">
                            <span class="option-text">C. The right to access any system without permission</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="d">
                            <span class="option-text">D. The right to ignore all online laws</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="2">
                    <div class="question-header">
                        <span class="q-number">Question 2</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What is the &quot;digital divide&quot;?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q2" value="a">
                            <span class="option-text">A. A firewall configuration setting</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="b">
                            <span class="option-text">B. The gap between those with and without access to digital technology</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="c">
                            <span class="option-text">C. A type of computer virus</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="d">
                            <span class="option-text">D. A social media trend</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="3">
                    <div class="question-header">
                        <span class="q-number">Question 3</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What is digital ethics primarily concerned with?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q3" value="a">
                            <span class="option-text">A. Making websites load faster</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="b">
                            <span class="option-text">B. The responsible and moral use of technology</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="c">
                            <span class="option-text">C. Increasing internet connection speed</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="d">
                            <span class="option-text">D. Selling personal data for profit</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="4">
                    <div class="question-header">
                        <span class="q-number">Question 4</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>Why is it important to consider bias in AI systems?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q4" value="a">
                            <span class="option-text">A. Bias in AI doesn&#039;t matter</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="b">
                            <span class="option-text">B. AI can reinforce unfair or discriminatory outcomes if not checked</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="c">
                            <span class="option-text">C. AI is always completely neutral</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="d">
                            <span class="option-text">D. AI has no impact on society</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="5">
                    <div class="question-header">
                        <span class="q-number">Question 5</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>Which best describes a responsible digital citizen?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q5" value="a">
                            <span class="option-text">A. Someone who ignores online rules and etiquette</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="b">
                            <span class="option-text">B. Someone who uses technology safely, ethically, and respectfully</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="c">
                            <span class="option-text">C. Someone who shares everything publicly without thought</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="d">
                            <span class="option-text">D. Someone who never uses the Internet</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="6">
                    <div class="question-header">
                        <span class="q-number">Question 6</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>Name one digital right.</h3>
                    <p class="hint">Hint: Privacy, access, expression...</p>
                    <input type="text" class="structured-input" name="q6" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="7">
                    <div class="question-header">
                        <span class="q-number">Question 7</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>What is the digital divide?</h3>
                    <p class="hint">Hint: A gap in access to technology.</p>
                    <input type="text" class="structured-input" name="q7" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="8">
                    <div class="question-header">
                        <span class="q-number">Question 8</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>Give one example of an ethical concern with AI.</h3>
                    <p class="hint">Hint: Bias, privacy, jobs...</p>
                    <input type="text" class="structured-input" name="q8" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="9">
                    <div class="question-header">
                        <span class="q-number">Question 9</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>What does it mean to be a responsible digital citizen?</h3>
                    <p class="hint">Hint: Safe, ethical, respectful use.</p>
                    <input type="text" class="structured-input" name="q9" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="10">
                    <div class="question-header">
                        <span class="q-number">Question 10</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>Why is continuous learning about technology important?</h3>
                    <p class="hint">Hint: Technology keeps changing.</p>
                    <input type="text" class="structured-input" name="q10" placeholder="Type your answer...">
                </div>

                <div class="form-actions">
                    <button type="submit" name="submit_level8" class="btn-submit">📤 Submit Answers</button>
                </div>
            </form>
            <?php endif; ?>
            
            <?php if ($_SESSION['level8_completed'] && isset($_SESSION['level8_passed'])): ?>
            <div class="results-section <?php echo $_SESSION['level8_passed'] ? 'passed' : 'failed'; ?>">
                <div class="results-icon">
                    <?php echo $_SESSION['level8_passed'] ? '🎉' : '😅'; ?>
                </div>
                <h2><?php echo $_SESSION['level8_passed'] ? 'Congratulations!' : 'Keep Trying!'; ?></h2>
                <div class="score-display">
                    <div class="score-circle">
                        <span class="score-number"><?php echo $_SESSION['level8_percentage'] ?? 0; ?>%</span>
                    </div>
                </div>
                <p>You scored <?php echo $_SESSION['level8_score'] ?? 0; ?> out of 10</p>
                <?php if ($_SESSION['level8_passed']): ?>
                    <p class="success-message">🌟 Course complete! Your certificate is ready. You earned <?php echo 190 + ($_SESSION['level8_score'] * 5); ?> XP!</p>
                    <p style="color: #999; font-size: 14px;">Redirecting to your certificate in 5 seconds...</p>
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