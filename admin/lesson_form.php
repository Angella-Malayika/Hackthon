<?php
require_once("../middleware/admin.php");
require_once("../core.php");

// Get admin info
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['fullname'];

// Get lesson ID for editing
$lesson_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$isEdit = $lesson_id > 0;
$isView = isset($_GET['action']) && $_GET['action'] == 'view';
$pageTitle = $isView ? 'View Lesson' : ($isEdit ? 'Edit Lesson' : 'Add New Lesson');

// Initialize variables
$title = '';
$description = '';
$content = '';
$category_id = 0;
$video_url = '';
$pdf_file = '';
$thumbnail = '';
$estimated_time = '';
$status = 'draft';

$message = '';
$messageType = '';

// Get all categories for dropdown
$categoriesResult = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $categoriesResult->fetch_all(MYSQLI_ASSOC);

// If editing or viewing, fetch lesson data
if ($isEdit || $isView) {
    $stmt = $conn->prepare("SELECT * FROM lessons WHERE id = ?");
    $stmt->bind_param("i", $lesson_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $lesson = $result->fetch_assoc();
    
    if (!$lesson) {
        header("Location: lessons.php?error=Lesson not found");
        exit();
    }
    
    $title = $lesson['title'];
    $description = $lesson['description'];
    $content = $lesson['content'];
    $category_id = $lesson['category_id'];
    $video_url = $lesson['video_url'];
    $pdf_file = $lesson['pdf_file'];
    $thumbnail = $lesson['thumbnail'];
    $estimated_time = $lesson['estimated_time'];
    $status = $lesson['status'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_lesson'])) {
    // If viewing, redirect
    if ($isView) {
        header("Location: lessons.php");
        exit();
    }
    
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $content = trim($_POST['content']);
    $category_id = intval($_POST['category_id']);
    $video_url = trim($_POST['video_url']);
    $estimated_time = intval($_POST['estimated_time']);
    $status = $_POST['status'];
    
    // Handle file uploads (simplified - you'll need to implement actual file upload)
    $thumbnail = isset($_POST['thumbnail']) ? trim($_POST['thumbnail']) : '';
    $pdf_file = isset($_POST['pdf_file']) ? trim($_POST['pdf_file']) : '';
    
    // Validation
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Lesson title is required";
    }
    
    if (empty($content)) {
        $errors[] = "Lesson content is required";
    }
    
    if ($category_id <= 0) {
        $errors[] = "Please select a category";
    }
    
    // If no errors, save lesson
    if (empty($errors)) {
        if ($isEdit) {
            // Update existing lesson
            $stmt = $conn->prepare("UPDATE lessons SET 
                title = ?, 
                description = ?, 
                content = ?, 
                category_id = ?, 
                video_url = ?, 
                pdf_file = ?, 
                thumbnail = ?, 
                estimated_time = ?, 
                status = ? 
                WHERE id = ?");
            $stmt->bind_param("sssissssii", 
                $title, $description, $content, $category_id, 
                $video_url, $pdf_file, $thumbnail, $estimated_time, 
                $status, $lesson_id
            );
            
            if ($stmt->execute()) {
                $message = "Lesson updated successfully!";
                $messageType = "success";
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'lessons.php';
                    }, 2000);
                </script>";
            } else {
                $message = "Error updating lesson: " . $conn->error;
                $messageType = "error";
            }
        } else {
            // Insert new lesson
            $stmt = $conn->prepare("INSERT INTO lessons 
                (title, description, content, category_id, video_url, pdf_file, thumbnail, estimated_time, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssissssi", 
                $title, $description, $content, $category_id, 
                $video_url, $pdf_file, $thumbnail, $estimated_time, 
                $status
            );
            
            if ($stmt->execute()) {
                $message = "Lesson created successfully!";
                $messageType = "success";
                
                // Clear form fields
                $title = '';
                $description = '';
                $content = '';
                $category_id = 0;
                $video_url = '';
                $pdf_file = '';
                $thumbnail = '';
                $estimated_time = '';
                $status = 'draft';
                
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'lessons.php';
                    }, 2000);
                </script>";
            } else {
                $message = "Error creating lesson: " . $conn->error;
                $messageType = "error";
            }
        }
    } else {
        $message = implode("<br>", $errors);
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin Panel</title>
    
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
                <h2 style="margin: 0; color: #1a1a2e;"><?php echo $pageTitle; ?></h2>
                <a href="lessons.php" class="btn-secondary" style="padding: 8px 20px; font-size: 14px;">
                    ← Back to Lessons
                </a>
            </div>
            
            <!-- Display Message -->
            <?php if ($message): ?>
            <div class="toast-message <?php echo $messageType; ?>">
                <span><?php echo $messageType == 'success' ? '✅' : '❌'; ?></span>
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <!-- Form -->
            <div class="form-container">
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="title">Lesson Title <span class="required">*</span></label>
                            <?php if ($isView): ?>
                                <div class="view-mode">
                                    <div class="value"><?php echo htmlspecialchars($title); ?></div>
                                </div>
                            <?php else: ?>
                                <input type="text" id="title" name="title" 
                                       value="<?php echo htmlspecialchars($title); ?>" 
                                       placeholder="Enter lesson title" required>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="category_id">Category <span class="required">*</span></label>
                            <?php if ($isView): ?>
                                <div class="view-mode">
                                    <div class="value">
                                        <?php 
                                        $catName = '';
                                        foreach ($categories as $cat) {
                                            if ($cat['id'] == $category_id) {
                                                $catName = $cat['name'];
                                                break;
                                            }
                                        }
                                        echo htmlspecialchars($catName);
                                        ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <select id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                            <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Short Description</label>
                        <?php if ($isView): ?>
                            <div class="view-mode">
                                <div class="value"><?php echo nl2br(htmlspecialchars($description)); ?></div>
                            </div>
                        <?php else: ?>
                            <textarea id="description" name="description" 
                                      placeholder="Enter a brief description (optional)"><?php echo htmlspecialchars($description); ?></textarea>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Lesson Content <span class="required">*</span></label>
                        <?php if ($isView): ?>
                            <div class="view-mode" style="min-height: 100px;">
                                <div class="value"><?php echo nl2br(htmlspecialchars($content)); ?></div>
                            </div>
                        <?php else: ?>
                            <textarea id="content" name="content" 
                                      placeholder="Enter the full lesson content..." 
                                      required><?php echo htmlspecialchars($content); ?></textarea>
                            <div class="help-text">You can use HTML tags for formatting.</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="video_url">Video URL</label>
                            <?php if ($isView): ?>
                                <div class="view-mode">
                                    <div class="value">
                                        <?php if ($video_url): ?>
                                            <a href="<?php echo htmlspecialchars($video_url); ?>" target="_blank">
                                                <?php echo htmlspecialchars($video_url); ?>
                                            </a>
                                        <?php else: ?>
                                            No video provided
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <input type="url" id="video_url" name="video_url" 
                                       value="<?php echo htmlspecialchars($video_url); ?>" 
                                       placeholder="https://www.youtube.com/watch?v=...">
                                <div class="help-text">Enter a YouTube or Vimeo URL</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="estimated_time">Estimated Time (minutes)</label>
                            <?php if ($isView): ?>
                                <div class="view-mode">
                                    <div class="value"><?php echo $estimated_time ? $estimated_time . ' minutes' : 'Not specified'; ?></div>
                                </div>
                            <?php else: ?>
                                <input type="number" id="estimated_time" name="estimated_time" 
                                       value="<?php echo $estimated_time; ?>" 
                                       placeholder="e.g., 15" min="1">
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!$isView): ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="draft" <?php echo $status == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo $status == 'published' ? 'selected' : ''; ?>>Published</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="thumbnail">Thumbnail URL</label>
                            <input type="text" id="thumbnail" name="thumbnail" 
                                   value="<?php echo htmlspecialchars($thumbnail); ?>" 
                                   placeholder="https://example.com/thumbnail.jpg">
                            <div class="help-text">Image URL for the lesson thumbnail</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="pdf_file">PDF File URL</label>
                        <input type="text" id="pdf_file" name="pdf_file" 
                               value="<?php echo htmlspecialchars($pdf_file); ?>" 
                               placeholder="https://example.com/lesson.pdf">
                        <div class="help-text">URL to the PDF resource file</div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!$isView): ?>
                    <div class="form-actions">
                        <button type="submit" name="save_lesson" class="btn-primary">
                            <?php echo $isEdit ? '💾 Update Lesson' : '➕ Create Lesson'; ?>
                        </button>
                        <a href="lessons.php" class="btn-secondary">Cancel</a>
                    </div>
                    <?php else: ?>
                    <div class="form-actions">
                        <a href="lessons.php" class="btn-secondary">← Back to Lessons</a>
                        <a href="lesson_form.php?id=<?php echo $lesson_id; ?>" class="btn-primary">✏️ Edit Lesson</a>
                    </div>
                    <?php endif; ?>
                </form>
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