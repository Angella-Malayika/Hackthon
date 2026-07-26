<?php
require_once("../middleware/user.php");
require_once("../core.php");

$user_id = $_SESSION['user_id'];

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_feedback'])) {
    $subject = trim($_POST['subject'] ?? '');
    $msgText = trim($_POST['message'] ?? '');

    if (empty($msgText)) {
        $message = "Please write a message before submitting.";
        $messageType = "error";
    } else {
        $stmt = $conn->prepare("INSERT INTO feedback (user_id, subject, message, status, created_at) VALUES (?, ?, ?, 'unread', NOW())");
        $stmt->bind_param("iss", $user_id, $subject, $msgText);
        $stmt->execute();

        $message = "Thank you! Your feedback has been sent to the admin team.";
        $messageType = "success";
    }
}

// Fetch this user's feedback history, most recent first
$history = [];
$stmt = $conn->prepare("SELECT id, subject, message, status, reply, replied_at, created_at FROM feedback WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $history[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php require_once("../includes/sidebar.php"); ?>

    <div class="page-container">
        <?php require_once("../includes/navbar.php"); ?>

        <div class="page-content">
            <div class="page-header">
                <h1>💬 Feedback</h1>
                <p>Share your thoughts, report issues, or suggest improvements. Admins can see and reply to your feedback.</p>
            </div>

            <?php if ($message): ?>
            <div class="toast-message <?php echo $messageType; ?>">
                <span><?php echo $messageType == 'success' ? '✅' : '⚠️'; ?></span>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <div class="feedback-form-card">
                <h3 style="margin-top:0;">Send Feedback</h3>
                <form method="POST" action="">
                    <div class="input-group" style="margin-bottom: 14px;">
                        <label style="display:block; margin-bottom:6px; font-weight:600; font-size: 14px;">Subject (optional)</label>
                        <input type="text" name="subject" placeholder="e.g. Bug report, Suggestion, Question..."
                               style="width:100%; padding:12px 14px; border:1px solid #ddd; border-radius:8px; box-sizing:border-box;">
                    </div>
                    <div class="input-group" style="margin-bottom: 14px;">
                        <label style="display:block; margin-bottom:6px; font-weight:600; font-size: 14px;">Message</label>
                        <textarea name="message" placeholder="Tell us what's on your mind..." required></textarea>
                    </div>
                    <button type="submit" name="submit_feedback" class="btn-save">📨 Submit Feedback</button>
                </form>
            </div>

            <h3>Your Feedback History</h3>
            <?php if (empty($history)): ?>
                <div class="no-quests">You haven't submitted any feedback yet.</div>
            <?php else: ?>
                <?php foreach ($history as $f): ?>
                    <div class="feedback-history-item">
                        <strong><?php echo htmlspecialchars($f['subject'] ?: 'General Feedback'); ?></strong>
                        <span class="fh-status <?php echo $f['status']; ?>"><?php echo ucfirst($f['status']); ?></span>
                        <div class="fh-date"><?php echo date('M j, Y g:i A', strtotime($f['created_at'])); ?></div>
                        <p style="margin: 8px 0 0;"><?php echo nl2br(htmlspecialchars($f['message'])); ?></p>
                        <?php if (!empty($f['reply'])): ?>
                            <div class="feedback-reply-box">
                                <strong>Admin reply:</strong> <?php echo nl2br(htmlspecialchars($f['reply'])); ?>
                                <?php if ($f['replied_at']): ?>
                                    <div style="font-size:11px; color:#6c9; margin-top:4px;">
                                        <?php echo date('M j, Y g:i A', strtotime($f['replied_at'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="../javascript/script.js"></script>
</body>
</html>
