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
$categoryFilter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Delete lesson
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    
    // First, delete lesson progress records
    $stmt = $conn->prepare("DELETE FROM lesson_progress WHERE lesson_id = ?");
    $stmt->bind_param("i", $deleteId);
    $stmt->execute();
    
    // Then delete the lesson
    $stmt = $conn->prepare("DELETE FROM lessons WHERE id = ?");
    $stmt->bind_param("i", $deleteId);
    if ($stmt->execute()) {
        $message = "Lesson deleted successfully!";
        $messageType = "success";
    } else {
        $message = "Error deleting lesson: " . $conn->error;
        $messageType = "error";
    }
}

// Toggle lesson status (publish/unpublish)
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $lessonId = intval($_GET['toggle']);
    
    // Get current status
    $stmt = $conn->prepare("SELECT status FROM lessons WHERE id = ?");
    $stmt->bind_param("i", $lessonId);
    $stmt->execute();
    $result = $stmt->get_result();
    $lesson = $result->fetch_assoc();
    
    if ($lesson) {
        $newStatus = ($lesson['status'] == 'published') ? 'draft' : 'published';
        $stmt = $conn->prepare("UPDATE lessons SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $lessonId);
        if ($stmt->execute()) {
            $message = "Lesson " . ($newStatus == 'published' ? 'published' : 'unpublished') . " successfully!";
            $messageType = "success";
        } else {
            $message = "Error updating lesson status: " . $conn->error;
            $messageType = "error";
        }
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

if ($categoryFilter > 0) {
    $whereConditions[] = "category_id = ?";
    $params[] = $categoryFilter;
    $types .= "i";
}

if (!empty($statusFilter)) {
    $whereConditions[] = "status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get total lessons
$countQuery = "SELECT COUNT(*) AS total FROM lessons $whereClause";
$stmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totalLessons = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$totalPages = ceil($totalLessons / $limit);

// Get lessons with pagination
$query = "SELECT l.*, c.name as category_name 
          FROM lessons l
          LEFT JOIN categories c ON l.category_id = c.id
          $whereClause 
          ORDER BY l.created_at DESC 
          LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$params[] = $limit;
$params[] = $offset;
$types .= "ii";
$stmt->bind_param($types, ...$params);
$stmt->execute();
$lessons = $stmt->get_result();

// Get all categories for filter and dropdown
$categoriesResult = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $categoriesResult->fetch_all(MYSQLI_ASSOC);

// Get statistics
$result = $conn->query("SELECT COUNT(*) AS total FROM lessons");
$totalAllLessons = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM lessons WHERE status = 'published'");
$publishedLessons = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM lessons WHERE status = 'draft'");
$draftLessons = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT COUNT(*) AS total FROM categories");
$totalCategories = $result->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lesson Management - Admin Panel</title>
    
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
                <h2 style="margin: 0; color: #1a1a2e;">📚 Lesson Management</h2>
                <a href="lesson_form.php" class="action-btn edit" style="padding: 10px 20px; font-size: 14px;">
                    ➕ Add New Lesson
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
            <div class="lesson-stats-grid">
                <div class="lesson-stat-card green">
                    <div class="stat-number"><?php echo $totalAllLessons; ?></div>
                    <div class="stat-label">Total Lessons</div>
                </div>
                <div class="lesson-stat-card blue">
                    <div class="stat-number"><?php echo $publishedLessons; ?></div>
                    <div class="stat-label">Published</div>
                </div>
                <div class="lesson-stat-card orange">
                    <div class="stat-number"><?php echo $draftLessons; ?></div>
                    <div class="stat-label">Drafts</div>
                </div>
                <div class="lesson-stat-card purple">
                    <div class="stat-number"><?php echo $totalCategories; ?></div>
                    <div class="stat-label">Categories</div>
                </div>
            </div>
            
            <!-- Lessons Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3>📋 All Lessons (<?php echo $totalLessons; ?> found)</h3>
                    
                    <form method="GET" action="" class="table-controls">
                        <input type="text" name="search" placeholder="Search by title or description..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $categoryFilter == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="published" <?php echo $statusFilter == 'published' ? 'selected' : ''; ?>>Published</option>
                            <option value="draft" <?php echo $statusFilter == 'draft' ? 'selected' : ''; ?>>Draft</option>
                        </select>
                        <button type="submit" class="btn-search">🔍 Search</button>
                        <?php if ($search || $categoryFilter || $statusFilter): ?>
                        <a href="lessons.php" class="btn-reset">↺ Reset</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Est. Time</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($lessons->num_rows > 0): ?>
                                <?php while ($lesson = $lessons->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="lesson-title">
                                            <?php echo htmlspecialchars($lesson['title']); ?>
                                            <?php if ($lesson['thumbnail']): ?>
                                                <div class="lesson-meta">
                                                    🖼️ Has thumbnail
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($lesson['video_url'] || $lesson['pdf_file']): ?>
                                                <div class="lesson-meta">
                                                    <?php if ($lesson['video_url']): ?>📹 <?php endif; ?>
                                                    <?php if ($lesson['pdf_file']): ?>📄 <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-category">
                                            <?php echo htmlspecialchars($lesson['category_name'] ?? 'Uncategorized'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-status <?php echo $lesson['status']; ?>">
                                            <?php echo ucfirst($lesson['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $lesson['estimated_time'] ? $lesson['estimated_time'] . ' min' : 'N/A'; ?>
                                    </td>
                                    <td style="font-size: 12px; color: #999;">
                                        <?php echo date('M d, Y', strtotime($lesson['created_at'])); ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="lesson_form.php?action=view&id=<?php echo $lesson['id']; ?>" 
                                               class="action-btn view" title="View">
                                                👁️ View
                                            </a>
                                            <a href="lesson_form.php?id=<?php echo $lesson['id']; ?>" 
                                               class="action-btn edit" title="Edit">
                                                ✏️ Edit
                                            </a>
                                            <a href="lessons.php?toggle=<?php echo $lesson['id']; ?>" 
                                               class="action-btn toggle <?php echo $lesson['status'] == 'draft' ? 'draft' : ''; ?>" 
                                               title="<?php echo $lesson['status'] == 'published' ? 'Unpublish' : 'Publish'; ?>"
                                               onclick="return confirm('Are you sure you want to <?php echo $lesson['status'] == 'published' ? 'unpublish' : 'publish'; ?> this lesson?');">
                                                <?php echo $lesson['status'] == 'published' ? '📤 Unpublish' : '📥 Publish'; ?>
                                            </a>
                                            <a href="lessons.php?delete=<?php echo $lesson['id']; ?>" 
                                               class="action-btn delete" 
                                               title="Delete Lesson"
                                               onclick="return confirm('Are you sure you want to delete this lesson? This action cannot be undone!');">
                                                🗑️ Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="no-lessons">
                                            <span class="icon">📚</span>
                                            <p>No lessons found matching your criteria.</p>
                                            <p style="font-size: 13px; color: #ccc;">
                                                <a href="lesson_form.php" style="color: #03a60c; text-decoration: none; font-weight: 500;">
                                                    Click here to create your first lesson →
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
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $categoryFilter; ?>&status=<?php echo urlencode($statusFilter); ?>">◀ Previous</a>
                    <?php else: ?>
                        <span class="disabled">◀ Previous</span>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $categoryFilter; ?>&status=<?php echo urlencode($statusFilter); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $categoryFilter; ?>&status=<?php echo urlencode($statusFilter); ?>">Next ▶</a>
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