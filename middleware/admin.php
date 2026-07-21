<?php

require_once("auth.php");

if($_SESSION['role'] != "admin"){

    header("Location: ../user/dashboard.php");
    exit();

}