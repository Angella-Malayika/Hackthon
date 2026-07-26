<?php
require_once("../middleware/user.php");
require_once("../core.php");

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Get current user details
$stmt = $conn->prepare("
    SELECT id, fullname, email, profile_picture, program, experience, xp, level
    FROM users
    WHERE id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Update basic profile info
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $program = trim($_POST['program'] ?? '');
        $experience = trim($_POST['experience'] ?? '');
        
        $stmt = $conn->prepare("
            UPDATE users 
            SET program = ?, experience = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssi", $program, $experience, $user_id);
        
        if ($stmt->execute()) {
            // Set success message in session to show on profile page
            $_SESSION['profile_update_message'] = "✅ Profile updated successfully!";
            // Redirect to profile page
            header("Location: profile.php");
            exit;
        } else {
            $error = "❌ Failed to update profile.";
        }
    }
    
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['size'] > 0) {
        $file = $_FILES['profile_picture'];
        
        // Validate file
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file_ext, $allowed_ext)) {
            $error = "❌ Invalid file type. Only JPG, PNG, and GIF allowed.";
        } elseif ($file['size'] > $max_size) {
            $error = "❌ File size exceeds 5MB limit.";
        } else {
            // Create uploads directory if it doesn't exist
            $upload_dir = "../uploads/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $new_filename = "profile_" . $user_id . "_" . time() . "." . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            // Delete old profile picture if it exists and is not default
            if ($user['profile_picture'] != 'default.png' && file_exists($upload_dir . $user['profile_picture'])) {
                unlink($upload_dir . $user['profile_picture']);
            }
            
            // Upload new file
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Update database
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET profile_picture = ?
                    WHERE id = ?
                ");
                $stmt->bind_param("si", $new_filename, $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['profile_update_message'] = "✅ Profile picture uploaded successfully!";
                    header("Location: profile.php");
                    exit;
                } else {
                    $error = "❌ Failed to update profile picture in database.";
                    unlink($upload_path);
                }
            } else {
                $error = "❌ Failed to upload file.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/profile.css">
    <link rel="stylesheet" href="../assets/edit profile.css">
</head>
<body>

<?php require_once("../includes/sidebar.php"); ?>

<div class="main">
    <?php require_once("../includes/navbar.php"); ?>

    <div class="content">
        <!-- Page Header -->
        <div class="dashboard-hero">
            <h1>✏️ Edit Profile</h1>
            <p>Update your profile information and picture.</p>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
        <div class="message-box success">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="message-box error">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <!-- Edit Form -->
        <div class="edit-profile-page">
            
            <!-- Profile Picture Section -->
            <div class="edit-profile-card profile-picture-card">
                <h3 class="profile-section-title">📸 Profile Picture</h3>
                
                <div class="profile-picture-preview">
                    <?php if ($user['profile_picture'] != 'default.png'): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($user['profile_picture']); ?>" class="profile-picture-img">
                    <?php else: ?>
                        <div class="profile-avatar-stub">
                            <?php echo strtoupper(substr($user['fullname'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <div class="input-group">
                        <label>Upload New Picture</label>
                        <input type="file" name="profile_picture" accept="image/*" class="file-input">
                        <small class="input-note">JPG, PNG, or GIF • Max 5MB</small>
                    </div>
                    <button type="submit" class="action-btn primary">📤 Upload Picture</button>
                </form>
            </div>

            <!-- Profile Details Section -->
            <div class="edit-profile-card profile-details-card">
                <h3 class="profile-section-title">📝 Profile Details</h3>

                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="input-group">
                        <label>Full Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['fullname']); ?>" class="disabled-input" disabled>
                        <small class="input-note">ⓘ Name cannot be changed</small>
                    </div>

                    <div class="input-group">
                        <label>Email</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="disabled-input" disabled>
                        <small class="input-note">ⓘ Email cannot be changed</small>
                    </div>

                    <div class="input-group">
                        <label>Program</label>
                        <input type="text" name="program" placeholder="e.g., Internet Governance, Web Development" value="<?php echo htmlspecialchars($user['program'] ?? ''); ?>">
                        <small class="input-note">Enter your current program or course</small>
                    </div>

                    <div class="input-group">
                        <label>My Experience</label>
                        <textarea name="experience" placeholder="Tell us about your experience..." rows="5"><?php echo htmlspecialchars($user['experience'] ?? ''); ?></textarea>
                        <small class="input-note">Share your learning journey and achievements</small>
                    </div>

                    <div class="stats-note">
                        <p>
                            <strong>Stats:</strong> Level <?php echo $user['level']; ?> • <?php echo $user['xp']; ?> XP
                        </p>
                    </div>

                    <button type="submit" class="action-btn primary">💾 Save Changes</button>
                </form>
            </div>
        </div>

    </div>
</div>

<script src="../javascript/script.js"></script>
</body>
</html>