<?php
// profile.php
require_once("../middleware/user.php");
require_once("../core.php");
require_once("../includes/achievements.php");

// Logged in user
$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    header("Location: ../auth/login.php");
    exit;
}

// Check for a success message handed off from edit_profile.php
$update_message = '';
if (isset($_SESSION['profile_update_message'])) {
    $update_message = $_SESSION['profile_update_message'];
    unset($_SESSION['profile_update_message']);
}

// Fetch full user profile data
$stmt = $conn->prepare("SELECT id, fullname, email, xp, level, profile_picture, program, experience, date_joined, created_at, last_login FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("User not found.");
}

// Format date joined (fall back to created_at if date_joined is empty)
$joinedRaw = !empty($user['date_joined']) ? $user['date_joined'] : $user['created_at'];
$formattedDate = $joinedRaw ? (new DateTime($joinedRaw))->format('F j, Y') : 'Unknown';

// --- Awards / Badges count ---
$stmt = $conn->prepare("SELECT COUNT(*) AS total_badges FROM user_badges WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$badgesCount = $stmt->get_result()->fetch_assoc()['total_badges'] ?? 0;

// --- Points (XP) - combine quest, quest-level, and lesson XP sources for the display card ---
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(q.xp_reward), 0) AS total_xp
    FROM user_quests uq
    JOIN quests q ON uq.quest_id = q.id
    WHERE uq.user_id = ? AND uq.status = 'completed'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$xpFromQuests = $stmt->get_result()->fetch_assoc()['total_xp'] ?? 0;

$stmt = $conn->prepare("SELECT COUNT(*) * 10 AS xp_from_lessons FROM lesson_progress WHERE user_id = ? AND status = 'completed'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$xpFromLessons = $stmt->get_result()->fetch_assoc()['xp_from_lessons'] ?? 0;

// The user's actual XP total (kept authoritative on users.xp) is what we show as "Points";
// the breakdown above is informational only.
$totalXP = (int) $user['xp'];

// --- Program ---
$program = !empty($user['program']) ? $user['program'] : 'Week';

// --- Quest levels completed (the 8-level Quest challenge) ---
$questLevelCount = 0;
if (table_exists($conn, 'quest_progress')) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM quest_progress WHERE user_id = ? AND status = 'completed'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $questLevelCount = $stmt->get_result()->fetch_assoc()['c'] ?? 0;
}

// --- Bonus quests completed ---
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM user_quests WHERE user_id = ? AND status = 'completed'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bonusQuestCount = $stmt->get_result()->fetch_assoc()['c'] ?? 0;
$questCount = $questLevelCount + $bonusQuestCount;

// --- Achievements unlocked ---
$achievementCount = 0;
if (table_exists($conn, 'user_achievements')) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM user_achievements WHERE user_id = ? AND status = 'completed'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $achievementCount = $stmt->get_result()->fetch_assoc()['c'] ?? 0;
}

// --- Friends preview (accepted friends only, excluding self) ---
$friends = [];
if (table_exists($conn, 'friends')) {
    $stmt = $conn->prepare("
        SELECT u.id, u.fullname, u.profile_picture
        FROM friends f
        JOIN users u ON u.id = IF(f.user_id = ?, f.friend_id, f.user_id)
        WHERE (f.user_id = ? OR f.friend_id = ?) AND f.status = 'accepted'
        LIMIT 6
    ");
    $stmt->bind_param("iii", $user_id, $user_id, $user_id);
    $stmt->execute();
    $friendsResult = $stmt->get_result();
    while ($row = $friendsResult->fetch_assoc()) {
        $friends[] = $row;
    }
}

// --- XP level badge (based on total XP) ---
function getLevel($xp) {
    if ($xp < 100) return ['level' => 'Beginner', 'emoji' => '🌱'];
    if ($xp < 300) return ['level' => 'Intermediate', 'emoji' => '📚'];
    if ($xp < 600) return ['level' => 'Advanced', 'emoji' => '🚀'];
    if ($xp < 1000) return ['level' => 'Expert', 'emoji' => '🏆'];
    return ['level' => 'Master', 'emoji' => '👑'];
}
$levelInfo = getLevel($totalXP);

// Share link - invite friends to register
$shareLink = "https://" . $_SERVER['HTTP_HOST'] . "/internet/auth/register.php?ref=" . $user_id;

$message = '';
$messageType = '';

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

$initials = '';
$nameParts = explode(' ', trim($user['fullname']));
foreach ($nameParts as $part) {
    if (!empty($part)) $initials .= strtoupper($part[0]);
}
$initials = substr($initials, 0, 2);
if ($initials === '') $initials = '?';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Profile - Internet Governance Platform</title>
    <link rel="stylesheet" href="../assets/style.css" />
    <link rel="stylesheet" href="../assets/profile.css" />
</head>
<body>
    <?php require_once("../includes/sidebar.php"); ?>
    <div class="main">
        <?php require_once("../includes/navbar.php"); ?>
        <div class="content">
            <div class="profile-content">
                <!-- Welcome Banner -->
                <div class="profile-welcome">
                    <h1> Welcome, <span><?php echo htmlspecialchars($user['fullname']); ?></span></h1>
                    <p>Manage your profile, track your achievements, and connect with friends.</p>
                </div>

                <?php if ($update_message): ?>
                <div class="toast-message success">
                    <span>✅</span>
                    <?php echo htmlspecialchars($update_message); ?>
                </div>
                <?php endif; ?>

                <?php if ($message): ?>
                <div class="toast-message <?php echo $messageType; ?>">
                    <span><?php echo $messageType == 'success' ? '✅' : '⚠️'; ?></span>
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <!-- Profile Card -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <div class="profile-avatar">
                            <?php if (!empty($user['profile_picture']) && $user['profile_picture'] !== 'default.png'): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="">
                            <?php else: ?>
                                <?php echo htmlspecialchars($initials); ?>
                            <?php endif; ?>
                        </div>
                        <div class="profile-info">
                            <h2><?php echo htmlspecialchars($user['fullname']); ?></h2>
                            <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                            <span class="profile-date"> Joined <?php echo $formattedDate; ?></span>
                            <span class="profile-badge-level">
                                <?php echo $levelInfo['emoji']; ?> <?php echo $levelInfo['level']; ?>
                            </span>
                        </div>
                        <a href="edit_profile.php"> Edit Profile</a>
                    </div>

                    <!-- Stats -->
                    <div class="stats-grid-profile">
                        <div class="stat-card-profile">
                            <div class="stat-number"><?php echo $badgesCount; ?></div>
                            <div class="stat-label">Awards</div>
                        </div>
                        <div class="stat-card-profile">
                            <div class="stat-number"><?php echo number_format($totalXP); ?></div>
                            <div class="stat-label">Points (XP)</div>
                        </div>
                        <div class="stat-card-profile">
                            <div class="stat-number"><?php echo $questCount; ?></div>
                            <div class="stat-label">Quests Done</div>
                        </div>
                        <div class="stat-card-profile">
                            <div class="stat-number" style="font-size:18px;"><?php echo htmlspecialchars($levelInfo['level']); ?></div>
                            <div class="stat-label">Rank</div>
                        </div>
                        <div class="stat-card-profile">
                            <div class="stat-number"><?php echo $achievementCount; ?>/100</div>
                            <div class="stat-label">Achievements</div>
                        </div>
                        <div class="stat-card-profile">
                            <div class="stat-number" style="font-size:18px;"><?php echo htmlspecialchars($program); ?></div>
                            <div class="stat-label">Program</div>
                        </div>
                    </div>
                </div>

                <div class="profile-grid">
                    <!-- Experience -->
                    <div class="profile-card">
                        <h3 class="section-header"> Experience</h3>
                        <?php if (!empty($user['experience'])): ?>
                            <div class="experience-box">
                                <p><?php echo nl2br(htmlspecialchars($user['experience'])); ?></p>
                            </div>
                        <?php else: ?>
                            <p class="no-content">You haven't shared your experience yet. <a href="edit_profile.php">Add it now →</a></p>
                        <?php endif; ?>
                    </div>

                    <!-- Friends -->
                    <div class="profile-card">
                        <h3 class="section-header"> Friends</h3>
                        <?php if (!empty($friends)): ?>
                            <div class="friends-grid">
                                <?php foreach ($friends as $f): ?>
                                    <div class="friend-card">
                                        <div class="friend-avatar-sm">
                                            <?php if (!empty($f['profile_picture']) && $f['profile_picture'] !== 'default.png'): ?>
                                                <img src="../uploads/<?php echo htmlspecialchars($f['profile_picture']); ?>" alt="">
                                            <?php else: ?>
                                                <?php echo htmlspecialchars(strtoupper(substr($f['fullname'], 0, 1))); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="friend-name"><?php echo htmlspecialchars($f['fullname']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="no-content">No friends yet. Start connecting!</p>
                        <?php endif; ?>
                        <a href="friends.php" style="text-decoration: none;">
                            <button class="add-friend-btn">+ Add Friends</button>
                        </a>
                    </div>

                    <!-- Share Link -->
                    <div class="profile-card">
                        <h3 class="section-header"> Share Link</h3>
                        <div class="share-link-box">
                            <input type="hidden" id="shareLinkInput" value="<?php echo htmlspecialchars($shareLink); ?>" />
                            <button class="copy-btn" onclick="copyShareLink()" style="width: 100%;">
                                 Copy Invitation Link
                            </button>
                            <p class="share-description">Share this link with friends and grow the community.</p>
                        </div>
                    </div>

                    <!-- Account Info -->
                    <div class="profile-card">
                        <h3 class="section-header"> Account Info</h3>
                        <div class="experience-box" style="text-align:left;">
                            <p style="margin:0 0 10px;"><strong>Full Name:</strong> <?php echo htmlspecialchars($user['fullname']); ?></p>
                            <p style="margin:0;"><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                            <small>Your name and email stay fixed once set to keep your certificates and records consistent.</small>
                        </div>
                        <p class="no-content" style="text-align:left; margin-top:12px;">
                            Update your program, experience, and photo from <a href="edit_profile.php">Edit Profile →</a>.
                        </p>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="profile-card" >
                    <h3 class="section-header"> Change Password</h3>
                    <form method="POST" action="" style="align-items: center; max-width: 450px; margin-left: 200px;">
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

    <script>
        function showToast(message, isSuccess = true) {
            const existingToast = document.querySelector('.toast-notification');
            if (existingToast) existingToast.remove();

            const toast = document.createElement('div');
            toast.className = 'toast-notification ' + (isSuccess ? 'toast-success' : 'toast-error');
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.animation = 'slideDown 0.3s ease';
                setTimeout(() => { if (toast.parentNode) toast.remove(); }, 300);
            }, 3000);
        }

        function copyShareLink() {
            const input = document.getElementById('shareLinkInput');
            input.type = 'text';
            input.select();
            input.setSelectionRange(0, 99999);

            try {
                document.execCommand('copy');
                showToast('✅ Invitation link copied to clipboard!', true);
            } catch (err) {
                showToast('❌ Could not copy. Please copy manually.', false);
            }

            input.type = 'hidden';
        }

        // Briefly reveal password fields when the "show passwords" checkbox is toggled
        document.querySelectorAll('[data-toggle-password]').forEach(function (cb) {
            cb.addEventListener('change', function () {
                const targets = (cb.getAttribute('data-target') || '').split(',');
                targets.forEach(function (id) {
                    const el = document.getElementById(id.trim());
                    if (el) el.type = cb.checked ? 'text' : 'password';
                });
            });
        });
    </script>
    <script src="../javascript/script.js"></script>
</body>
</html>
