<?php
require_once("../middleware/admin.php");
require_once("../core.php");

// Get admin info
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['fullname'];

// Handle actions
$action = $_GET['action'] ?? 'list';
$message = '';
$messageType = '';

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Delete user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    
    // Don't allow admin to delete themselves
    if ($deleteId == $admin_id) {
        $message = "You cannot delete your own account!";
        $messageType = "error";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $deleteId);
        if ($stmt->execute()) {
            $message = "User deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Error deleting user: " . $conn->error;
            $messageType = "error";
        }
    }
}

// Block/Unblock user (toggle status)
if (isset($_GET['block']) && is_numeric($_GET['block'])) {
    $userId = intval($_GET['block']);
    
    // Don't allow admin to block themselves
    if ($userId == $admin_id) {
        $message = "You cannot block your own account!";
        $messageType = "error";
    } else {
        // Get current status
        $stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user) {
            $newStatus = ($user['status'] == 'active') ? 'blocked' : 'active';
            $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $newStatus, $userId);
            if ($stmt->execute()) {
                $message = "User " . ($newStatus == 'active' ? 'unblocked' : 'blocked') . " successfully!";
                $messageType = "success";
            } else {
                $message = "Error updating user status: " . $conn->error;
                $messageType = "error";
            }
        }
    }
}

// Build query with filters
$whereConditions = [];
$params = [];
$types = "";

if (!empty($search)) {
    $whereConditions[] = "(fullname LIKE ? OR email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

if (!empty($statusFilter)) {
    $whereConditions[] = "status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get total users
$countQuery = "SELECT COUNT(*) AS total FROM users $whereClause";
$stmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totalUsers = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$totalPages = ceil($totalUsers / $limit);

// Get users with pagination
$query = "SELECT id, fullname, email, role, status, xp, level, profile_picture, created_at 
          FROM users 
          $whereClause 
          ORDER BY created_at DESC 
          LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$params[] = $limit;
$params[] = $offset;
$types .= "ii";
$stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result();

// Get statistics for cards
$result = $conn->query("SELECT COUNT(*) AS total FROM users");
$totalAllUsers = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE status = 'active'");
$activeUsers = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE status = 'blocked'");
$blockedUsers = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'admin'");
$adminUsers = $result->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Panel</title>
    
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
                <h2 style="margin: 0; color: #1a1a2e;">👥 User Management</h2>
                <div style="display: flex; gap: 10px;">
                    <button onclick="exportUsersToCSV()" class="action-btn edit" style="padding: 10px 20px; font-size: 14px;">
                        📥 Export CSV
                    </button>
                    <a href="user_form.php" class="action-btn edit" style="padding: 10px 20px; font-size: 14px;">
                        ➕ Add New User
                    </a>
                </div>
            </div>
            
            <!-- Display Message -->
            <?php if ($message): ?>
            <div class="toast-message <?php echo $messageType; ?>">
                <span><?php echo $messageType == 'success' ? '✅' : '❌'; ?></span>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="users-stats-grid">
                <div class="users-stat-card green">
                    <div class="stat-number"><?php echo $totalAllUsers; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="users-stat-card blue">
                    <div class="stat-number"><?php echo $activeUsers; ?></div>
                    <div class="stat-label">Active Users</div>
                </div>
                <div class="users-stat-card red">
                    <div class="stat-number"><?php echo $blockedUsers; ?></div>
                    <div class="stat-label">Blocked Users</div>
                </div>
                <div class="users-stat-card purple">
                    <div class="stat-number"><?php echo $adminUsers; ?></div>
                    <div class="stat-label">Administrators</div>
                </div>
            </div>
            
            <!-- Users Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3>📋 All Users (<?php echo $totalUsers; ?> found)</h3>
                    
                    <form method="GET" action="" class="table-controls">
                        <input type="text" name="search" placeholder="Search by name or email..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $statusFilter == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="blocked" <?php echo $statusFilter == 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                        </select>
                        <button type="submit" class="btn-search">🔍 Search</button>
                        <?php if ($search || $statusFilter): ?>
                        <a href="users.php" class="btn-reset">↺ Reset</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>XP</th>
                                <th>Level</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($users->num_rows > 0): ?>
                                <?php while ($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($user['fullname'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="name"><?php echo htmlspecialchars($user['fullname']); ?></div>
                                                <?php if ($user['id'] == $admin_id): ?>
                                                    <span style="font-size: 10px; color: #03a60c; font-weight: 600;">(You)</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge-status <?php echo $user['role']; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-status <?php echo $user['status']; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $user['xp']; ?></td>
                                    <td><?php echo $user['level']; ?></td>
                                    <td style="font-size: 12px; color: #999;">
                                        <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <!-- View button opens the full progress dashboard for this user -->
                                            <a href="user_progress.php?id=<?php echo $user['id']; ?>" 
                                               class="action-btn view" title="View">
                                                👁️ View
                                            </a>
                                            
                                            <!-- UPDATED: Edit button now points to user_form.php -->
                                            <a href="user_form.php?id=<?php echo $user['id']; ?>" 
                                               class="action-btn edit" title="Edit">
                                                ✏️ Edit
                                            </a>
                                            
                                            <?php if ($user['id'] != $admin_id): ?>
                                                <?php if ($user['status'] == 'active'): ?>
                                                    <a href="users.php?block=<?php echo $user['id']; ?>" 
                                                       class="action-btn block" 
                                                       title="Block User"
                                                       onclick="return confirm('Are you sure you want to block this user?');">
                                                        🚫 Block
                                                    </a>
                                                <?php else: ?>
                                                    <a href="users.php?block=<?php echo $user['id']; ?>" 
                                                       class="action-btn unblock" 
                                                       title="Unblock User"
                                                       onclick="return confirm('Are you sure you want to unblock this user?');">
                                                        ✅ Unblock
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <a href="users.php?delete=<?php echo $user['id']; ?>" 
                                                   class="action-btn delete" 
                                                   title="Delete User"
                                                   onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone!');">
                                                    🗑️ Delete
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="no-users">
                                            <span class="icon">👤</span>
                                            <p>No users found matching your criteria.</p>
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
   
</body>
</html>