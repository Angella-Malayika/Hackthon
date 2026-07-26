<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: ../admin/dashboard.php");
    exit();
}
