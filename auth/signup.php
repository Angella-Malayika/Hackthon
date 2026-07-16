<?php
session_start();
require_once("../core.php");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account</title>

    <link rel="stylesheet" href="../assets/style.css">
</head>

<body>
    <?php

    if (isset($_SESSION['error'])) {

        echo "<div class='error'>" . $_SESSION['error'] . "</div>";

        unset($_SESSION['error']);
    }

    ?>

    <div class="container">

        <div class="signup-box">

            <h2>Create Account</h2>

            <p>Create your account to continue.</p>

            <form action="register.php" method="POST">

                <div class="input-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" placeholder="Enter Full Name" required>
                </div>

                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="Enter Email Address" required>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <input type="password" id="password" name="password" placeholder="Create Password" required>
                </div>

                <div class="input-group">
                    <label>Confirm Password</label>
                    <input type="password" id="confirmPassword" name="confirm_password" placeholder="Confirm Password" required>
                </div>

                <div class="show-password">
                    <input type="checkbox" id="showPassword">

                    <label for="showPassword"> Show Password </label>
                </div>

                <button type="submit" class="btn">
                    Create Account
                </button>

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

            <p class="login-link">
                Already have an account?
                <a href="login.php">Login</a>
            </p>

            <p class="forgot-link">
                <a href="forgot_password.php">Forgot Password?</a>
            </p>

        </div>

    </div>

    <script src="../javascript/script.js"></script>

</body>

</html>