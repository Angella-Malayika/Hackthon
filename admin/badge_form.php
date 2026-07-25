<?php
require_once("../middleware/admin.php");
require_once("../core.php");

// Get admin info
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['fullname'];

// Get badge ID for editing
$badge_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$isEdit = $badge_id > 0;
$isView = isset($_GET['action']) && $_GET['action'] == 'view';
$pageTitle = $isView ? 'View Badge' : ($isEdit ? 'Edit Badge' : 'Add New Badge');

// Initialize variables
$name = '';
$description = '';
$image = '🏅';
$requirement = '';

$message = '';
$messageType = '';

// Common badge icons for dropdown
$badgeIcons = [
    '🏅' => 'Generic Medal',
    '🥇' => 'Gold Medal',
    '🥈' => 'Silver Medal',
    '🥉' => 'Bronze Medal',
    '⭐' => 'Star',
    '🌟' => 'Glowing Star',
    '👑' => 'Crown',
    '🛡️' => 'Shield',
    '🔰' => 'Japanese Symbol',
    '💎' => 'Diamond',
    '🏆' => 'Trophy',
    '🎖️' => 'Military Medal',
    '🏵️' => 'Rosette',
    '🎯' => 'Target',
    '🚀' => 'Rocket',
    '🔥' => 'Fire',
    '💪' => 'Muscle',
    '🧠' => 'Brain',
    '🦸' => 'Superhero',
    '🧙' => 'Wizard'
];

// If editing or viewing, fetch badge data
if ($isEdit || $isView) {
    $stmt = $conn->prepare("SELECT * FROM badges WHERE id = ?");
    $stmt->bind_param("i", $badge_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $badge = $result->fetch_assoc();
    
    if (!$badge) {
        header("Location: badges.php?error=Badge not found");
        exit();
    }
    
    $name = $badge['name'];
    $description = $badge['description'];
    $image = $badge['image'] ?: '🏅';
    $requirement = $badge['requirement'] ?? '';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_badge'])) {
    // If viewing, redirect
    if ($isView) {
        header("Location: badges.php");
        exit();
    }
    
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $image = trim($_POST['image']);
    $requirement = trim($_POST['requirement']);
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Badge name is required";
    }
    
    if (strlen($name) < 2) {
        $errors[] = "Badge name must be at least 2 characters";
    }
    
    if (empty($image)) {
        $errors[] = "Please select a badge icon";
    }
    
    // If no errors, save badge
    if (empty($errors)) {
        if ($isEdit) {
            // Update existing badge
            $stmt = $conn->prepare("UPDATE badges SET 
                name = ?, 
                description = ?, 
                image = ?,
                requirement = ?
                WHERE id = ?");
            $stmt->bind_param("ssssi", 
                $name, $description, $image, $requirement, $badge_id
            );
            
            if ($stmt->execute()) {
                $message = "Badge updated successfully!";
                $messageType = "success";
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'badges.php';
                    }, 2000);
                </script>";
            } else {
                $message = "Error updating badge: " . $conn->error;
                $messageType = "error";
            }
        } else {
            // Insert new badge
            $stmt = $conn->prepare("INSERT INTO badges 
                (name, description, image, requirement) 
                VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", 
                $name, $description, $image, $requirement
            );
            
            if ($stmt->execute()) {
                $message = "Badge created successfully!";
                $messageType = "success";
                
                // Clear form fields
                $name = '';
                $description = '';
                $image = '🏅';
                $requirement = '';
                
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'badges.php';
                    }, 2000);
                </script>";
            } else {
                $message = "Error creating badge: " . $conn->error;
                $messageType = "error";
            }
        }
    } else {
        $message = implode("<br>", $errors);
        $messageType = "error";
    }
}

// Get stats for this badge (if viewing/editing)
$stats = [
    'earned' => 0,
    'users' => 0
];

if ($isEdit || $isView) {
    $stmt = $conn->prepare("SELECT 
        COUNT(*) as earned,
        COUNT(DISTINCT user_id) as users
        FROM user_badges WHERE badge_id = ?");
    $stmt->bind_param("i", $badge_id);
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
                <a href="badges.php" class="btn-secondary" style="padding: 8px 20px; font-size: 14px;">
                    ← Back to Badges
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
                        <div class="number green"><?php echo $stats['earned'] ?? 0; ?></div>
                        <div class="label">🏅 Total Earned</div>
                    </div>
                    <div class="stat-item">
                        <div class="number blue"><?php echo $stats['users'] ?? 0; ?></div>
                        <div class="label">👥 Unique Users</div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Live Preview -->
                <div class="badge-preview">
                    <span class="preview-icon" id="previewIcon"><?php echo $image ?: '🏅'; ?></span>
                    <div class="preview-name" id="previewName"><?php echo htmlspecialchars($name ?: 'Badge Name'); ?></div>
                    <div class="preview-desc" id="previewDesc"><?php echo htmlspecialchars($description ?: 'Badge Description'); ?></div>
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">Badge Name <span class="required">*</span></label>
                        <?php if ($isView): ?>
                            <div class="view-mode">
                                <div class="value"><?php echo htmlspecialchars($name); ?></div>
                            </div>
                        <?php else: ?>
                            <input type="text" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($name); ?>" 
                                   placeholder="e.g., Cyber Hero" 
                                   oninput="document.getElementById('previewName').textContent = this.value || 'Badge Name';"
                                   required>
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
                                      placeholder="Describe what this badge represents"
                                      oninput="document.getElementById('previewDesc').textContent = this.value || 'Badge Description';"><?php echo htmlspecialchars($description); ?></textarea>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="requirement">Requirement <span class="required">*</span></label>
                        <?php if ($isView): ?>
                            <div class="view-mode">
                                <div class="value"><?php echo htmlspecialchars($requirement ?: 'No specific requirement'); ?></div>
                            </div>
                        <?php else: ?>
                            <input type="text" id="requirement" name="requirement" 
                                   value="<?php echo htmlspecialchars($requirement); ?>" 
                                   placeholder="e.g., Complete 5 lessons or Earn 500 XP">
                            <div class="help-text">What does a user need to do to earn this badge?</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Badge Icon <span class="required">*</span></label>
                        <?php if ($isView): ?>
                            <div class="view-mode">
                                <div class="value" style="font-size: 32px;"><?php echo htmlspecialchars($image); ?></div>
                            </div>
                        <?php else: ?>
                            <select id="image" name="image" 
                                    onchange="document.getElementById('previewIcon').textContent = this.value;">
                                <?php foreach ($badgeIcons as $icon => $label): ?>
                                    <option value="<?php echo $icon; ?>" 
                                        <?php echo $image == $icon ? 'selected' : ''; ?>>
                                        <?php echo $icon; ?> - <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="help-text">Choose an icon that represents this badge</div>
                            
                            <!-- Quick Icon Picker -->
                            <div class="icon-grid">
                                <?php foreach (array_keys($badgeIcons) as $icon): ?>
                                    <div class="icon-option <?php echo $image == $icon ? 'selected' : ''; ?>" 
                                         onclick="selectIcon('<?php echo $icon; ?>')">
                                        <?php echo $icon; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!$isView): ?>
                    <div class="form-actions">
                        <button type="submit" name="save_badge" class="btn-primary">
                            <?php echo $isEdit ? '💾 Update Badge' : '➕ Create Badge'; ?>
                        </button>
                        <a href="badges.php" class="btn-secondary">Cancel</a>
                    </div>
                    <?php else: ?>
                    <div class="form-actions">
                        <a href="badges.php" class="btn-secondary">← Back to Badges</a>
                        <a href="badge_form.php?id=<?php echo $badge_id; ?>" class="btn-primary">✏️ Edit Badge</a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../javascript/script.js"></script>
   
</body>
</html>