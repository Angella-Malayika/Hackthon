<?php
session_start();
require_once("../core.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get and clean input
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check empty fields
    if (empty($fullname) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: signup.php");
        exit();
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email address.";
        header("Location: signup.php");
        exit();
    }

    // Password match
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: signup.php");
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
        header("Location: signup.php");
        exit();
    }

    // Check existing email
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Email already exists.";
        header("Location: signup.php");
        exit();
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Default role
    $role = "user";

    // Insert user
    $stmt = $conn->prepare("INSERT INTO users(fullname,email,password,role) VALUES(?,?,?,?)");
    $stmt->bind_param("ssss", $fullname, $email, $hashedPassword, $role);

    if ($stmt->execute()) {

        $_SESSION['success'] = "Account created successfully. Please login.";

        header("Location: login.php");
        exit();

    } else {

        $_SESSION['error'] = "Registration failed.";

        header("Location: signup.php");
        exit();

    }

}
?>