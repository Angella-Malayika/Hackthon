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

        // Prevent session fixation
        session_regenerate_id(true);

        // Store user information
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        // Redirect according to role
        if ($user['role'] == "admin") {

            header("Location: ../admin/Dashboard.php");
            exit();
        } else {

            header("Location: ../user/dashboard.php");
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
