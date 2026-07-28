<?php
require_once("../middleware/user.php");
require_once("../core.php");
require_once("../includes/achievements.php");

// Logged in user
$user_id = $_SESSION['user_id'];

// Re-check achievements every time the dashboard loads, so anything that
// changed elsewhere (friends accepted, badges earned, etc.) shows up here.
evaluate_achievements($conn, $user_id);

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

// Completed quests (bonus quests + the 8-level Quest challenge combined)
$stmt = $conn->prepare("SELECT COUNT(*) AS completed FROM user_quests WHERE user_id=? AND status='completed'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$completedLegacyQuests = $stmt->get_result()->fetch_assoc()['completed'] ?? 0;

$completedQuestLevels = 0;
if (table_exists($conn, 'quest_progress')) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS completed FROM quest_progress WHERE user_id=? AND status='completed'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $completedQuestLevels = $stmt->get_result()->fetch_assoc()['completed'] ?? 0;
}
$completedQuests = $completedLegacyQuests + $completedQuestLevels;

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

// Recent achievements - the 3 most recently unlocked, shown on the dashboard.
// "More" opens the full achievements page (all 100).
$recentAchievements = [];
$stmt = $conn->prepare("
    SELECT a.title, a.icon, a.xp_reward, a.difficulty, ua.completed_at
    FROM user_achievements ua
    JOIN achievements a ON ua.achievement_id = a.id
    WHERE ua.user_id = ? AND ua.status = 'completed'
    ORDER BY ua.completed_at DESC
    LIMIT 3
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recentAchievements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$totalUnlockedCount = $conn->prepare("SELECT COUNT(*) AS c FROM user_achievements WHERE user_id = ? AND status = 'completed'");
$totalUnlockedCount->bind_param("i", $user_id);
$totalUnlockedCount->execute();
$totalUnlocked = $totalUnlockedCount->get_result()->fetch_assoc()['c'] ?? 0;

// Daily Challenges - always exactly 5, pulled from the achievements
// catalog and automatically refreshed every 24 hours (see includes/achievements.php)
$dailyChallenges = get_daily_challenges($conn, $user_id, 5);
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
                <h1> Welcome back, <?php echo htmlspecialchars($_SESSION['fullname']); ?>!</h1>
                <p>Continue your journey to become a cyber-aware citizen. Your progress is saved automatically.</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card green">
                    <div class="number"><?php echo $totalLessons; ?></div>
                    <div class="label">Total Lessons</div>
                </div>
                
                <div class="stat-card gold">
                    <div class="number"><?php echo $completedQuests; ?></div>
                    <div class="label">Completed Quests</div>
                </div>
                
                <div class="stat-card blue">
                    <div class="number"><?php echo $progress; ?>%</div>
                    <div class="label">Progress</div>
                </div>
                
                <div class="stat-card purple">
                    <div class="number"><?php echo $totalCertificates; ?></div>
                    <div class="label">Certificates</div>
                </div>
            </div>

            <!-- Progress Section -->
            <div class="progress-section">
                <h3> Your Learning Progress</h3>
                <p>You've completed <strong><?php echo $completedLessons; ?></strong> out of <strong><?php echo $totalLessons; ?></strong> lessons</p>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
                </div>
                <div class="progress-text">
                    <span> Beginner</span>
                    <span><?php echo $progress; ?>% Complete</span>
                    <span> Expert</span>
                </div>
            </div>

            <!-- Continue Learning -->
            <?php if ($nextLesson): ?>
            <div class="continue-learning">
                <h3> Continue Learning</h3>
                <p><strong><?php echo htmlspecialchars($nextLesson['title']); ?></strong></p>
                <p><?php echo htmlspecialchars($nextLesson['description']); ?></p>
                <a href="learn.php">
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
                    <h3> Recent Achievements <span style="font-weight:400;color:#999;font-size:13px;">(<?php echo $totalUnlocked; ?>/100 unlocked)</span></h3>
                    <?php if (count($recentAchievements) > 0): ?>
                        <div class="recent-ach-list">
                            <?php foreach ($recentAchievements as $ra): ?>
                                <div class="recent-ach-item">
                                    
                                    <div>
                                        <div class="ach-title"><?php echo htmlspecialchars($ra['title']); ?></div>
                                        <div class="ach-meta">+<?php echo (int)$ra['xp_reward']; ?> XP · <?php echo $ra['completed_at'] ? date('M j', strtotime($ra['completed_at'])) : ''; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="achievements.php" class="view-more-btn">See more achievements →</a>
                    <?php else: ?>
                        <p class="no-content">Complete a lesson or daily challenge to earn your first achievement! </p>
                        <a href="achievements.php" class="view-more-btn">Browse all achievements →</a>
                    <?php endif; ?>
                </div>

                <!-- Daily Challenge -->
                <div class="daily-challenge">
                    <h3> Daily Challenges</h3>
                    <?php if (!empty($dailyChallenges)): ?>
                        <div class="challenges-list">
                            <?php foreach ($dailyChallenges as $c): ?>
                                <div class="challenge-row <?php echo $c['is_completed'] ? 'completed' : ''; ?>">
                                    <span class="challenge-dot <?php echo $c['is_completed'] ? 'completed' : 'pending'; ?>"></span>
                                    <div class="challenge-row-body">
                                        <div class="cr-title">
                                            <?php echo htmlspecialchars($c['icon']); ?> <?php echo htmlspecialchars($c['title']); ?>
                                            <span class="difficulty-tag <?php echo htmlspecialchars($c['difficulty']); ?>"><?php echo htmlspecialchars($c['difficulty']); ?></span>
                                        </div>
                                        <div class="cr-desc"><?php echo htmlspecialchars($c['description']); ?></div>
                                    </div>
                                    <span class="cr-xp"><?php echo $c['is_completed'] ? '✓ Done' : '+' . (int)$c['xp_reward'] . ' XP'; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="challenge-refresh-note">🔄 A fresh set of 5 challenges unlocks every 24 hours.</p>
                    <?php else: ?>
                        <p class="no-content">No challenges available at the moment. Check back soon!</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="learn.php" class="action-btn primary">
                    Browse Lessons
                </a>
                <a href="quests.php" class="action-btn secondary">
                     View Quests
                </a>
                <a href="achievements.php" class="action-btn gold">
                     Achievements
                </a>
                <a href="certificates.php" class="action-btn gold">
                     My Certificates
                </a>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../javascript/script.js"></script>
</body>
</html>
