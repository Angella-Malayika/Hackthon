<?php
require_once("../middleware/user.php");
require_once("../core.php");

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id, fullname, email, xp, level, created_at, last_login FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$message = '';
$messageType = '';

// --- Update profile details (fullname / email) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);

    if (empty($fullname) || empty($email)) {
        $message = "Full name and email cannot be empty.";
        $messageType = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";
    } else {
        // Make sure the email isn't already used by someone else
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->bind_param("si", $email, $user_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $message = "That email is already in use by another account.";
            $messageType = "error";
        } else {
            $upd = $conn->prepare("UPDATE users SET fullname = ?, email = ? WHERE id = ?");
            $upd->bind_param("ssi", $fullname, $email, $user_id);
            $upd->execute();

            $_SESSION['fullname'] = $fullname;
            $_SESSION['email'] = $email;
            $user['fullname'] = $fullname;
            $user['email'] = $email;

            $message = "Profile updated successfully!";
            $messageType = "success";
        }
    }
}

// --- Change password ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_new_password'] ?? '';

    $pwStmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $pwStmt->bind_param("i", $user_id);
    $pwStmt->execute();
    $row = $pwStmt->get_result()->fetch_assoc();

    if (!password_verify($currentPassword, $row['password'])) {
        $message = "Your current password is incorrect.";
        $messageType = "error";
    } elseif (strlen($newPassword) < 8 || strlen($newPassword) > 16 ||
              !preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword) ||
              !preg_match('/[0-9]/', $newPassword) || !preg_match('/[\W]/', $newPassword)) {
        $message = "New password must be 8-16 characters and include upper/lowercase letters, a number, and a symbol.";
        $messageType = "error";
    } elseif ($newPassword !== $confirmPassword) {
        $message = "New password and confirmation do not match.";
        $messageType = "error";
    } else {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $upd->bind_param("si", $hashed, $user_id);
        $upd->execute();

        $message = "Password changed successfully!";
        $messageType = "success";
    }
}

// --- Quick stats for the sidebar card ---
$badgeCount = $conn->query("SELECT COUNT(*) AS c FROM user_badges WHERE user_id = $user_id")->fetch_assoc()['c'] ?? 0;
$questCount = $conn->query("SELECT COUNT(*) AS c FROM user_quests WHERE user_id = $user_id AND status = 'completed'")->fetch_assoc()['c'] ?? 0;

$initial = strtoupper(substr($user['fullname'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php require_once("../includes/sidebar.php"); ?>

    <div class="page-container">
        <?php require_once("../includes/navbar.php"); ?>

        <div class="page-content">
            <div class="page-header">
                <h1>👤 My Profile</h1>
                <p>Manage your account details and password.</p>
            </div>

            <?php if ($message): ?>
            <div class="toast-message <?php echo $messageType; ?>">
                <span><?php echo $messageType == 'success' ? '✅' : '⚠️'; ?></span>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <div class="profile-grid">
                <div class="profile-card">
                    <div class="profile-avatar-lg"><?php echo $initial; ?></div>
                    <h2><?php echo htmlspecialchars($user['fullname']); ?></h2>
                    <span class="role-tag">Learner</span>
                    <p style="color:#888; font-size: 13px;"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p style="color:#aaa; font-size: 12px;">
                        Member since <?php echo date('M Y', strtotime($user['created_at'])); ?>
                    </p>
                    <div class="profile-mini-stats">
                        <div>
                            <div class="num">⭐ <?php echo $user['xp']; ?></div>
                            <div class="lbl">XP</div>
                        </div>
                        <div>
                            <div class="num">L<?php echo $user['level']; ?></div>
                            <div class="lbl">Level</div>
                        </div>
                        <div>
                            <div class="num">🏅 <?php echo $badgeCount; ?></div>
                            <div class="lbl">Badges</div>
                        </div>
                        <div>
                            <div class="num">🧭 <?php echo $questCount; ?></div>
                            <div class="lbl">Quests</div>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="profile-form-card">
                        <h3>Profile Details</h3>
                        <form method="POST" action="">
                            <div class="input-group">
                                <label>Full Name</label>
                                <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                            </div>
                            <div class="input-group">
                                <label>Email Address</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <button type="submit" name="update_profile" class="btn-save"> Save Changes</button>
                        </form>
                    </div>

                    <div class="profile-form-card">
                        <h3>Change Password</h3>
                        <form method="POST" action="">
                            <div class="input-group">
                                <label>Current Password</label>
                                <div class="password-field-wrap">
                                    <input type="password" id="currentPassword" name="current_password" required>
                                </div>
                            </div>
                            <div class="input-group">
                                <label>New Password</label>
                                <div class="password-field-wrap">
                                    <input type="password" id="newPassword" name="new_password" required>
                                </div>
                            </div>
                            <div class="input-group">
                                <label>Confirm New Password</label>
                                <div class="password-field-wrap">
                                    <input type="password" id="confirmNewPassword" name="confirm_new_password" required>
                                </div>
                                <div class="password-toggle-row">
                                    <input type="checkbox" id="showProfilePasswords" data-toggle-password data-target="currentPassword,newPassword,confirmNewPassword">
                                    <label for="showProfilePasswords" style="margin:0; font-weight:normal;">Show passwords briefly</label>
                                </div>
                            </div>
                            <p style="font-size: 12px; color: #999;">
                                Must be 8-16 characters with upper &amp; lowercase letters, a number, and a symbol.
                            </p>
                            <button type="submit" name="change_password" class="btn-save"> Update Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../javascript/script.js"></script>
</body>
</html>
