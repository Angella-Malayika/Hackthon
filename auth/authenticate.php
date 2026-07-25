<?php
session_start();

require_once("../core.php");

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: login.php");
    exit();
}

$email = trim($_POST['email']);
$password = $_POST['password'];

// Check if fields are empty
if (empty($email) || empty($password)) {

    $_SESSION['error'] = "Please fill in all fields.";

    header("Location: login.php");
    exit();
}

// Check whether email exists
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");

$stmt->bind_param("s", $email);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 1) {

    $user = $result->fetch_assoc();

    // Verify password
    if (password_verify($password, $user['password'])) {

        // Check if user is blocked
        if (isset($user['status']) && $user['status'] === 'blocked') {
            $_SESSION['error'] = "Your account has been blocked. Please contact the administrator.";
            header("Location: login.php");
            exit();
        }

        // Check if user is inactive
        if (isset($user['status']) && $user['status'] === 'inactive') {
            $_SESSION['error'] = "Your account is inactive. Please contact the administrator.";
            header("Location: login.php");
            exit();
        }

        // Prevent session fixation
        session_regenerate_id(true);

        // Store user information
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['theme'] = isset($user['theme']) && in_array($user['theme'], ['light', 'dark'], true)
            ? $user['theme']
            : 'light';

        // Redirect according to role
        if ($user['role'] == "admin") {

            header("Location: ../admin/dashboard.php");
            exit();
        } else {

            header("Location: ../user/learn.php");
            exit();
        }
    } else {

        $_SESSION['error'] = "Incorrect password.";

        header("Location: login.php");
        exit();
    }
} else {

    $_SESSION['error'] = "No account found with that email.";

    header("Location: login.php");
    exit();
}
