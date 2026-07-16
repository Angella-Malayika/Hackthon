<?php
session_start();
require_once("../core.php");

// Check if token exists
if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("Invalid password reset link.");
}

$token = $_GET['token'];

// Find token
$stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows != 1) {
    die("Invalid or expired reset link.");
}

$reset = $result->fetch_assoc();

// Check expiry
if (strtotime($reset['expires_at']) < time()) {

    // Delete expired token
    $delete = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
    $delete->bind_param("s", $token);
    $delete->execute();

    die("This password reset link has expired.");
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    // Check passwords match
    if ($password != $confirm) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: new_password.php?token=" . $token);
        exit();
    }

    // Password rules
    if (
        strlen($password) < 8 ||
        strlen($password) > 16 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[\W]/', $password)
    ) {

        $_SESSION['error'] = "Password does not meet the required guidelines.";

        header("Location: new_password.php?token=" . $token);
        exit();
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Update user password
    $update = $conn->prepare("UPDATE users SET password=? WHERE email=?");
    $update->bind_param("ss", $hashedPassword, $reset['email']);
    $update->execute();

    // Delete token after successful reset
    $delete = $conn->prepare("DELETE FROM password_resets WHERE token=?");
    $delete->bind_param("s", $token);
    $delete->execute();

    $_SESSION['success'] = "Password updated successfully. Please login.";

    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create New Password</title>

    <link rel="stylesheet" href="../assets/style.css">

</head>

<body>

    <div class="container">

        <div class="signup-box">

            <h2>Create New Password</h2>

            <p>Choose a secure password.</p>

            <?php

            if (isset($_SESSION['error'])) {

                echo "<div class='error'>" . $_SESSION['error'] . "</div>";

                unset($_SESSION['error']);
            }

            ?>

            <form method="POST">

                <div class="input-group">
                    <label>New Password</label>
                    <input type="password" name="password" id="password" required>

                </div>

                <div class="input-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirmPassword" required>

                </div>

                <div class="show-password">
                    <input type="checkbox" id="showPassword">
                    <label for="showPassword"> Show Password </label>

                </div>

                <button class="btn"> Reset Password</button>

            </form>

            <div class="rules">

                <h4>Password Guidelines</h4>

                <ul>

                    <li id="length">❌ At least 8 characters long</li>

                    <li id="max">❌ Must not exceed 16 characters</li>

                    <li id="upper">❌ Include at least one uppercase letter</li>

                    <li id="lower">❌ Include at least one lowercase letter</li>

                    <li id="number">❌ Include at least one number</li>

                    <li id="special">❌ Include at least one special character</li>

                </ul>

            </div>

        </div>

    </div>

    <script src="../javascript/script.js"></script>

</body>

</html>