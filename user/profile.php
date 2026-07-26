<?php
// profile.php
require_once("../middleware/user.php");
require_once("../core.php");

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Logged in user
$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Check for success message from edit profile
$update_message = '';
if (isset($_SESSION['profile_update_message'])) {
    $update_message = $_SESSION['profile_update_message'];
    unset($_SESSION['profile_update_message']);
}

// Fetch user profile data - include ALL fields
$stmt = $conn->prepare("SELECT fullname, email, date_joined, program, experience FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("User not found.");
}

// Format date joined
$dateJoined = new DateTime($user['date_joined']);
$formattedDate = $dateJoined->format('F j, Y');

// --- Awards / Badges count ---
$stmt = $conn->prepare("SELECT COUNT(*) AS total_badges FROM user_badges WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$badgesCount = $stmt->get_result()->fetch_assoc()['total_badges'] ?? 0;

// --- Points (XP) ---
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(q.xp_reward), 0) AS total_xp 
    FROM user_quests uq
    JOIN quests q ON uq.quest_id = q.id
    WHERE uq.user_id = ? AND uq.status = 'completed'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$xpFromQuests = $stmt->get_result()->fetch_assoc()['total_xp'] ?? 0;

$stmt = $conn->prepare("
    SELECT COUNT(*) * 10 AS xp_from_lessons
    FROM lesson_progress
    WHERE user_id = ? AND status = 'completed'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$xpFromLessons = $stmt->get_result()->fetch_assoc()['xp_from_lessons'] ?? 0;

$totalXP = $xpFromQuests + $xpFromLessons;

// --- Program - Use user's saved program or default ---
$program = !empty($user['program']) ? $user['program'] : 'Weekend';

// --- Friends (excluding current user) ---
$friends = [];
$stmt = $conn->prepare("SELECT id, fullname FROM users WHERE id != ? LIMIT 20");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$friendsResult = $stmt->get_result();
while ($row = $friendsResult->fetch_assoc()) {
    $friends[] = $row;
}

// --- Get user's XP level based on total XP ---
function getLevel($xp) {
    if ($xp < 100) return ['level' => 'Beginner', 'emoji' => '🌱'];
    if ($xp < 300) return ['level' => 'Intermediate', 'emoji' => '📚'];
    if ($xp < 600) return ['level' => 'Advanced', 'emoji' => '🚀'];
    if ($xp < 1000) return ['level' => 'Expert', 'emoji' => '🏆'];
    return ['level' => 'Master', 'emoji' => '👑'];
}
$levelInfo = getLevel($totalXP);

// Share link - redirects to register page with referral
$shareLink = "https://" . $_SERVER['HTTP_HOST'] . "/hackthon/auth/register.php?ref=" . $user_id;



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
    <!-- Include sidebar and navbar -->
    <?php require_once("../includes/sidebar.php"); ?>
    <div class="main">
        <?php require_once("../includes/navbar.php"); ?>
        <div class="content">
            <div class="profile-content">
                <!-- Welcome Banner -->
                <div class="profile-welcome">
                    <h1>👋 Welcome, <span><?php echo htmlspecialchars($user['fullname']); ?></span></h1>
                    <p>Manage your profile, track your achievements, and connect with friends.</p>
                </div>

                <!-- Success Message from Edit Profile -->
                <?php if ($update_message): ?>
                <div style="
                    background: #d4edda;
                    color: #155724;
                    padding: 15px 20px;
                    border-radius: 10px;
                    margin-bottom: 20px;
                    border: 1px solid #c3e6cb;
                    font-weight: 500;
                ">
                    <?php echo $update_message; ?>
                </div>
                <?php endif; ?>

                <!-- Profile Card -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <div class="profile-avatar">
                            <?php 
                                $initials = '';
                                $nameParts = explode(' ', trim($user['fullname']));
                                foreach ($nameParts as $part) {
                                    if (!empty($part)) $initials .= strtoupper($part[0]);
                                }
                                echo substr($initials, 0, 2);
                            ?>
                        </div>
                        <div class="profile-info">
                            <h2><?php echo htmlspecialchars($user['fullname']); ?></h2>
                            <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                            <span class="profile-date">📅 Joined <?php echo $formattedDate; ?></span>
                            <span class="profile-badge-level">
                                <?php echo $levelInfo['emoji']; ?> <?php echo $levelInfo['level']; ?>
                            </span>
                        </div>
                        <!-- Edit Profile Link -->
                        <a href="edit_profile.php" >✏️ Edit Profile</a>
                    </div>

                    <!-- Stats -->
                    <div class="stats-grid-profile">
                        <div class="stat-card-profile">
                            <span class="stat-icon">🏅</span>
                            <div class="stat-number"><?php echo $badgesCount; ?></div>
                            <div class="stat-label">Awards</div>
                        </div>
                        <div class="stat-card-profile">
                            <span class="stat-icon">⭐</span>
                            <div class="stat-number"><?php echo number_format($totalXP); ?></div>
                            <div class="stat-label">Points (XP)</div>
                        </div>
                        <div class="stat-card-profile">
                            <span class="stat-icon">📋</span>
                            <div class="stat-number" style="font-size:18px;"><?php echo htmlspecialchars($program); ?></div>
                            <div class="stat-label">Program</div>
                        </div>
                        <div class="stat-card-profile">
                            <span class="stat-icon">🏆</span>
                            <div class="stat-number" style="font-size:18px;"><?php echo $levelInfo['level']; ?></div>
                            <div class="stat-label">Level</div>
                        </div>
                    </div>
                </div>

                <!-- Experience - Shows user's experience from database -->
                <div class="profile-card">
                    <h3 class="section-header">🧠 Experience</h3>
                    
                </div>
                
                <!-- Add Friends -->
                <div class="profile-card">
                    <h3 class="section-header">👥 Add Friends</h3>
                    <a href="friends.php" style="text-decoration: none;">
                        <button class="add-friend-btn">+ Add</button>
                    </a>
                </div>

                <!-- Share Link -->
                <div class="profile-card">
                    <h3 class="section-header">🔗 Share Link</h3>
                    <div class="share-link-box">
                        <input 
                            type="hidden" 
                            id="shareLinkInput" 
                            value="<?php echo htmlspecialchars($shareLink); ?>" 
                        />
                        <button class="copy-btn" onclick="copyShareLink()" style="width: 100%;">
                            📋 Copy Invitation Link
                        </button>
                        <p class="share-description">
                            Share this link with friends. They will be redirected to the registration page.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toast notification function
        function showToast(message, isSuccess = true) {
            // Remove any existing toast
            const existingToast = document.querySelector('.toast-notification');
            if (existingToast) {
                existingToast.remove();
            }

            const toast = document.createElement('div');
            toast.className = 'toast-notification ' + (isSuccess ? 'toast-success' : 'toast-error');
            toast.textContent = message;
            document.body.appendChild(toast);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                toast.style.animation = 'slideDown 0.3s ease';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.remove();
                    }
                }, 300);
            }, 3000);
        }

        // Copy share link function
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
    </script>
    <script src="../javascript/script.js"></script>
</body>
</html>