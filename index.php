<?php
session_start();

if (isset($_SESSION['user_id'])) {
    if (($_SESSION['role'] ?? '') === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/learn.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internet Governance & Awareness Platform</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
            background: #f5f7fb;
            color: #1f2937;
        }
        .hero-box {
            background: #ffffff;
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-radius: 20px;
            padding: 50px 40px;
            max-width: 520px;
            width: 90%;
        }
        .hero-box .logo {
            font-size: 60px;
            display: block;
            margin-bottom: 10px;
        }
        .hero-box h1 {
            color: #1f2937;
            font-size: 1.8rem;
            margin-bottom: 8px;
        }
        .hero-box p {
            color: #555555;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .hero-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .hero-buttons .hero-btn {
            display: inline-block;
            padding: 14px 40px;
            border-radius: 999px;
            font-size: 16px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        .hero-buttons .hero-btn-login {
            background: #2563eb;
            color: #ffffff;
            border: none;
        }
        .hero-buttons .hero-btn-login:hover {
            background: #1e40af;
        }
        .hero-buttons .hero-btn-signup {
            background: transparent;
            color: #1f2937;
            border: 2px solid #cccccc;
        }
        .hero-buttons .hero-btn-signup:hover {
            border-color: #2563eb;
            color: #2563eb;
        }
    </style>
</head>
<body data-theme="light">
    <div class="hero-box">
        <span class="logo">🌐</span>
        <h1>Internet Governance &amp; Awareness</h1>
        <p>Empowering digital citizens through knowledge. Learn about internet governance, privacy, cyber awareness, and responsible digital citizenship.</p>
        <div class="hero-buttons">
            <a href="auth/login.php" class="hero-btn hero-btn-login">Login</a>
            <a href="auth/signup.php" class="hero-btn hero-btn-signup">Create Account</a>
        </div>
    </div>
</body>
</html>

