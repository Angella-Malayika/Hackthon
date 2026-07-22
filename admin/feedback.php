<?php
require_once("../middleware/admin.php");
require_once("../core.php");

// Get admin info
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['fullname'];

// Handle actions
$message = '';
$messageType = '';

// Search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Reply to feedback
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reply_feedback'])) {
    $feedback_id = intval($_POST['feedback_id']);
    $reply = trim($_POST['reply']);
    $status = trim($_POST['status']);
    
    if (empty($reply)) {
        $message = "Reply message is required!";
        $messageType = "error";
    } else {
        $stmt = $conn->prepare("UPDATE feedback SET 
            reply = ?, 
            replied_at = NOW(), 
            status = ?,
            replied_by = ? 
            WHERE id = ?");
        $stmt->bind_param("ssii", $reply, $status, $admin_id, $feedback_id);
        
        if ($stmt->execute()) {
            $message = "Reply sent successfully!";
            $messageType = "success";
            
            // Get user info for notification
            $userStmt = $conn->prepare("SELECT f.user_id, u.email, u.fullname 
                                        FROM feedback f 
                                        JOIN users u ON f.user_id = u.id 
                                        WHERE f.id = ?");
            $userStmt->bind_param("i", $feedback_id);
            $userStmt->execute();
            $userInfo = $userStmt->get_result()->fetch_assoc();
            
            // Add notification for user
            if ($userInfo) {
                $notifStmt = $conn->prepare("INSERT INTO notifications 
                    (user_id, title, message, is_read) 
                    VALUES (?, 'Feedback Response', ?, 0)");
                $notifMessage = "Admin has responded to your feedback: " . substr($reply, 0, 100) . "...";
                $notifStmt->bind_param("is", $userInfo['user_id'], $notifMessage);
                $notifStmt->execute();
            }
            
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'feedback.php';
                }, 1500);
            </script>";
        } else {
            $message = "Error sending reply: " . $conn->error;
            $messageType = "error";
        }
    }
}

// Delete feedback
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    
    $stmt = $conn->prepare("DELETE FROM feedback WHERE id = ?");
    $stmt->bind_param("i", $deleteId);
    if ($stmt->execute()) {
        $message = "Feedback deleted successfully!";
        $messageType = "success";
    } else {
        $message = "Error deleting feedback: " . $conn->error;
        $messageType = "error";
    }
}

// Mark as read/unread
if (isset($_GET['mark']) && is_numeric($_GET['mark'])) {
    $feedbackId = intval($_GET['mark']);
    $status = isset($_GET['status']) ? $_GET['status'] : 'read';
    
    $stmt = $conn->prepare("UPDATE feedback SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $feedbackId);
    if ($stmt->execute()) {
        $message = "Feedback marked as " . ($status == 'read' ? 'read' : 'unread') . "!";
        $messageType = "success";
    } else {
        $message = "Error updating status: " . $conn->error;
        $messageType = "error";
    }
}

// Build query with filters
$whereConditions = [];
$params = [];
$types = "";

if (!empty($search)) {
    $whereConditions[] = "(f.message LIKE ? OR u.fullname LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

if (!empty($statusFilter)) {
    $whereConditions[] = "f.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get total feedback
$countQuery = "SELECT COUNT(*) AS total FROM feedback f 
               LEFT JOIN users u ON f.user_id = u.id 
               $whereClause";
$stmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totalFeedback = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$totalPages = ceil($totalFeedback / $limit);

// Get feedback with pagination
$query = "SELECT f.*, u.fullname, u.email, u.profile_picture,
          (SELECT fullname FROM users WHERE id = f.replied_by) as replied_by_name
          FROM feedback f 
          LEFT JOIN users u ON f.user_id = u.id 
          $whereClause 
          ORDER BY 
            CASE WHEN f.status = 'unread' THEN 0 ELSE 1 END,
            f.created_at DESC
          LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$params[] = $limit;
$params[] = $offset;
$types .= "ii";
$stmt->bind_param($types, ...$params);
$stmt->execute();
$feedbacks = $stmt->get_result();

// Get statistics
$result = $conn->query("SELECT COUNT(*) AS total FROM feedback");
$totalAll = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM feedback WHERE status = 'unread'");
$unreadCount = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM feedback WHERE status = 'read'");
$readCount = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM feedback WHERE reply IS NOT NULL AND reply != ''");
$repliedCount = $result->fetch_assoc()['total'] ?? 0;

// Get recent feedback for sidebar
$recentFeedbacks = [];
$result = $conn->query("SELECT f.*, u.fullname 
                        FROM feedback f 
                        JOIN users u ON f.user_id = u.id 
                        ORDER BY f.created_at DESC 
                        LIMIT 5");
if ($result) {
    $recentFeedbacks = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Management - Admin Panel</title>
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="../assets/style.css">
    
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
                <h2 style="margin: 0; color: #1a1a2e;">💬 Feedback Management</h2>
                <span style="background: #dc3545; color: white; padding: 5px 15px; border-radius: 20px; font-size: 14px;">
                    <?php echo $unreadCount; ?> Unread
                </span>
            </div>
            
            <!-- Display Message -->
            <?php if ($message): ?>
            <div class="toast-message <?php echo $messageType; ?>">
                <span><?php echo $messageType == 'success' ? '✅' : '❌'; ?></span>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="feedback-stats-grid">
                <div class="feedback-stat-card blue">
                    <div class="stat-number"><?php echo $totalAll; ?></div>
                    <div class="stat-label">Total Feedback</div>
                </div>
                <div class="feedback-stat-card orange">
                    <div class="stat-number"><?php echo $unreadCount; ?></div>
                    <div class="stat-label">Unread</div>
                </div>
                <div class="feedback-stat-card green">
                    <div class="stat-number"><?php echo $readCount; ?></div>
                    <div class="stat-label">Read</div>
                </div>
                <div class="feedback-stat-card purple">
                    <div class="stat-number"><?php echo $repliedCount; ?></div>
                    <div class="stat-label">Replied</div>
                </div>
            </div>
            
            <!-- Feedback Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3>📋 All Feedback (<?php echo $totalFeedback; ?> found)</h3>
                    
                    <form method="GET" action="" class="table-controls">
                        <input type="text" name="search" placeholder="Search by user or message..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="unread" <?php echo $statusFilter == 'unread' ? 'selected' : ''; ?>>Unread</option>
                            <option value="read" <?php echo $statusFilter == 'read' ? 'selected' : ''; ?>>Read</option>
                            <option value="replied" <?php echo $statusFilter == 'replied' ? 'selected' : ''; ?>>Replied</option>
                        </select>
                        <button type="submit" class="btn-search">🔍 Search</button>
                        <?php if ($search || $statusFilter): ?>
                        <a href="feedback.php" class="btn-reset">↺ Reset</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($feedbacks->num_rows > 0): ?>
                                <?php while ($feedback = $feedbacks->fetch_assoc()): ?>
                                <tr class="<?php echo $feedback['status'] == 'unread' ? 'unread' : ''; ?>">
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar-small">
                                                <?php echo strtoupper(substr($feedback['fullname'], 0, 1)); ?>
                                            </div>
                                            <div class="user-details">
                                                <div class="name"><?php echo htmlspecialchars($feedback['fullname']); ?></div>
                                                <div class="email"><?php echo htmlspecialchars($feedback['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="feedback-message">
                                            <div class="content" id="msg_<?php echo $feedback['id']; ?>">
                                                <?php echo htmlspecialchars($feedback['message']); ?>
                                            </div>
                                            <?php if (strlen($feedback['message']) > 100): ?>
                                                <button class="toggle-btn" onclick="toggleMessage('<?php echo $feedback['id']; ?>')">
                                                    Show more
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($feedback['reply']): ?>
                                                <div style="margin-top: 5px; font-size: 12px; color: #03a60c;">
                                                    💬 Replied: <?php echo htmlspecialchars(substr($feedback['reply'], 0, 50)) . (strlen($feedback['reply']) > 50 ? '...' : ''); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-status <?php echo $feedback['status']; ?>">
                                            <?php echo ucfirst($feedback['status']); ?>
                                        </span>
                                        <?php if ($feedback['reply']): ?>
                                            <br><small style="color: #03a60c;">by <?php echo htmlspecialchars($feedback['replied_by_name'] ?? 'Admin'); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 12px; color: #999;">
                                        <?php echo date('M d, Y', strtotime($feedback['created_at'])); ?>
                                        <br>
                                        <small><?php echo date('h:i A', strtotime($feedback['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn reply" onclick="openReplyModal(<?php echo $feedback['id']; ?>)">
                                                💬 Reply
                                            </button>
                                            <?php if ($feedback['status'] == 'unread'): ?>
                                                <a href="feedback.php?mark=<?php echo $feedback['id']; ?>&status=read" 
                                                   class="action-btn mark-read">
                                                    📖 Mark Read
                                                </a>
                                            <?php endif; ?>
                                            <a href="feedback.php?delete=<?php echo $feedback['id']; ?>" 
                                               class="action-btn delete" 
                                               onclick="return confirm('Are you sure you want to delete this feedback?');">
                                                🗑️ Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="no-feedback">
                                            <span class="icon">💬</span>
                                            <p>No feedback found matching your criteria.</p>
                                            <p style="font-size: 13px; color: #ccc;">When users submit feedback, it will appear here.</p>
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

    <!-- Reply Modal -->
    <div class="modal-overlay" id="replyModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>💬 Reply to Feedback</h3>
                <button class="modal-close" onclick="closeReplyModal()">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="feedback_id" id="feedback_id" value="">
                    
                    <div class="feedback-detail" id="feedbackDetail">
                        <div class="user" id="feedbackUser">User Name</div>
                        <div class="message" id="feedbackMessage">Feedback message goes here...</div>
                        <div class="date" id="feedbackDate">Date</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="reply">Your Reply <span class="required">*</span></label>
                        <textarea id="reply" name="reply" placeholder="Type your reply here..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Update Status</label>
                        <select id="status" name="status">
                            <option value="read">Read</option>
                            <option value="replied" selected>Replied</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="reply_feedback" class="btn-primary">📤 Send Reply</button>
                    <button type="button" class="btn-secondary" onclick="closeReplyModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../javascript/script.js"></script>
</body>
</html>