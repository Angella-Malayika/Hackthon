<?php

require_once("auth.php");

if(!isset($_SESSION['role']) || $_SESSION['role'] != "user"){

    header("Location: ../admin/dashboard.php");
    exit();

}
