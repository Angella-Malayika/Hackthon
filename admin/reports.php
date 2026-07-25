<?php
require_once("../middleware/admin.php");
require_once("../core.php");

// Get admin info
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['fullname'];

// Get date range filters
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Get report type
$reportType = isset($_GET['type']) ? $_GET['type'] : 'overview';

// ============================================
// 1. USER STATISTICS
// ============================================

// Total users
$result = $conn->query("SELECT COUNT(*) AS total FROM users");
$totalUsers = $result->fetch_assoc()['total'] ?? 0;

// New users this month
$result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
$newUsersThisMonth = $result->fetch_assoc()['total'] ?? 0;

// Users by role (if role column exists)
$usersByRole = [];
try {
    $result = $conn->query("SELECT role, COUNT(*) AS count FROM users GROUP BY role");
    if ($result) {
        $usersByRole = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    // Column might not exist
}

// Users by status (if status column exists)
$usersByStatus = [];
try {
    $result = $conn->query("SELECT status, COUNT(*) AS count FROM users GROUP BY status");
    if ($result) {
        $usersByStatus = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    // Column might not exist
}

// ============================================
// 2. LESSON STATISTICS
// ============================================

// Total lessons
$result = $conn->query("SELECT COUNT(*) AS total FROM lessons");
$totalLessons = $result->fetch_assoc()['total'] ?? 0;

// Lessons by status (if status column exists)
$lessonsByStatus = [];
try {
    $result = $conn->query("SELECT status, COUNT(*) AS count FROM lessons GROUP BY status");
    if ($result) {
        $lessonsByStatus = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    // Column might not exist
}

// Lessons by category
$result = $conn->query("SELECT c.name, COUNT(l.id) AS count 
                        FROM categories c 
                        LEFT JOIN lessons l ON c.id = l.category_id 
                        GROUP BY c.id");
$lessonsByCategory = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// ============================================
// 3. QUEST STATISTICS
// ============================================

// Total quests
$result = $conn->query("SELECT COUNT(*) AS total FROM quests");
$totalQuests = $result->fetch_assoc()['total'] ?? 0;

// Quests by status (if status column exists)
$questsByStatus = [];
try {
    $result = $conn->query("SELECT status, COUNT(*) AS count FROM quests GROUP BY status");
    if ($result) {
        $questsByStatus = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    // Column might not exist - use default values
    $questsByStatus = [
        ['status' => 'active', 'count' => $totalQuests],
        ['status' => 'inactive', 'count' => 0]
    ];
}

// Quest completion rate
$result = $conn->query("SELECT 
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
    COUNT(*) as total
    FROM user_quests");
$questStats = $result ? $result->fetch_assoc() : ['completed' => 0, 'pending' => 0, 'total' => 0];
$questCompletionRate = ($questStats['total'] ?? 0) > 0 ? round((($questStats['completed'] ?? 0) / ($questStats['total'] ?? 1)) * 100) : 0;

// ============================================
// 4. PROGRESS & ENGAGEMENT
// ============================================

// Lesson progress stats
$result = $conn->query("SELECT 
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
    COUNT(CASE WHEN status = 'not_started' THEN 1 END) as not_started,
    COUNT(*) as total
    FROM lesson_progress");
$progressStats = $result ? $result->fetch_assoc() : ['completed' => 0, 'in_progress' => 0, 'not_started' => 0, 'total' => 0];

// Average progress per user
$userProgress = [];
try {
    $result = $conn->query("SELECT 
        user_id,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
        (SELECT COUNT(*) FROM lessons WHERE status = 'published') as total_lessons
        FROM lesson_progress 
        GROUP BY user_id");
    if ($result) {
        $userProgress = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    // Query might fail
}

$avgProgress = 0;
if (count($userProgress) > 0) {
    $totalProgress = 0;
    foreach ($userProgress as $up) {
        $totalLessons = $up['total_lessons'] ?? 1;
        if ($totalLessons > 0) {
            $totalProgress += round(($up['completed'] / $totalLessons) * 100);
        }
    }
    $avgProgress = round($totalProgress / count($userProgress));
}

// ============================================
// 5. BADGE STATISTICS
// ============================================

$result = $conn->query("SELECT COUNT(*) AS total FROM badges");
$totalBadges = $result ? $result->fetch_assoc()['total'] ?? 0 : 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM user_badges");
$totalBadgesEarned = $result ? $result->fetch_assoc()['total'] ?? 0 : 0;

$result = $conn->query("SELECT COUNT(DISTINCT user_id) AS total FROM user_badges");
$usersWithBadges = $result ? $result->fetch_assoc()['total'] ?? 0 : 0;

// ============================================
// 6. CERTIFICATE STATISTICS
// ============================================

$result = $conn->query("SELECT COUNT(*) AS total FROM certificates");
$totalCertificates = $result ? $result->fetch_assoc()['total'] ?? 0 : 0;

$result = $conn->query("SELECT COUNT(DISTINCT user_id) AS total FROM certificates");
$usersWithCertificates = $result ? $result->fetch_assoc()['total'] ?? 0 : 0;

// ============================================
// 7. FEEDBACK STATISTICS
// ============================================

$result = $conn->query("SELECT COUNT(*) AS total FROM feedback");
$totalFeedback = $result ? $result->fetch_assoc()['total'] ?? 0 : 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM feedback WHERE DATE(created_at) >= CURDATE() - INTERVAL 7 DAY");
$feedbackThisWeek = $result ? $result->fetch_assoc()['total'] ?? 0 : 0;

// ============================================
// 8. MONTHLY ACTIVITY (Last 12 months)
// ============================================

$monthlyData = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthName = date('M', strtotime("-$i months"));
    
    // New users
    $result = $conn->query("SELECT COUNT(*) AS count FROM users WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'");
    $newUsers = $result ? $result->fetch_assoc()['count'] ?? 0 : 0;
    
    // Lessons completed
    $result = $conn->query("SELECT COUNT(*) AS count FROM lesson_progress 
                            WHERE status = 'completed' 
                            AND DATE_FORMAT(completed_at, '%Y-%m') = '$month'");
    $lessonsCompleted = $result ? $result->fetch_assoc()['count'] ?? 0 : 0;
    
    // Quests completed
    $result = $conn->query("SELECT COUNT(*) AS count FROM user_quests 
                            WHERE status = 'completed' 
                            AND DATE_FORMAT(completed_at, '%Y-%m') = '$month'");
    $questsCompleted = $result ? $result->fetch_assoc()['count'] ?? 0 : 0;
    
    // Certificates issued
    $result = $conn->query("SELECT COUNT(*) AS count FROM certificates 
                            WHERE DATE_FORMAT(issued_at, '%Y-%m') = '$month'");
    $certificatesIssued = $result ? $result->fetch_assoc()['count'] ?? 0 : 0;
    
    $monthlyData[] = [
        'month' => $monthName,
        'new_users' => $newUsers,
        'lessons_completed' => $lessonsCompleted,
        'quests_completed' => $questsCompleted,
        'certificates' => $certificatesIssued
    ];
}

// ============================================
// 9. TOP PERFORMERS
// ============================================

// Most active users (by lessons completed)
$topUsers = [];
try {
    $result = $conn->query("SELECT u.fullname, u.email, COUNT(lp.id) as completed_count
                            FROM users u
                            JOIN lesson_progress lp ON u.id = lp.user_id
                            WHERE lp.status = 'completed'
                            GROUP BY u.id
                            ORDER BY completed_count DESC
                            LIMIT 5");
    if ($result) {
        $topUsers = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    // Query might fail
}

// Most completed lessons
$topLessons = [];
try {
    $result = $conn->query("SELECT l.title, COUNT(lp.id) as completed_count
                            FROM lessons l
                            JOIN lesson_progress lp ON l.id = lp.lesson_id
                            WHERE lp.status = 'completed'
                            GROUP BY l.id
                            ORDER BY completed_count DESC
                            LIMIT 5");
    if ($result) {
        $topLessons = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    // Query might fail
}

// Most earned badges
$topBadges = [];
try {
    $result = $conn->query("SELECT b.name, b.image, COUNT(ub.id) as earned_count
                            FROM badges b
                            JOIN user_badges ub ON b.id = ub.badge_id
                            GROUP BY b.id
                            ORDER BY earned_count DESC
                            LIMIT 5");
    if ($result) {
        $topBadges = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    // Query might fail
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Admin Panel</title>
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="../assets/style.css">
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="../assets/admin.css">
    
    <!-- Chart.js for charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* Reports & Analytics Specific Styles */
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .report-filters {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .report-filters input[type="date"] {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
        }
        
        .report-filters input[type="date"]:focus {
            border-color: #03a60c;
        }
        
        .report-filters .btn-filter {
            padding: 8px 20px;
            background: #03a60c;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s;
        }
        
        .report-filters .btn-filter:hover {
            background: #028c0a;
        }
        
        .report-filters .btn-export {
            padding: 8px 20px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .report-filters .btn-export:hover {
            background: #5a6268;
        }
        
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 18px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            border: 1px solid #f0f0f0;
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .stat-card .stat-icon {
            font-size: 28px;
            display: block;
            margin-bottom: 5px;
        }
        
        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 700;
        }
        
        .stat-card .stat-label {
            font-size: 0.85rem;
            color: #666;
            margin-top: 3px;
        }
        
        .stat-card .stat-change {
            font-size: 12px;
            margin-top: 5px;
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
        }
        
        .stat-card .stat-change.up {
            background: #d4edda;
            color: #155724;
        }
        
        .stat-card .stat-change.down {
            background: #f8d7da;
            color: #721c24;
        }
        
        .stat-card.green .stat-number { color: #03a60c; }
        .stat-card.blue .stat-number { color: #3b82f6; }
        .stat-card.purple .stat-number { color: #8b5cf6; }
        .stat-card.orange .stat-number { color: #f59e0b; }
        .stat-card.red .stat-number { color: #ef4444; }
        .stat-card.teal .stat-number { color: #14b8a6; }
        .stat-card.pink .stat-number { color: #ec4899; }
        
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .chart-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            border: 1px solid #f0f0f0;
            padding: 20px;
        }
        
        .chart-container h3 {
            margin: 0 0 15px 0;
            color: #1a1a2e;
            font-size: 16px;
        }
        
        .chart-container .chart-wrapper {
            position: relative;
            height: 280px;
        }
        
        .chart-container.full-width {
            grid-column: 1 / -1;
        }
        
        .top-performers {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .performer-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            border: 1px solid #f0f0f0;
            padding: 20px;
        }
        
        .performer-card h3 {
            margin: 0 0 15px 0;
            color: #1a1a2e;
            font-size: 16px;
        }
        
        .performer-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .performer-item:last-child {
            border-bottom: none;
        }
        
        .performer-item .rank {
            font-weight: 700;
            color: #03a60c;
            font-size: 14px;
            min-width: 25px;
        }
        
        .performer-item .info {
            flex: 1;
        }
        
        .performer-item .info .name {
            font-weight: 500;
            color: #1a1a2e;
        }
        
        .performer-item .info .detail {
            font-size: 12px;
            color: #999;
        }
        
        .performer-item .count {
            font-weight: 600;
            color: #03a60c;
        }
        
        @media (max-width: 992px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .top-performers {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .top-performers {
                grid-template-columns: 1fr;
            }
            
            .report-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .report-filters {
                flex-direction: column;
            }
            
            .report-filters input[type="date"],
            .report-filters .btn-filter,
            .report-filters .btn-export {
                width: 100%;
            }
            
            .stats-overview {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .stats-overview {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php require_once("../includes/admin_sidebar.php"); ?>
    
    <div class="admin-content">
        <?php require_once("../includes/admin_navbar.php"); ?>
        
        <div style="margin-top: 20px;">
            <!-- Report Header -->
            <div class="report-header">
                <h2 style="margin: 0; color: #1a1a2e;">📈 Reports & Analytics</h2>
                
                <form method="GET" action="" class="report-filters">
                    <input type="date" name="date_from" value="<?php echo $dateFrom; ?>">
                    <span style="color: #999;">to</span>
                    <input type="date" name="date_to" value="<?php echo $dateTo; ?>">
                    <button type="submit" class="btn-filter">📊 Update</button>
                    <a href="reports.php?export=pdf" class="btn-export">📥 Export PDF</a>
                </form>
            </div>
            
            <!-- Stats Overview -->
            <div class="stats-overview">
                <div class="stat-card green">
                    <span class="stat-icon">👥</span>
                    <div class="stat-number"><?php echo $totalUsers; ?></div>
                    <div class="stat-label">Total Users</div>
                    <span class="stat-change up">+<?php echo $newUsersThisMonth; ?> this month</span>
                </div>
                
                <div class="stat-card blue">
                    <span class="stat-icon">📚</span>
                    <div class="stat-number"><?php echo $totalLessons; ?></div>
                    <div class="stat-label">Total Lessons</div>
                </div>
                
                <div class="stat-card purple">
                    <span class="stat-icon">🎯</span>
                    <div class="stat-number"><?php echo $totalQuests; ?></div>
                    <div class="stat-label">Total Quests</div>
                </div>
                
                <div class="stat-card orange">
                    <span class="stat-icon">🏅</span>
                    <div class="stat-number"><?php echo $totalBadges; ?></div>
                    <div class="stat-label">Total Badges</div>
                    <span class="stat-change up"><?php echo $totalBadgesEarned; ?> earned</span>
                </div>
                
                <div class="stat-card teal">
                    <span class="stat-icon">🏆</span>
                    <div class="stat-number"><?php echo $totalCertificates; ?></div>
                    <div class="stat-label">Certificates Issued</div>
                    <span class="stat-change up"><?php echo $usersWithCertificates; ?> users</span>
                </div>
                
                <div class="stat-card pink">
                    <span class="stat-icon">💬</span>
                    <div class="stat-number"><?php echo $totalFeedback; ?></div>
                    <div class="stat-label">Feedback Received</div>
                    <span class="stat-change up"><?php echo $feedbackThisWeek; ?> this week</span>
                </div>
                
                <div class="stat-card blue">
                    <span class="stat-icon">📈</span>
                    <div class="stat-number"><?php echo $avgProgress; ?>%</div>
                    <div class="stat-label">Avg. User Progress</div>
                </div>
                
                <div class="stat-card green">
                    <span class="stat-icon">✅</span>
                    <div class="stat-number"><?php echo $questCompletionRate; ?>%</div>
                    <div class="stat-label">Quest Completion Rate</div>
                </div>
            </div>
            
            <!-- Charts Grid -->
            <div class="charts-grid">
                <!-- Monthly Activity Chart -->
                <div class="chart-container full-width">
                    <h3>📊 Monthly Activity (Last 12 Months)</h3>
                    <div class="chart-wrapper">
                        <canvas id="monthlyActivityChart"></canvas>
                    </div>
                </div>
                
                <!-- Lessons by Category -->
                <div class="chart-container">
                    <h3>📚 Lessons by Category</h3>
                    <div class="chart-wrapper">
                        <canvas id="lessonsByCategoryChart"></canvas>
                    </div>
                </div>
                
                <!-- User Progress Distribution -->
                <div class="chart-container">
                    <h3>📊 User Progress Status</h3>
                    <div class="chart-wrapper">
                        <canvas id="progressStatusChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Top Performers -->
            <div class="top-performers">
                <!-- Top Users -->
                <div class="performer-card">
                    <h3>🏆 Top Users</h3>
                    <?php if (count($topUsers) > 0): ?>
                        <?php $rank = 1; ?>
                        <?php foreach ($topUsers as $user): ?>
                        <div class="performer-item">
                            <span class="rank">#<?php echo $rank++; ?></span>
                            <div class="info">
                                <div class="name"><?php echo htmlspecialchars($user['fullname']); ?></div>
                                <div class="detail"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                            <span class="count"><?php echo $user['completed_count']; ?> ✅</span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #999; text-align: center; padding: 20px;">No data available</p>
                    <?php endif; ?>
                </div>
                
                <!-- Top Lessons -->
                <div class="performer-card">
                    <h3>📚 Most Completed Lessons</h3>
                    <?php if (count($topLessons) > 0): ?>
                        <?php $rank = 1; ?>
                        <?php foreach ($topLessons as $lesson): ?>
                        <div class="performer-item">
                            <span class="rank">#<?php echo $rank++; ?></span>
                            <div class="info">
                                <div class="name"><?php echo htmlspecialchars($lesson['title']); ?></div>
                            </div>
                            <span class="count"><?php echo $lesson['completed_count']; ?> ✅</span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #999; text-align: center; padding: 20px;">No data available</p>
                    <?php endif; ?>
                </div>
                
                <!-- Top Badges -->
                <div class="performer-card">
                    <h3>🏅 Most Earned Badges</h3>
                    <?php if (count($topBadges) > 0): ?>
                        <?php $rank = 1; ?>
                        <?php foreach ($topBadges as $badge): ?>
                        <div class="performer-item">
                            <span class="rank">#<?php echo $rank++; ?></span>
                            <div class="info">
                                <div class="name"><?php echo htmlspecialchars($badge['image']); ?> <?php echo htmlspecialchars($badge['name']); ?></div>
                            </div>
                            <span class="count"><?php echo $badge['earned_count']; ?> 🏅</span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #999; text-align: center; padding: 20px;">No data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../javascript/script.js"></script>
    <script src="../assets/admin.js"></script>
    
    <script>
        // ============================================
        // 1. MONTHLY ACTIVITY CHART
        // ============================================
        const monthlyCtx = document.getElementById('monthlyActivityChart').getContext('2d');
        const monthlyData = <?php echo json_encode($monthlyData); ?>;
        
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyData.map(d => d.month),
                datasets: [
                    {
                        label: 'New Users',
                        data: monthlyData.map(d => d.new_users),
                        borderColor: '#03a60c',
                        backgroundColor: 'rgba(3, 166, 12, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Lessons Completed',
                        data: monthlyData.map(d => d.lessons_completed),
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Quests Completed',
                        data: monthlyData.map(d => d.quests_completed),
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Certificates Issued',
                        data: monthlyData.map(d => d.certificates),
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // ============================================
        // 2. LESSONS BY CATEGORY CHART
        // ============================================
        const categoryCtx = document.getElementById('lessonsByCategoryChart').getContext('2d');
        const categoryData = <?php echo json_encode($lessonsByCategory); ?>;
        
        const categoryColors = [
            '#03a60c', '#3b82f6', '#f59e0b', '#8b5cf6', '#ef4444',
            '#14b8a6', '#ec4899', '#f97316', '#6366f1', '#06b6d4'
        ];
        
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: categoryData.length > 0 ? categoryData.map(d => d.name || 'Uncategorized') : ['No Categories'],
                datasets: [{
                    data: categoryData.length > 0 ? categoryData.map(d => d.count) : [1],
                    backgroundColor: categoryData.length > 0 ? categoryColors.slice(0, categoryData.length) : ['#e5e7eb'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 15
                        }
                    }
                }
            }
        });

        // ============================================
        // 3. PROGRESS STATUS CHART
        // ============================================
        const progressCtx = document.getElementById('progressStatusChart').getContext('2d');
        const progressData = <?php echo json_encode($progressStats); ?>;
        
        new Chart(progressCtx, {
            type: 'bar',
            data: {
                labels: ['Completed', 'In Progress', 'Not Started'],
                datasets: [{
                    label: 'Lesson Progress',
                    data: [
                        progressData.completed || 0,
                        progressData.in_progress || 0,
                        progressData.not_started || 0
                    ],
                    backgroundColor: [
                        '#03a60c',
                        '#f59e0b',
                        '#e5e7eb'
                    ],
                    borderColor: [
                        '#028c0a',
                        '#d97706',
                        '#d1d5db'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>