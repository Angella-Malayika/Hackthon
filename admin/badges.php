<?php
require_once("../middleware/admin.php");
require_once("../core.php");

// Get admin info
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['fullname'];

// Handle actions
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$message = '';
$messageType = '';

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Delete badge
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    
    // First, delete user badge records
    $stmt = $conn->prepare("DELETE FROM user_badges WHERE badge_id = ?");
    $stmt->bind_param("i", $deleteId);
    $stmt->execute();
    
    // Then delete the badge
    $stmt = $conn->prepare("DELETE FROM badges WHERE id = ?");
    $stmt->bind_param("i", $deleteId);
    if ($stmt->execute()) {
        $message = "Badge deleted successfully!";
        $messageType = "success";
    } else {
        $message = "Error deleting badge: " . $conn->error;
        $messageType = "error";
    }
}

// Build query with filters
$whereConditions = [];
$params = [];
$types = "";

if (!empty($search)) {
    $whereConditions[] = "(name LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get total badges
$countQuery = "SELECT COUNT(*) AS total FROM badges $whereClause";
$stmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totalBadges = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$totalPages = ceil($totalBadges / $limit);

// Get badges with pagination
$query = "SELECT b.*, 
          (SELECT COUNT(*) FROM user_badges WHERE badge_id = b.id) as earned_count
          FROM badges b
          $whereClause 
          ORDER BY b.id ASC 
          LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$params[] = $limit;
$params[] = $offset;
$types .= "ii";
$stmt->bind_param($types, ...$params);
$stmt->execute();
$badges = $stmt->get_result();

// Get statistics
$result = $conn->query("SELECT COUNT(*) AS total FROM badges");
$totalAllBadges = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM user_badges");
$totalEarned = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT COUNT(DISTINCT user_id) AS total FROM user_badges");
$usersWithBadges = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT b.name, COUNT(ub.id) as count 
                        FROM badges b 
                        LEFT JOIN user_badges ub ON b.id = ub.badge_id 
                        GROUP BY b.id 
                        ORDER BY count DESC 
                        LIMIT 1");
$mostPopular = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Badge Management - Admin Panel</title>
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>
    <?php require_once("../includes/admin_sidebar.php"); ?>
    
    <div class="admin-content">
        <?php require_once("../includes/admin_navbar.php"); ?>
        
        <div style="margin-top: 20px;">
            <!-- Page Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                <h2 style="margin: 0; color: #1a1a2e;">🏅 Badge Management</h2>
                <a href="badge_form.php" class="action-btn edit" style="padding: 10px 20px; font-size: 14px;">
                    ➕ Add New Badge
                </a>
            </div>
            
            <!-- Display Message -->
            <?php if ($message): ?>
            <div class="toast-message <?php echo $messageType; ?>">
                <span><?php echo $messageType == 'success' ? '✅' : '❌'; ?></span>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="badge-stats-grid">
                <div class="badge-stat-card green">
                    <div class="stat-number"><?php echo $totalAllBadges; ?></div>
                    <div class="stat-label">Total Badges</div>
                </div>
                <div class="badge-stat-card blue">
                    <div class="stat-number"><?php echo $totalEarned; ?></div>
                    <div class="stat-label">Total Earned</div>
                    <div class="stat-sub">Badges awarded to users</div>
                </div>
                <div class="badge-stat-card purple">
                    <div class="stat-number"><?php echo $usersWithBadges; ?></div>
                    <div class="stat-label">Users with Badges</div>
                    <div class="stat-sub">Unique users who earned badges</div>
                </div>
                <div class="badge-stat-card gold">
                    <div class="stat-number"><?php echo $mostPopular ? $mostPopular['name'] : 'N/A'; ?></div>
                    <div class="stat-label">Most Popular Badge</div>
                    <div class="stat-sub"><?php echo $mostPopular ? $mostPopular['count'] . ' users earned this' : 'No badges yet'; ?></div>
                </div>
            </div>
            
            <!-- Badges Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3>📋 All Badges (<?php echo $totalBadges; ?> found)</h3>
                    
                    <form method="GET" action="" class="table-controls">
                        <input type="text" name="search" placeholder="Search by name or description..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn-search">🔍 Search</button>
                        <?php if ($search): ?>
                        <a href="badges.php" class="btn-reset">↺ Reset</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Badge</th>
                                <th>Description</th>
                                <th>Earned By</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($badges->num_rows > 0): ?>
                                <?php while ($badge = $badges->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="badge-display">
                                            <div class="badge-icon">
                                                <?php echo htmlspecialchars($badge['image'] ?: '🏅'); ?>
                                            </div>
                                            <div class="badge-info">
                                                <div class="name"><?php echo htmlspecialchars($badge['name']); ?></div>
                                                <?php if ($badge['requirement']): ?>
                                                    <div class="requirement">📋 <?php echo htmlspecialchars($badge['requirement']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars(substr($badge['description'], 0, 60)) . (strlen($badge['description']) > 60 ? '...' : ''); ?>
                                    </td>
                                    <td>
                                        <span class="earned-count">👥 <?php echo $badge['earned_count']; ?> users</span>
                                    </td>
                                    <td style="font-size: 12px; color: #999;">
                                        <?php echo date('M d, Y', strtotime($badge['created_at'])); ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="badge_form.php?action=view&id=<?php echo $badge['id']; ?>" 
                                               class="action-btn view" title="View">
                                                👁️ View
                                            </a>
                                            <a href="badge_form.php?id=<?php echo $badge['id']; ?>" 
                                               class="action-btn edit" title="Edit">
                                                ✏️ Edit
                                            </a>
                                            <a href="badges.php?delete=<?php echo $badge['id']; ?>" 
                                               class="action-btn delete" 
                                               title="Delete Badge"
                                               onclick="return confirm('Are you sure you want to delete this badge? This action cannot be undone!');">
                                                🗑️ Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="no-badges">
                                            <span class="icon">🏅</span>
                                            <p>No badges found matching your criteria.</p>
                                            <p style="font-size: 13px; color: #ccc;">
                                                <a href="badge_form.php" style="color: #03a60c; text-decoration: none; font-weight: 500;">
                                                    Click here to create your first badge →
                                                </a>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">◀ Previous</a>
                    <?php else: ?>
                        <span class="disabled">◀ Previous</span>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Next ▶</a>
                    <?php else: ?>
                        <span class="disabled">Next ▶</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Auto-hide toast messages after 5 seconds
        setTimeout(function() {
            const toast = document.querySelector('.toast-message');
            if (toast) {
                toast.style.display = 'none';
            }
        }, 5000);
    </script>
</body>
</html>