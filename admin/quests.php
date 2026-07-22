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

// Search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Check if status column exists in quests table
$statusColumnExists = false;
try {
    $result = $conn->query("SHOW COLUMNS FROM quests LIKE 'status'");
    if ($result && $result->num_rows > 0) {
        $statusColumnExists = true;
    }
} catch (Exception $e) {
    // Column doesn't exist
}

// If status column doesn't exist, add it
if (!$statusColumnExists) {
    try {
        $conn->query("ALTER TABLE quests ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
        $statusColumnExists = true;
    } catch (Exception $e) {
        // Could not add column
    }
}

// Delete quest
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    
    // First, delete user quest records
    $stmt = $conn->prepare("DELETE FROM user_quests WHERE quest_id = ?");
    $stmt->bind_param("i", $deleteId);
    $stmt->execute();
    
    // Then delete the quest
    $stmt = $conn->prepare("DELETE FROM quests WHERE id = ?");
    $stmt->bind_param("i", $deleteId);
    if ($stmt->execute()) {
        $message = "Quest deleted successfully!";
        $messageType = "success";
    } else {
        $message = "Error deleting quest: " . $conn->error;
        $messageType = "error";
    }
}

// Toggle quest status (active/inactive) - only if status column exists
if (isset($_GET['toggle']) && is_numeric($_GET['toggle']) && $statusColumnExists) {
    $questId = intval($_GET['toggle']);
    
    $stmt = $conn->prepare("UPDATE quests SET status = IF(status='active', 'inactive', 'active') WHERE id = ?");
    $stmt->bind_param("i", $questId);
    if ($stmt->execute()) {
        $message = "Quest status toggled successfully!";
        $messageType = "success";
    } else {
        $message = "Error toggling quest status: " . $conn->error;
        $messageType = "error";
    }
}

// Build query with filters
$whereConditions = [];
$params = [];
$types = "";

if (!empty($search)) {
    $whereConditions[] = "(title LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

// Only add status filter if column exists
if (!empty($statusFilter) && $statusColumnExists) {
    $whereConditions[] = "status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get total quests
$countQuery = "SELECT COUNT(*) AS total FROM quests $whereClause";
$stmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totalQuests = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$totalPages = ceil($totalQuests / $limit);

// Build select query
$selectFields = "q.*";
if ($statusColumnExists) {
    $selectFields = "q.*";
} else {
    // If status column doesn't exist, add a default status
    $selectFields = "q.*, 'active' as status";
}

// Get quests with pagination
$query = "SELECT $selectFields, 
          (SELECT COUNT(*) FROM user_quests WHERE quest_id = q.id AND status = 'completed') as completed_count,
          (SELECT COUNT(*) FROM user_quests WHERE quest_id = q.id) as total_attempts
          FROM quests q
          $whereClause 
          ORDER BY q.created_at DESC 
          LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$params[] = $limit;
$params[] = $offset;
$types .= "ii";
$stmt->bind_param($types, ...$params);
$stmt->execute();
$quests = $stmt->get_result();

// Get statistics
$result = $conn->query("SELECT COUNT(*) AS total FROM quests");
$totalAllQuests = $result->fetch_assoc()['total'] ?? 0;

// Handle status-based stats
if ($statusColumnExists) {
    $result = $conn->query("SELECT COUNT(*) AS total FROM quests WHERE status = 'active'");
    $activeQuests = $result->fetch_assoc()['total'] ?? 0;
    
    $result = $conn->query("SELECT COUNT(*) AS total FROM quests WHERE status = 'inactive'");
    $inactiveQuests = $result->fetch_assoc()['total'] ?? 0;
} else {
    // If no status column, all quests are considered active
    $activeQuests = $totalAllQuests;
    $inactiveQuests = 0;
}

$result = $conn->query("SELECT COUNT(*) AS total FROM user_quests WHERE status = 'completed'");
$completedQuests = $result->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quest Management - Admin Panel</title>
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="../assets/style.css">
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="../assets/admin.css">
    
    <style>
        /* Quest Management Specific Styles */
        .quest-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .quest-stat-card {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            border: 1px solid #f0f0f0;
            text-align: center;
        }
        
        .quest-stat-card .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .quest-stat-card .stat-label {
            font-size: 0.85rem;
            color: #666;
        }
        
        .quest-stat-card.green .stat-number { color: #03a60c; }
        .quest-stat-card.blue .stat-number { color: #3b82f6; }
        .quest-stat-card.orange .stat-number { color: #f59e0b; }
        .quest-stat-card.purple .stat-number { color: #8b5cf6; }
        
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            border: 1px solid #f0f0f0;
            overflow: hidden;
        }
        
        .table-header {
            padding: 18px 25px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .table-header h3 {
            margin: 0;
            font-size: 16px;
            color: #1a1a2e;
        }
        
        .table-controls {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .table-controls input[type="text"],
        .table-controls select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s;
        }
        
        .table-controls input[type="text"]:focus,
        .table-controls select:focus {
            border-color: #03a60c;
        }
        
        .table-controls .btn-search {
            padding: 8px 16px;
            background: #03a60c;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s;
        }
        
        .table-controls .btn-search:hover {
            background: #028c0a;
        }
        
        .table-controls .btn-reset {
            padding: 8px 16px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s;
            text-decoration: none;
            font-size: 14px;
        }
        
        .table-controls .btn-reset:hover {
            background: #5a6268;
        }
        
        .table-wrapper {
            overflow-x: auto;
            padding: 0 5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        table thead {
            background: #f8f9fa;
        }
        
        table th {
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #1a1a2e;
            border-bottom: 2px solid #e9ecef;
            white-space: nowrap;
        }
        
        table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }
        
        table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .quest-title {
            font-weight: 500;
            color: #1a1a2e;
        }
        
        .quest-description {
            font-size: 13px;
            color: #666;
            margin-top: 3px;
        }
        
        .xp-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 12px;
            background: #fff3cd;
            color: #856404;
            font-weight: 600;
        }
        
        .badge-status {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-status.active {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-status.inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-status.unknown {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .completion-stats {
            font-size: 12px;
            color: #666;
        }
        
        .completion-stats .completed {
            color: #03a60c;
            font-weight: 600;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .action-btn {
            padding: 4px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.3s;
        }
        
        .action-btn.view {
            background: #e3f2fd;
            color: #0d47a1;
        }
        
        .action-btn.view:hover {
            background: #bbdefb;
        }
        
        .action-btn.edit {
            background: #fff3e0;
            color: #e65100;
        }
        
        .action-btn.edit:hover {
            background: #ffe0b2;
        }
        
        .action-btn.toggle {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .action-btn.toggle:hover {
            background: #c8e6c9;
        }
        
        .action-btn.toggle.inactive {
            background: #fff3e0;
            color: #e65100;
        }
        
        .action-btn.toggle.inactive:hover {
            background: #ffe0b2;
        }
        
        .action-btn.delete {
            background: #ffebee;
            color: #b71c1c;
        }
        
        .action-btn.delete:hover {
            background: #ffcdd2;
        }
        
        .no-quests {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        
        .no-quests .icon {
            font-size: 48px;
            display: block;
            margin-bottom: 10px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            padding: 15px 20px;
            border-top: 1px solid #f0f0f0;
            flex-wrap: wrap;
        }
        
        .pagination a,
        .pagination span {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            color: #1a1a2e;
            font-size: 14px;
        }
        
        .pagination a:hover {
            background: #03a60c;
            color: white;
        }
        
        .pagination .active {
            background: #03a60c;
            color: white;
        }
        
        .pagination .disabled {
            color: #ccc;
            cursor: not-allowed;
        }
        
        .toast-message {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }
        
        .toast-message.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .toast-message.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        @media (max-width: 768px) {
            .table-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .table-controls {
                flex-direction: column;
            }
            
            .table-controls input[type="text"],
            .table-controls select {
                width: 100%;
            }
            
            .quest-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .action-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php require_once("../includes/admin_sidebar.php"); ?>
    
    <div class="admin-content">
        <?php require_once("../includes/admin_navbar.php"); ?>
        
        <div style="margin-top: 20px;">
            <!-- Page Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                <h2 style="margin: 0; color: #1a1a2e;">🎯 Quest Management</h2>
                <a href="quest_form.php" class="action-btn edit" style="padding: 10px 20px; font-size: 14px;">
                    ➕ Add New Quest
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
            <div class="quest-stats-grid">
                <div class="quest-stat-card green">
                    <div class="stat-number"><?php echo $totalAllQuests; ?></div>
                    <div class="stat-label">Total Quests</div>
                </div>
                <div class="quest-stat-card blue">
                    <div class="stat-number"><?php echo $activeQuests; ?></div>
                    <div class="stat-label">Active Quests</div>
                </div>
                <div class="quest-stat-card orange">
                    <div class="stat-number"><?php echo $inactiveQuests; ?></div>
                    <div class="stat-label">Inactive Quests</div>
                </div>
                <div class="quest-stat-card purple">
                    <div class="stat-number"><?php echo $completedQuests; ?></div>
                    <div class="stat-label">Total Completed</div>
                </div>
            </div>
            
            <!-- Quests Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3>📋 All Quests (<?php echo $totalQuests; ?> found)</h3>
                    
                    <form method="GET" action="" class="table-controls">
                        <input type="text" name="search" placeholder="Search by title or description..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <?php if ($statusColumnExists): ?>
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $statusFilter == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $statusFilter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                        <?php endif; ?>
                        <button type="submit" class="btn-search">🔍 Search</button>
                        <?php if ($search || $statusFilter): ?>
                        <a href="quests.php" class="btn-reset">↺ Reset</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Quest</th>
                                <th>XP Reward</th>
                                <th>Status</th>
                                <th>Completion Stats</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($quests->num_rows > 0): ?>
                                <?php while ($quest = $quests->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="quest-title">
                                            <?php echo htmlspecialchars($quest['title']); ?>
                                            <?php if ($quest['description']): ?>
                                                <div class="quest-description">
                                                    <?php echo htmlspecialchars(substr($quest['description'], 0, 60)) . (strlen($quest['description']) > 60 ? '...' : ''); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="xp-badge">⭐ <?php echo $quest['xp_reward']; ?> XP</span>
                                    </td>
                                    <td>
                                        <?php 
                                        $status = $quest['status'] ?? 'active';
                                        ?>
                                        <span class="badge-status <?php echo $status; ?>">
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="completion-stats">
                                            <span class="completed"><?php echo $quest['completed_count'] ?? 0; ?></span> completed
                                            <span style="color: #999;">/ <?php echo $quest['total_attempts'] ?? 0; ?> attempts</span>
                                        </div>
                                    </td>
                                    <td style="font-size: 12px; color: #999;">
                                        <?php echo date('M d, Y', strtotime($quest['created_at'])); ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="quest_form.php?action=view&id=<?php echo $quest['id']; ?>" 
                                               class="action-btn view" title="View">
                                                👁️ View
                                            </a>
                                            <a href="quest_form.php?id=<?php echo $quest['id']; ?>" 
                                               class="action-btn edit" title="Edit">
                                                ✏️ Edit
                                            </a>
                                            <?php if ($statusColumnExists): ?>
                                                <a href="quests.php?toggle=<?php echo $quest['id']; ?>" 
                                                   class="action-btn toggle <?php echo $status == 'inactive' ? 'inactive' : ''; ?>" 
                                                   title="<?php echo $status == 'active' ? 'Deactivate' : 'Activate'; ?>"
                                                   onclick="return confirm('Are you sure you want to <?php echo $status == 'active' ? 'deactivate' : 'activate'; ?> this quest?');">
                                                    <?php echo $status == 'active' ? '📤 Deactivate' : '📥 Activate'; ?>
                                                </a>
                                            <?php endif; ?>
                                            <a href="quests.php?delete=<?php echo $quest['id']; ?>" 
                                               class="action-btn delete" 
                                               title="Delete Quest"
                                               onclick="return confirm('Are you sure you want to delete this quest? This action cannot be undone!');">
                                                🗑️ Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="no-quests">
                                            <span class="icon">🎯</span>
                                            <p>No quests found matching your criteria.</p>
                                            <p style="font-size: 13px; color: #ccc;">
                                                <a href="quest_form.php" style="color: #03a60c; text-decoration: none; font-weight: 500;">
                                                    Click here to create your first quest →
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
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>">◀ Previous</a>
                    <?php else: ?>
                        <span class="disabled">◀ Previous</span>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>">Next ▶</a>
                    <?php else: ?>
                        <span class="disabled">Next ▶</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../javascript/script.js"></script>

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