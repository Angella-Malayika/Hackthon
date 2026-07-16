<?php
session_start();
require_once("../core.php");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    <link rel="stylesheet" href="../assets/style.css">
</head>

<body>

    <div class="container">

        <div class="signup-box">

            <h2>Welcome Back</h2>

            <p>Login to continue.</p>

            <?php

            if (isset($_SESSION['success'])) {
                echo "<div class='success'>" . $_SESSION['success'] . "</div>";
                unset($_SESSION['success']);
            }

            if (isset($_SESSION['error'])) {
                echo "<div class='error'>" . $_SESSION['error'] . "</div>";
                unset($_SESSION['error']);
            }

            ?>

            <form action="authenticate.php" method="POST">

                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="Enter Email Address" required>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" id="loginPassword" placeholder="Enter Password" required>
                </div>

                <div class="show-password">
                    <input type="checkbox" id="showLoginPassword">
                    <label for="showLoginPassword"> Show Password </label>
                </div>

                <div class="login-options">
                    <a href="forgot_password.php"> Forgot Password? </a>
                </div>

                <button type="submit" class="btn"> Login </button>

            </form>

            <p class="login-link">
                Don't have an account?
                <a href="signup.php">
                    Create Account
                </a>

            </p>

        </div>

    </div>

    <script src="../javascript/script.js"></script>

</body>

</html>