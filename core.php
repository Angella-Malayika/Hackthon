<?php
// create connection
$servername = "localhost";
$username = "root";
$password = "";
$db_name = "hackthon";

// Default XAMPP MySQL port is usually 3306. If your MySQL server runs on another port, update it here.
$port = 3305;

$conn = new mysqli($servername, $username, $password, $db_name, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// keep output quiet for included core file
?>