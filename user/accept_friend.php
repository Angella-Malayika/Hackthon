<?php
require_once("../middleware/user.php");
require_once("../core.php");

$user_id = $_SESSION['user_id'];
$requestId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($requestId > 0) {
    // Only the recipient of the request (friend_id) may accept it.
    $stmt = $conn->prepare("UPDATE friends SET status = 'accepted' WHERE id = ? AND friend_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $requestId, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Accepting a friend request may unlock a friends-based achievement.
        evaluate_achievements($conn, $user_id);
    }
}

header("Location: friends.php");
exit();
