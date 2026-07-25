<?php
// Check if session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Check if user has admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../user/learn.php");
    exit();
}

// Optional: Store admin info
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['fullname'] ?? 'Admin';
?>