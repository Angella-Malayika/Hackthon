<?php
require_once("../middleware/user.php");
require_once("../core.php");

$user_id = $_SESSION['user_id'];

// Get current user level
$stmt = $conn->prepare("SELECT level, xp FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$currentLevel = $user['level'];
$xp = $user['xp'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Learn</title>

<link rel="stylesheet" href="../assets/style.css">

<style>

.learn-container{
    margin-left:260px;
    padding:30px;
    background:#fffdf7;
    min-height:100vh;
}

.learn-header{
    background:#ffffff;
    padding:20px;
    border-radius:15px;
    box-shadow:0 5px 15px rgba(0,0,0,.08);
    margin-bottom:30px;
}

.learn-header h1{
    color:#03a60c;
}

.learn-header p{
    color:#666;
}

.path{
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:35px;
}

.level{
    width:120px;
    height:120px;
    border-radius:50%;
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    text-decoration:none;
    font-weight:bold;
    transition:.3s;
    font-size:18px;
}

.level:hover{
    transform:scale(1.08);
}

.unlocked{
    background:#03a60c;
    color:white;
    box-shadow:0 8px 20px rgba(3,166,12,.3);
}

.locked{
    background:#dddddd;
    color:#777;
    cursor:not-allowed;
}

.path-line{
    width:6px;
    height:45px;
    background:#03a60c;
}

.stats{
    display:flex;
    gap:20px;
    margin-bottom:30px;
}

.stat-card{
    flex:1;
    background:white;
    padding:20px;
    border-radius:15px;
    box-shadow:0 5px 15px rgba(0,0,0,.08);
    text-align:center;
}

.stat-card h2{
    color:#03a60c;
}

</style>

</head>

<body>

<?php require_once("../includes/sidebar.php"); ?>

<div class="learn-container">

<?php require_once("../includes/navbar.php"); ?>

<div class="learn-header">

<h1> Learning Journey</h1>

<p>Complete each level to unlock the next one.</p>

</div>

<div class="stats">

<div class="stat-card">
<h2><?php echo $currentLevel; ?></h2>
<p>Current Level</p>
</div>

<div class="stat-card">
<h2><?php echo $xp; ?></h2>
<p>XP Earned</p>
</div>

<div class="stat-card">
<h2>8</h2>
<p>Total Levels</p>
</div>

</div>

<div class="path">

<?php
for($i=1;$i<=8;$i++):

if($i <= $currentLevel){
?>

<a href="level<?php echo $i;?>.php" class="level unlocked">

<div style="font-size:35px;">⭐</div>

Level <?php echo $i;?>

</a>

<?php
}else{
?>

<div class="level locked">

<div style="font-size:35px;">🔒</div>

Level <?php echo $i;?>

</div>

<?php
}

if($i<8){
echo '<div class="path-line"></div>';
}

endfor;
?>

</div>

</div>

</body>
</html>