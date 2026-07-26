<?php
require_once("../middleware/user.php");
require_once("../core.php");

// Logged in user
$user_id = $_SESSION['user_id'];


// Total published lessons
$sqlLessons = "SELECT COUNT(*) AS total FROM lessons WHERE status='published'";
$resultLessons = $conn->query($sqlLessons);
$totalLessons = $resultLessons->fetch_assoc()['total'] ?? 0;

// Completed lessons
$stmt = $conn->prepare("
    SELECT COUNT(*) AS completed
    FROM lesson_progress
    WHERE user_id=?
    AND status='completed'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$completedLessons = $stmt->get_result()->fetch_assoc()['completed'] ?? 0;

// Completed quests
$stmt = $conn->prepare("
    SELECT COUNT(*) AS completed
    FROM user_quests
    WHERE user_id=?
    AND status='completed'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$completedQuests = $stmt->get_result()->fetch_assoc()['completed'] ?? 0;

// Certificates
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM certificates
    WHERE user_id=?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$totalCertificates = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Progress
$progress = 0;
if ($totalLessons > 0) {
    $progress = round(($completedLessons / $totalLessons) * 100);
}

// Next lesson to continue
$nextLesson = null;
$stmt = $conn->prepare("
    SELECT lessons.*
    FROM lessons
    LEFT JOIN lesson_progress 
        ON lessons.id = lesson_progress.lesson_id 
        AND lesson_progress.user_id = ?
    WHERE lesson_progress.status IS NULL 
        OR lesson_progress.status != 'completed'
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$nextLesson = $stmt->get_result()->fetch_assoc();

// Recent achievements (last 5 badges earned)
$recentBadges = [];
$stmt = $conn->prepare("
    SELECT b.name, b.image, ub.earned_at
    FROM user_badges ub
    JOIN badges b ON ub.badge_id = b.id
    WHERE ub.user_id = ?
    ORDER BY ub.earned_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recentBadges = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Daily challenge (random quest for today)
$dailyQuest = null;
$stmt = $conn->prepare("
    SELECT * FROM quests
    ORDER BY RAND()
    LIMIT 1
");
$stmt->execute();
$dailyQuest = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Internet Governance Platform</title>
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php require_once("../includes/sidebar.php"); ?>
    
    <div class="main">
        <?php require_once("../includes/navbar.php"); ?>
        
        <div class="content">
            <!-- Hero Welcome Section -->
            <div class="dashboard-hero">
                <h1>👋 Welcome back, <?php echo htmlspecialchars($_SESSION['fullname']); ?>!</h1>
                <p>Continue your journey to become a cyber-aware citizen. Your progress is saved automatically.</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card green">
                    <span class="icon">📚</span>
                    <div class="number"><?php echo $totalLessons; ?></div>
                    <div class="label">Total Lessons</div>
                </div>
                
                <div class="stat-card gold">
                    <span class="icon">🎯</span>
                    <div class="number"><?php echo $completedQuests; ?></div>
                    <div class="label">Completed Quests</div>
                </div>
                
                <div class="stat-card blue">
                    <span class="icon">📈</span>
                    <div class="number"><?php echo $progress; ?>%</div>
                    <div class="label">Progress</div>
                </div>
                
                <div class="stat-card purple">
                    <span class="icon">🏆</span>
                    <div class="number"><?php echo $totalCertificates; ?></div>
                    <div class="label">Certificates</div>
                </div>
            </div>

            <!-- Progress Section -->
            <div class="progress-section">
                <h3>📊 Your Learning Progress</h3>
                <p>You've completed <strong><?php echo $completedLessons; ?></strong> out of <strong><?php echo $totalLessons; ?></strong> lessons</p>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
                </div>
                <div class="progress-text">
                    <span>🌱 Beginner</span>
                    <span><?php echo $progress; ?>% Complete</span>
                    <span>🏆 Expert</span>
                </div>
            </div>

            <!-- Continue Learning -->
            <?php if ($nextLesson): ?>
            <div class="continue-learning">
                <h3>📖 Continue Learning</h3>
                <p><strong><?php echo htmlspecialchars($nextLesson['title']); ?></strong></p>
                <p><?php echo htmlspecialchars($nextLesson['description']); ?></p>
                <a href="lesson.php?id=<?php echo $nextLesson['id']; ?>">
                    <button class="continue-btn">
                        Continue Learning →
                    </button>
                </a>
            </div>
            <?php else: ?>
            <div class="continue-learning" style="border-left-color: #f59e0b;">
                <h3>🎉 Congratulations!</h3>
                <p>You've completed all available lessons! Check back later for new content.</p>
            </div>
            <?php endif; ?>

            <!-- Two Column Layout: Achievements & Daily Challenge -->
            <div class="grid-2col">
                <!-- Recent Achievements -->
                <div class="achievements-section">
                    <h3>🏅 Recent Achievements</h3>
                    <?php if (count($recentBadges) > 0): ?>
                        <div class="badge-list">
                            <?php foreach ($recentBadges as $badge): ?>
                                <div class="badge-item">
                                    <span class="badge-icon"><?php echo htmlspecialchars($badge['image']); ?></span>
                                    <?php echo htmlspecialchars($badge['name']); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-content">Complete your first quest to earn badges! 🎯</p>
                    <?php endif; ?>
                </div>

                <!-- Daily Challenge -->
                <div class="daily-challenge">
                    <h3>⚡ Daily Challenge</h3>
                    <?php if ($dailyQuest): ?>
                        <div class="challenge-card">
                            <h4><?php echo htmlspecialchars($dailyQuest['title']); ?></h4>
                            <p><?php echo htmlspecialchars($dailyQuest['description']); ?></p>
                            <p class="xp-reward">⭐ Reward: <?php echo $dailyQuest['xp_reward']; ?> XP</p>
                        </div>
                    <?php else: ?>
                        <p class="no-content">No challenges available at the moment. Check back soon!</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="learn.php" class="action-btn primary">
                    📖 Browse Lessons
                </a>
                <a href="quests.php" class="action-btn secondary">
                    🎯 View Quests
                </a>
                <a href="certificates.php" class="action-btn gold">
                    🏆 My Certificates
                </a>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../javascript/script.js"></script>
</body>
</html>