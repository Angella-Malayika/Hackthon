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
        'q1' => 'a',
        'q2' => 'c',
        'q3' => 'b',
        'q4' => 'b',
        'q5' => 'b'
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
        'q6' => ['governance', 'shared principles', 'rules', 'standards', 'decision-making', 'internet governance'],
        'q7' => ['government', 'governments', 'private', 'private sector', 'technical', 'technical community', 'civil society', 'academia', 'academic', 'international organisation', 'international organization'],
        'q8' => ['internet protocol address', 'ip address', 'unique identifier', 'device address', 'address assigned'],
        'q9' => ['internet service provider', 'isp'],
        'q10' => ['education', 'communication', 'information', 'learning', 'business', 'commerce', 'innovation', 'entertainment', 'services', 'access']
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
    $passMark = 80;
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
        
        // Unlock Level 2 when the user passes Level 1
        $newLevel = $currentLevel;
        if ($currentLevel < 2) {
            $newLevel = 2;
        }
        
        $stmt = $conn->prepare("UPDATE users SET xp = ?, level = ? WHERE id = ?");
        $stmt->bind_param("iii", $newXp, $newLevel, $user_id);
        $stmt->execute();
        
        // Add badge if score is high
        if ($percentage >= 90) {
            $badgeCheck = $conn->prepare("SELECT id FROM user_badges WHERE user_id = ? AND badge_id = 1");
            $badgeCheck->bind_param("i", $user_id);
            $badgeCheck->execute();
            if ($badgeCheck->get_result()->num_rows == 0) {
                $badgeStmt = $conn->prepare("INSERT INTO user_badges (user_id, badge_id, earned_at) VALUES (?, 1, NOW())");
                $badgeStmt->bind_param("i", $user_id);
                $badgeStmt->execute();
            }
        }
        
        // Log completion in lesson_progress (links this level to the dashboard's progress tracking)
        $lessonRow = $conn->query("SELECT id FROM lessons WHERE title LIKE 'Level 1:%' LIMIT 1")->fetch_assoc();
        if ($lessonRow) {
            $lessonId = $lessonRow['id'];
            $lpStmt = $conn->prepare("INSERT INTO lesson_progress (user_id, lesson_id, status, completed_at)
                VALUES (?, ?, 'completed', NOW())
                ON DUPLICATE KEY UPDATE status = 'completed', completed_at = NOW()");
            $lpStmt->bind_param("ii", $user_id, $lessonId);
            $lpStmt->execute();
        }

        $message = "🎉 Congratulations! You scored $percentage% and earned $xpEarned XP! Level 2 is now unlocked.";
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
    <title>Level 1 - Internet Foundations</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php require_once("../includes/sidebar.php"); ?>
    
    <div class="level-container">
        <?php require_once("../includes/navbar.php"); ?>
        
        <div class="level-content">
            <!-- Header -->
            <div class="level-header">
                <div class="header-left">
                    <h1>🌐 Level 1: Internet Foundations</h1>
                    <p>Understand the Internet, its governance, and how to use it responsibly.</p>
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
                <div class="intro-icon">🌐</div>
                <h2>Welcome to Level 1: Internet Foundations</h2>
                <p>In this level, you'll explore the Internet's purpose, history, governance, structure, benefits, and challenges.</p>
                <ul class="intro-list">
                    <li>✅ Define the Internet and explain how it works</li>
                    <li>✅ Learn the history and evolution of the Internet</li>
                    <li>✅ Understand Internet Governance and its stakeholders</li>
                    <li>✅ Recognise the benefits and challenges of the Internet</li>
                    <li>✅ Appreciate responsible Internet use</li>
                </ul>
                <p style="margin-top: 20px; color: #666;">Read the modules below, then complete the assessment. You need 80% to pass and unlock Level 2.</p>
            </div>

            <div class="intro-section" style="text-align:left;">
                <h2>Module 1: Introduction to the Internet</h2>
                <p>The Internet is a global network of interconnected computers and devices that communicate using standard protocols. It connects millions of networks and allows people to share information, communicate, learn, and access services around the world.</p>
                <h3>Key Points</h3>
                <ul class="intro-list">
                    <li>The Internet is a network of networks.</li>
                    <li>It connects billions of devices globally.</li>
                    <li>Information travels using Internet Protocol (IP).</li>
                    <li>It enables communication and information sharing.</li>
                </ul>

                <h2>Module 2: History and Evolution of the Internet</h2>
                <p>The Internet began in the late 1960s with the ARPANET research project. In 1983, the TCP/IP protocol became the standard, making it possible for different networks to communicate. In 1989, the World Wide Web was invented, making websites and hyperlinks easy to use.</p>
                <p>Since then, the Internet has grown through broadband, smartphones, cloud computing, social media, and more.</p>
                <h3>Timeline</h3>
                <ul class="intro-list">
                    <li><strong>1969</strong> – ARPANET established.</li>
                    <li><strong>1983</strong> – TCP/IP adopted.</li>
                    <li><strong>1989</strong> – World Wide Web invented.</li>
                    <li><strong>1990s</strong> – Public Internet expands globally.</li>
                    <li><strong>2000s</strong> – Social media and smartphones grow.</li>
                    <li><strong>Today</strong> – AI, cloud computing, and IoT transform the Internet.</li>
                </ul>

                <h2>Module 3: Understanding Internet Governance</h2>
                <p>Internet Governance covers the shared principles, rules, standards, and procedures used to manage the Internet. It ensures the Internet remains secure, open, stable, and accessible.</p>
                <p>It addresses cybersecurity, privacy, domain name management, online freedom, and digital inclusion.</p>

                <h2>Module 4: How the Internet Works</h2>
                <p>Your device connects to an Internet Service Provider (ISP). DNS translates website names into IP addresses. Data travels through servers, routers, fibre optic cables, satellites, and wireless networks to deliver content.</p>
                <p>Important components include ISPs, DNS, IP addresses, web servers, routers, cables, and wireless networks.</p>

                <h2>Module 5: Stakeholders in Internet Governance</h2>
                <p>Internet Governance uses a multi-stakeholder model. Different groups share responsibility.</p>
                <ul class="intro-list">
                    <li><strong>Governments</strong> – Create laws and policies for cybersecurity, privacy, and safety.</li>
                    <li><strong>Private sector</strong> – Builds infrastructure, platforms, and digital services.</li>
                    <li><strong>Technical community</strong> – Develops standards and keeps the network stable.</li>
                    <li><strong>Civil society</strong> – Advocates for rights, inclusion, and responsible use.</li>
                    <li><strong>Academic institutions</strong> – Deliver research, innovation, and policy guidance.</li>
                    <li><strong>International organisations</strong> – Promote global cooperation and access.</li>
                </ul>

                <h2>Module 6: Benefits and Challenges of the Internet</h2>
                <p>The Internet supports education, business, communication, healthcare, and innovation.</p>
                <h3>Benefits</h3>
                <ul class="intro-list">
                    <li>Online learning and research.</li>
                    <li>Global business and communication.</li>
                    <li>Access to information and services.</li>
                    <li>Innovation and entertainment.</li>
                </ul>
                <h3>Challenges</h3>
                <ul class="intro-list">
                    <li>Cybercrime and phishing attacks.</li>
                    <li>Misinformation and scams.</li>
                    <li>Privacy violations and data misuse.</li>
                    <li>Digital inequality and screen addiction.</li>
                </ul>

                <h2>Module 7: Case Study</h2>
                <p>A university adopted online learning and digital services. While learning improved, students faced phishing emails, fake scholarship websites, weak passwords, and misinformation. The university responded with awareness training, two-factor authentication, better password policies, and fact-checking guidance.</p>

                <h2>Module 8: Level Summary</h2>
                <p>In this level, you learned that the Internet is a global network, how it evolved, and why shared governance matters. You also learned how it works, who the stakeholders are, and the benefits and challenges of using it responsibly.</p>
            </div>

            <form method="POST" action="" style="display: inline-block; margin-top: 20px;">
                <input type="hidden" name="start_level" value="1">
                <button type="submit" class="btn-start">🚀 Start Level</button>
            </form>
            <?php endif; ?>
            
            <?php if ($_SESSION['level1_started'] && !$_SESSION['level1_completed']): ?>
            <form method="POST" action="" id="quizForm" class="quiz-form">
                <div class="question-card" data-question="1">
                    <div class="question-header">
                        <span class="q-number">Question 1</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What is the Internet?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q1" value="a">
                            <span class="option-text">A. A global network of interconnected computers and devices</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="b">
                            <span class="option-text">B. A single computer system</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="c">
                            <span class="option-text">C. A type of social media platform</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="d">
                            <span class="option-text">D. A computer virus</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="2">
                    <div class="question-header">
                        <span class="q-number">Question 2</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>In which year was TCP/IP adopted as the standard protocol?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q2" value="a">
                            <span class="option-text">A. 1969</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="b">
                            <span class="option-text">B. 1989</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="c">
                            <span class="option-text">C. 1983</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q2" value="d">
                            <span class="option-text">D. 2000</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="3">
                    <div class="question-header">
                        <span class="q-number">Question 3</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>Who invented the World Wide Web?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q3" value="a">
                            <span class="option-text">A. Vint Cerf</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="b">
                            <span class="option-text">B. Sir Tim Berners-Lee</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="c">
                            <span class="option-text">C. Bill Gates</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="d">
                            <span class="option-text">D. Mark Zuckerberg</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="4">
                    <div class="question-header">
                        <span class="q-number">Question 4</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>What does DNS do?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q4" value="a">
                            <span class="option-text">A. Stores website passwords</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="b">
                            <span class="option-text">B. Translates domain names into IP addresses</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="c">
                            <span class="option-text">C. Creates domain names</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q4" value="d">
                            <span class="option-text">D. Blocks unsafe websites</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="5">
                    <div class="question-header">
                        <span class="q-number">Question 5</span>
                        <span class="q-type">Multiple Choice</span>
                    </div>
                    <h3>Which of the following is a major challenge of the Internet?</h3>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q5" value="a">
                            <span class="option-text">A. Fast downloads</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="b">
                            <span class="option-text">B. Cybersecurity threats</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="c">
                            <span class="option-text">C. Free email</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="d">
                            <span class="option-text">D. Online education</span>
                        </label>
                    </div>
                </div>

                <div class="question-card" data-question="6">
                    <div class="question-header">
                        <span class="q-number">Question 6</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>What is Internet Governance?</h3>
                    <p class="hint">Hint: It is about rules, standards, and shared decision-making.</p>
                    <input type="text" class="structured-input" name="q6" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="7">
                    <div class="question-header">
                        <span class="q-number">Question 7</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>Name one major stakeholder in Internet Governance.</h3>
                    <p class="hint">Hint: Examples include governments, private sector, or civil society.</p>
                    <input type="text" class="structured-input" name="q7" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="8">
                    <div class="question-header">
                        <span class="q-number">Question 8</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>What is an IP address?</h3>
                    <p class="hint">Hint: It is a unique address for a device on the Internet.</p>
                    <input type="text" class="structured-input" name="q8" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="9">
                    <div class="question-header">
                        <span class="q-number">Question 9</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>What does ISP stand for?</h3>
                    <p class="hint">Hint: It provides access to the Internet.</p>
                    <input type="text" class="structured-input" name="q9" placeholder="Type your answer...">
                </div>

                <div class="question-card" data-question="10">
                    <div class="question-header">
                        <span class="q-number">Question 10</span>
                        <span class="q-type">Structured Answer</span>
                    </div>
                    <h3>Give one benefit of the Internet.</h3>
                    <p class="hint">Hint: It may help with education, communication, or business.</p>
                    <input type="text" class="structured-input" name="q10" placeholder="Type your answer...">
                </div>

                <div class="form-actions">
                    <button type="submit" name="submit_level1" class="btn-submit">📤 Submit Answers</button>
                </div>
            </form>
            <?php endif; ?>
            
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


