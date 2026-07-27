<?php
require_once("../middleware/admin.php");
require_once("../core.php");

// Get admin info
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['fullname'];

// Dashboard Statistics
// Total Users
$result = $conn->query("SELECT COUNT(*) AS total FROM users");
$totalUsers = $result->fetch_assoc()['total'] ?? 0;

// Total Lessons
$result = $conn->query("SELECT COUNT(*) AS total FROM lessons WHERE status='published'");
$totalLessons = $result->fetch_assoc()['total'] ?? 0;

// Total Categories
$result = $conn->query("SELECT COUNT(*) AS total FROM categories");
$totalCategories = $result->fetch_assoc()['total'] ?? 0;

// Total Quests
$result = $conn->query("SELECT COUNT(*) AS total FROM quests");
$totalQuests = $result->fetch_assoc()['total'] ?? 0;

// Total Certificates
$result = $conn->query("SELECT COUNT(*) AS total FROM certificates");
$totalCertificates = $result->fetch_assoc()['total'] ?? 0;

// Active Users (users who logged in within last 30 days)
$result = $conn->query("
    SELECT COUNT(DISTINCT user_id) AS total 
    FROM lesson_progress 
    WHERE completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$activeUsers = $result->fetch_assoc()['total'] ?? 0;

// Recent Activity (simulated for now - will be from activity_logs table later)
$recentActivities = [
    [
        'icon' => '📚',
        'icon_class' => 'green',
        'message' => 'New lesson created: "Internet Privacy"',
        'time' => '2 minutes ago'
    ],
    [
        'icon' => '👤',
        'icon_class' => 'blue',
        'message' => 'New user registered: John Doe',
        'time' => '15 minutes ago'
    ],
    [
        'icon' => '🎯',
        'icon_class' => 'gold',
        'message' => 'User Sarah completed "Cyber Security" lesson',
        'time' => '1 hour ago'
    ],
    [
        'icon' => '🏅',
        'icon_class' => 'purple',
        'message' => 'Badge "Cyber Hero" earned by Michael',
        'time' => '2 hours ago'
    ],
    [
        'icon' => '💬',
        'icon_class' => 'green',
        'message' => 'New feedback received from Emily',
        'time' => '3 hours ago'
    ]
];

// Quick Actions
$quickActions = [
    ['icon' => '➕', 'label' => 'Add Lesson', 'link' => 'lessons.php?action=add'],
    ['icon' => '📂', 'label' => 'Add Category', 'link' => 'categories.php?action=add'],
    ['icon' => '🎯', 'label' => 'Add Quest', 'link' => 'quests.php?action=add'],
    ['icon' => '👥', 'label' => 'Manage Users', 'link' => 'users.php']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Internet Governance Platform</title>
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="../assets/style.css">
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>
    <?php require_once("../includes/admin_sidebar.php"); ?>
    
    <div class="admin-content">
        <?php require_once("../includes/admin_navbar.php"); ?>
        
        <!-- Dashboard Content -->
        <div style="margin-top: 20px;">
            <!-- Welcome Banner -->
            <div style="background: linear-gradient(135deg, #03a60c 0%, #028c0a 100%); 
                        color: white; 
                        padding: 25px 30px; 
                        border-radius: 12px; 
                        margin-bottom: 30px;
                        box-shadow: 0 4px 20px rgba(3, 166, 12, 0.25);">
                <h2 style="margin: 0; font-weight: 600;">🛡️ Welcome back, <?php echo htmlspecialchars($admin_name); ?>!</h2>
                <p style="margin: 5px 0 0 0; opacity: 0.9;">Here's what's happening on your platform today.</p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="admin-stats-grid">
                <div class="admin-stat-card green">
                    <span class="stat-icon">👥</span>
                    <div class="stat-number" data-stat="users"><?php echo $totalUsers; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                
                <div class="admin-stat-card blue">
                    <span class="stat-icon">📚</span>
                    <div class="stat-number" data-stat="lessons"><?php echo $totalLessons; ?></div>
                    <div class="stat-label">Total Lessons</div>
                </div>
                
                <div class="admin-stat-card purple">
                    <span class="stat-icon">📂</span>
                    <div class="stat-number"><?php echo $totalCategories; ?></div>
                    <div class="stat-label">Categories</div>
                </div>
                
                <div class="admin-stat-card orange">
                    <span class="stat-icon">🎯</span>
                    <div class="stat-number"><?php echo $totalQuests; ?></div>
                    <div class="stat-label">Total Quests</div>
                </div>
                
                <div class="admin-stat-card gold">
                    <span class="stat-icon">🏆</span>
                    <div class="stat-number"><?php echo $totalCertificates; ?></div>
                    <div class="stat-label">Certificates Issued</div>
                </div>
                
                <div class="admin-stat-card red">
                    <span class="stat-icon">✅</span>
                    <div class="stat-number"><?php echo $activeUsers; ?></div>
                    <div class="stat-label">Active Users (30 days)</div>
                </div>
            </div>
            
            <!-- Two Column Layout -->
            <div class="admin-grid-2col">
                <!-- Recent Activity -->
                
                
                <!-- Quick Actions -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3>⚡ Quick Actions</h3>
                    </div>
                    <div class="admin-card-body">
                        <div class="quick-actions-grid">
                            <?php foreach ($quickActions as $action): ?>
                            <a href="<?php echo $action['link']; ?>" class="quick-action-btn">
                                <span class="action-icon"><?php echo $action['icon']; ?></span>
                                <span class="action-label"><?php echo $action['label']; ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../javascript/script.js"></script>
</body>
</html>