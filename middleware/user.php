<?php

require_once("auth.php");

if($_SESSION['role'] != "user"){

    header("Location: ../admin/dashboard.php");
    exit();

}