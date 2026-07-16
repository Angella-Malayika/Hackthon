<?php
session_start();

require_once("../core.php");

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: forgot_password.php");
    exit();
}

$email = trim($_POST['email']);

// Check whether the email exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {

    $_SESSION['error'] = "Email address not found.";

    header("Location: forgot_password.php");
    exit();
}

// Generate a secure token
$token = bin2hex(random_bytes(32));

// Token expires after one hour
$expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

// Remove old reset requests
$delete = $conn->prepare("DELETE FROM password_resets WHERE email=?");
$delete->bind_param("s", $email);
$delete->execute();

// Save new token
$insert = $conn->prepare("INSERT INTO password_resets(email,token,expires_at) VALUES(?,?,?)");
$insert->bind_param("sss", $email, $token, $expires);
$insert->execute();

// Build the reset link
$link = "http://localhost/INTERNET/auth/new_password.php?token=" . $token;

// Display it for local testing
echo "<h2>Password Reset Link</h2>";
echo "<p>Copy this link into your browser:</p>";
echo "<a href='$link'>$link</a>";
