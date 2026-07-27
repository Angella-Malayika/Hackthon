<?php
require_once("../middleware/user.php");
require_once("../core.php");

$user_id = $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

$search = "";

if(isset($_GET['search'])){
    $search = trim($_GET['search']);
}

/*
|--------------------------------------------------------------------------
| All Users
|--------------------------------------------------------------------------
*/

if($search != ""){

    $stmt = $conn->prepare("
        SELECT *
        FROM users
        WHERE id != ?
        AND fullname LIKE CONCAT('%', ?, '%')
        ORDER BY fullname ASC
    ");

    $stmt->bind_param("is",$user_id,$search);

}else{

    $stmt = $conn->prepare("
        SELECT *
        FROM users
        WHERE id != ?
        ORDER BY fullname ASC
    ");

    $stmt->bind_param("i",$user_id);

}

$stmt->execute();
$users = $stmt->get_result();

/*
|--------------------------------------------------------------------------
| Pending Friend Requests
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
SELECT
friends.id,
users.fullname,
users.profile_picture,
users.email
FROM friends
JOIN users
ON friends.user_id = users.id
WHERE friends.friend_id=?
AND friends.status='pending'
");

$stmt->bind_param("i",$user_id);
$stmt->execute();
$requests = $stmt->get_result();

/*
|--------------------------------------------------------------------------
| Accepted Friends
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
SELECT users.id,
users.fullname,
users.email,
users.profile_picture

FROM friends

JOIN users

ON
(
CASE

WHEN friends.user_id=?

THEN users.id=friends.friend_id

ELSE users.id=friends.user_id

END
)

WHERE

(friends.user_id=? OR friends.friend_id=?)

AND friends.status='accepted'
");

$stmt->bind_param("iii",$user_id,$user_id,$user_id);
$stmt->execute();
$friends=$stmt->get_result();

// Handle Add Friend via AJAX
if(isset($_POST['add_friend'])) {
    $friend_id = $_POST['friend_id'];
    
    // Check if already friends or request exists
    $check = $conn->prepare("
        SELECT id, status FROM friends 
        WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)
    ");
    $check->bind_param("iiii", $user_id, $friend_id, $friend_id, $user_id);
    $check->execute();
    $existing = $check->get_result();
    
    if($existing->num_rows > 0) {
        $row = $existing->fetch_assoc();
        if($row['status'] == 'accepted') {
            echo json_encode(['success' => false, 'message' => 'Already friends']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Friend request already sent']);
        }
        exit;
    }
    
    // Send friend request
    $stmt = $conn->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'pending')");
    $stmt->bind_param("ii", $user_id, $friend_id);
    
    if($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Friend request sent successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send friend request']);
    }
    exit;
}
?>

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Friends</title>

<link rel="stylesheet" href="../assets/style.css">

<link rel="stylesheet" href="../assets/friends.css">

<style>
    /* Toast notification styles */
    .toast-notification {
        position: fixed;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%);
        padding: 15px 30px;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        z-index: 9999;
        text-align: center;
        max-width: 90%;
        min-width: 200px;
        animation: slideUp 0.3s ease;
    }

    .toast-success {
        background: #03a60c;
        color: #fffdf7;
    }

    .toast-error {
        background: #dc3545;
        color: #fffdf7;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateX(-50%) translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
    }

    @keyframes slideDown {
        from {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
        to {
            opacity: 0;
            transform: translateX(-50%) translateY(20px);
        }
    }
</style>

</head>

<body>

<?php require_once("../includes/sidebar.php"); ?>

<div class="main">

<?php require_once("../includes/navbar.php"); ?>

<div class="content">

<div class="page-header">

<h1>👥 Friends</h1>

<p>Search for students, send requests and connect with fellow digital citizens.</p>

</div>

<!-- Search -->

<form method="GET" class="search-box">

<input
type="text"
name="search"
placeholder="Search by full name..."
value="<?php echo htmlspecialchars($search); ?>">

<button type="submit">

Search

</button>

</form>

<!-- Users -->

<div class="section">

<h2>Registered Users</h2>

<?php

if($users->num_rows>0){

while($row=$users->fetch_assoc()){

?>

<div class="friend-card">

<div class="friend-left">

<?php

if($row['profile_picture']!="default.png"){

?>

<img src="../uploads/<?php echo htmlspecialchars($row['profile_picture']); ?>">

<?php

}else{

?>

<div class="avatar">

<?php

echo strtoupper(substr($row['fullname'],0,1));

?>

</div>

<?php

}

?>

<div>

<h3>

<?php echo htmlspecialchars($row['fullname']); ?>

</h3>

<p>

<?php echo htmlspecialchars($row['email']); ?>

</p>

</div>

</div>

<div>

<button class="green-btn add-friend-btn" data-id="<?php echo $row['id']; ?>" data-name="<?php echo htmlspecialchars($row['fullname']); ?>">

Add Friend

</button>

</div>

</div>

<?php

}

}else{

?>

<p>No users found.</p>

<?php

}

?>

</div>

<!-- Pending Requests -->

<div class="section">

<h2>Pending Friend Requests</h2>

<?php

if($requests->num_rows>0){

while($row=$requests->fetch_assoc()){

?>

<div class="friend-card">

<div class="friend-left">

<?php

if($row['profile_picture']!="default.png"){

?>

<img src="../uploads/<?php echo htmlspecialchars($row['profile_picture']); ?>">

<?php

}else{

?>

<div class="avatar">

<?php

echo strtoupper(substr($row['fullname'],0,1));

?>

</div>

<?php

}

?>

<div>

<h3>

<?php echo htmlspecialchars($row['fullname']); ?>

</h3>

<p>

<?php echo htmlspecialchars($row['email']); ?>

</p>

</div>

</div>

<div>

<a href="accept_friend.php?id=<?php echo $row['id']; ?>">

<button class="green-btn">

Accept

</button>

</a>

<a href="decline_friend.php?id=<?php echo $row['id']; ?>">

<button class="red-btn">

Decline

</button>

</a>

</div>

</div>

<?php

}

}else{

?>

<p>No pending requests.</p>

<?php

}

?>

</div>

<!-- My Friends -->

<div class="section">

<h2>My Friends</h2>

<?php

if($friends->num_rows>0){

while($row=$friends->fetch_assoc()){

?>

<div class="friend-card">

<div class="friend-left">

<?php

if($row['profile_picture']!="default.png"){

?>

<img src="../uploads/<?php echo htmlspecialchars($row['profile_picture']); ?>">

<?php

}else{

?>

<div class="avatar">

<?php

echo strtoupper(substr($row['fullname'],0,1));

?>

</div>

<?php

}

?>

<div>

<h3>

<?php echo htmlspecialchars($row['fullname']); ?>

</h3>

<p>

<?php echo htmlspecialchars($row['email']); ?>

</p>

</div>

</div>

<span class="friend-badge">

Friends

</span>

</div>

<?php

}

}else{

?>

<p>You have no friends yet.</p>

<?php

}

?>

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

    // Friend functionality
    document.querySelectorAll('.add-friend-btn').forEach(button => {
        button.addEventListener('click', function() {
            const friendId = this.getAttribute('data-id');
            const friendName = this.getAttribute('data-name');
            
            // Disable button and show loading
            this.textContent = '⏳ Sending...';
            this.disabled = true;
            this.style.opacity = '0.7';
            
            // Send AJAX request
            const formData = new FormData();
            formData.append('add_friend', true);
            formData.append('friend_id', friendId);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('✅ ' + data.message, true);
                    this.textContent = '✓ Sent';
                    this.style.background = '#4a7a5a';
                    this.style.opacity = '1';
                } else {
                    showToast('❌ ' + data.message, false);
                    this.textContent = 'Add Friend';
                    this.disabled = false;
                    this.style.opacity = '1';
                }
            })
            .catch(error => {
                showToast('❌ Error sending friend request', false);
                this.textContent = 'Add Friend';
                this.disabled = false;
                this.style.opacity = '1';
            });
        });
    });
</script>

</body>

</html>