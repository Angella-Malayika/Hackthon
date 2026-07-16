<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Forgot Password</title>

    <link rel="stylesheet" href="../assets/style.css">

</head>

<body>

    <div class="container">

        <div class="signup-box">

            <h2>Forgot Password</h2>

            <p>Enter your registered email address.</p>

            <?php

            if (isset($_SESSION['error'])) {
                echo "<div class='error'>" . $_SESSION['error'] . "</div>";
                unset($_SESSION['error']);
            }

            if (isset($_SESSION['success'])) {
                echo "<div class='success'>" . $_SESSION['success'] . "</div>";
                unset($_SESSION['success']);
            }

            ?>

            <form action="reset_password.php" method="POST">
                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="Enter your email" required>
                </div>

                <button class="btn"> Send Reset Link </button>

            </form>

            <p class="login-link"><a href="login.php"> Back to Login</a></p>

        </div>

    </div>

</body>

</html>