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

// Delete category
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    
    // Check if category has lessons
    $checkStmt = $conn->prepare("SELECT COUNT(*) AS count FROM lessons WHERE category_id = ?");
    $checkStmt->bind_param("i", $deleteId);
    $checkStmt->execute();
    $count = $checkStmt->get_result()->fetch_assoc()['count'];
    
    if ($count > 0) {
        $message = "Cannot delete category. It has $count lesson(s) associated with it.";
        $messageType = "error";
    } else {
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $deleteId);
        if ($stmt->execute()) {
            $message = "Category deleted successfully!";
            $messageType = "success";
        } else {
            $message = "Error deleting category: " . $conn->error;
            $messageType = "error";
        }
    }
}

// Handle Add/Edit Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_category'])) {
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    if (empty($name)) {
        $message = "Category name is required!";
        $messageType = "error";
    } else {
        if ($category_id > 0) {
            // Update
            $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $description, $category_id);
            if ($stmt->execute()) {
                $message = "Category updated successfully!";
                $messageType = "success";
            } else {
                $message = "Error updating category: " . $conn->error;
                $messageType = "error";
            }
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $description);
            if ($stmt->execute()) {
                $message = "Category created successfully!";
                $messageType = "success";
            } else {
                $message = "Error creating category: " . $conn->error;
                $messageType = "error";
            }
        }
    }
}

// Get all categories
$result = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $result->fetch_all(MYSQLI_ASSOC);

// Get category count
$totalCategories = count($categories);

// Get lesson count per category
$categoryStats = [];
foreach ($categories as $cat) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM lessons WHERE category_id = ?");
    $stmt->bind_param("i", $cat['id']);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['count'];
    $categoryStats[$cat['id']] = $count;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management - Admin Panel</title>
    
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
                <h2 style="margin: 0; color: #1a1a2e;">📂 Categories Management</h2>
                <span style="background: #03a60c; color: white; padding: 5px 15px; border-radius: 20px; font-size: 14px;">
                    Total: <?php echo $totalCategories; ?> Categories
                </span>
            </div>
            
            <!-- Display Message -->
            <?php if ($message): ?>
            <div class="toast-message <?php echo $messageType; ?>">
                <span><?php echo $messageType == 'success' ? '✅' : '❌'; ?></span>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>
            
            <!-- Categories Grid -->
            <div class="categories-grid">
                <!-- Add/Edit Category Form -->
                <div class="category-form-container">
                    <h3>
                        <?php 
                        if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
                            $editId = intval($_GET['edit']);
                            $editStmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
                            $editStmt->bind_param("i", $editId);
                            $editStmt->execute();
                            $editCategory = $editStmt->get_result()->fetch_assoc();
                            echo '✏️ Edit Category';
                        } else {
                            echo '➕ Add New Category';
                        }
                        ?>
                    </h3>
                    
                    <form method="POST" action="">
                        <?php if (isset($editCategory)): ?>
                            <input type="hidden" name="category_id" value="<?php echo $editCategory['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="name">Category Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name" 
                                   value="<?php echo isset($editCategory) ? htmlspecialchars($editCategory['name']) : ''; ?>" 
                                   placeholder="Enter category name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" 
                                      placeholder="Enter category description (optional)"><?php echo isset($editCategory) ? htmlspecialchars($editCategory['description']) : ''; ?></textarea>
                        </div>
                        
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" name="save_category" class="btn-primary">
                                <?php echo isset($editCategory) ? '💾 Update Category' : '➕ Create Category'; ?>
                            </button>
                            <?php if (isset($editCategory)): ?>
                                <a href="categories.php" class="btn-secondary">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <!-- Categories List -->
                <div class="category-list-container">
                    <div class="category-list-header">
                        <h3>📋 All Categories</h3>
                    </div>
                    
                    <div>
                        <?php if (count($categories) > 0): ?>
                            <?php foreach ($categories as $category): ?>
                            <div class="category-item">
                                <div class="category-info">
                                    <div class="name"><?php echo htmlspecialchars($category['name']); ?></div>
                                    <?php if ($category['description']): ?>
                                        <div class="description"><?php echo htmlspecialchars($category['description']); ?></div>
                                    <?php endif; ?>
                                    <div class="meta">
                                        <span>📚 <?php echo $categoryStats[$category['id']] ?? 0; ?> lessons</span>
                                        <span>📅 <?php echo date('M d, Y', strtotime($category['created_at'])); ?></span>
                                    </div>
                                </div>
                                <div class="category-actions">
                                    <a href="categories.php?edit=<?php echo $category['id']; ?>" class="btn-edit">
                                        ✏️ Edit
                                    </a>
                                    <a href="categories.php?delete=<?php echo $category['id']; ?>" 
                                       class="btn-delete"
                                       onclick="return confirm('Are you sure you want to delete this category? This will also delete all lessons in this category!');">
                                        🗑️ Delete
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-categories">
                                <span class="icon">📂</span>
                                <p>No categories created yet.</p>
                                <p style="font-size: 13px; color: #ccc;">Use the form on the left to add your first category.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
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