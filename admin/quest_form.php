<?php
require_once("../middleware/admin.php");
require_once("../core.php");

// Get admin info
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['fullname'];

// Get quest ID for editing
$quest_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$isEdit = $quest_id > 0;
$isView = isset($_GET['action']) && $_GET['action'] == 'view';
$pageTitle = $isView ? 'View Quest' : ($isEdit ? 'Edit Quest' : 'Add New Quest');

// Initialize variables
$title = '';
$description = '';
$xp_reward = 50;
$status = 'active';

$message = '';
$messageType = '';

// If editing or viewing, fetch quest data
if ($isEdit || $isView) {
    $stmt = $conn->prepare("SELECT * FROM quests WHERE id = ?");
    $stmt->bind_param("i", $quest_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $quest = $result->fetch_assoc();
    
    if (!$quest) {
        header("Location: quests.php?error=Quest not found");
        exit();
    }
    
    $title = $quest['title'];
    $description = $quest['description'];
    $xp_reward = $quest['xp_reward'];
    $status = $quest['status'] ?? 'active';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_quest'])) {
    // If viewing, redirect
    if ($isView) {
        header("Location: quests.php");
        exit();
    }
    
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $xp_reward = intval($_POST['xp_reward']);
    $status = $_POST['status'] ?? 'active';
    
    // Validation
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Quest title is required";
    }
    
    if (strlen($title) < 3) {
        $errors[] = "Quest title must be at least 3 characters";
    }
    
    if ($xp_reward <= 0) {
        $errors[] = "XP reward must be greater than 0";
    }
    
    // If no errors, save quest
    if (empty($errors)) {
        if ($isEdit) {
            // Update existing quest
            $stmt = $conn->prepare("UPDATE quests SET 
                title = ?, 
                description = ?, 
                xp_reward = ?,
                status = ?
                WHERE id = ?");
            $stmt->bind_param("ssisi", 
                $title, $description, $xp_reward, $status, $quest_id
            );
            
            if ($stmt->execute()) {
                $message = "Quest updated successfully!";
                $messageType = "success";
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'quests.php';
                    }, 2000);
                </script>";
            } else {
                $message = "Error updating quest: " . $conn->error;
                $messageType = "error";
            }
        } else {
            // Insert new quest
            $stmt = $conn->prepare("INSERT INTO quests 
                (title, description, xp_reward, status) 
                VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssis", 
                $title, $description, $xp_reward, $status
            );
            
            if ($stmt->execute()) {
                $message = "Quest created successfully!";
                $messageType = "success";
                
                // Clear form fields
                $title = '';
                $description = '';
                $xp_reward = 50;
                $status = 'active';
                
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'quests.php';
                    }, 2000);
                </script>";
            } else {
                $message = "Error creating quest: " . $conn->error;
                $messageType = "error";
            }
        }
    } else {
        $message = implode("<br>", $errors);
        $messageType = "error";
    }
}

// Get stats for this quest (if viewing/editing)
$stats = [
    'completed' => 0,
    'pending' => 0,
    'total' => 0
];

if ($isEdit || $isView) {
    $stmt = $conn->prepare("SELECT 
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
        COUNT(*) as total
        FROM user_quests WHERE quest_id = ?");
    $stmt->bind_param("i", $quest_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin Panel</title>
    
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
                <h2 style="margin: 0; color: #1a1a2e;"><?php echo $pageTitle; ?></h2>
                <a href="quests.php" class="btn-secondary" style="padding: 8px 20px; font-size: 14px;">
                    ← Back to Quests
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
                <?php if ($isView || $isEdit): ?>
                <!-- Stats Box -->
                <div class="stats-box" style="margin-bottom: 25px;">
                    <div class="stat-item">
                        <div class="number green"><?php echo $stats['completed'] ?? 0; ?></div>
                        <div class="label">✅ Completed</div>
                    </div>
                    <div class="stat-item">
                        <div class="number orange"><?php echo $stats['pending'] ?? 0; ?></div>
                        <div class="label">⏳ Pending</div>
                    </div>
                    <div class="stat-item">
                        <div class="number blue"><?php echo $stats['total'] ?? 0; ?></div>
                        <div class="label">📊 Total Attempts</div>
                    </div>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="title">Quest Title <span class="required">*</span></label>
                        <?php if ($isView): ?>
                            <div class="view-mode">
                                <div class="value"><?php echo htmlspecialchars($title); ?></div>
                            </div>
                        <?php else: ?>
                            <input type="text" id="title" name="title" 
                                   value="<?php echo htmlspecialchars($title); ?>" 
                                   placeholder="Enter quest title" required>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <?php if ($isView): ?>
                            <div class="view-mode">
                                <div class="value"><?php echo nl2br(htmlspecialchars($description)); ?></div>
                            </div>
                        <?php else: ?>
                            <textarea id="description" name="description" 
                                      placeholder="Describe what the user needs to do to complete this quest"><?php echo htmlspecialchars($description); ?></textarea>
                            <div class="help-text">Clear instructions help users understand what they need to do.</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="xp_reward">XP Reward <span class="required">*</span></label>
                            <?php if ($isView): ?>
                                <div class="view-mode">
                                    <div class="value">⭐ <?php echo $xp_reward; ?> XP</div>
                                </div>
                            <?php else: ?>
                                <input type="number" id="xp_reward" name="xp_reward" 
                                       value="<?php echo $xp_reward; ?>" 
                                       min="1" max="1000" required>
                                <div class="help-text">How much XP should the user earn? (1-1000)</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <?php if ($isView): ?>
                                <div class="view-mode">
                                    <div class="value">
                                        <span class="badge-status <?php echo $status; ?>">
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <select id="status" name="status">
                                    <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                                <div class="help-text">Only active quests will be shown to users.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!$isView): ?>
                    <div class="form-actions">
                        <button type="submit" name="save_quest" class="btn-primary">
                            <?php echo $isEdit ? '💾 Update Quest' : '➕ Create Quest'; ?>
                        </button>
                        <a href="quests.php" class="btn-secondary">Cancel</a>
                    </div>
                    <?php else: ?>
                    <div class="form-actions">
                        <a href="quests.php" class="btn-secondary">← Back to Quests</a>
                        <a href="quest_form.php?id=<?php echo $quest_id; ?>" class="btn-primary">✏️ Edit Quest</a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

</body>
</html>