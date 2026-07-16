<?php
// create connection
$servername = "localhost";
$username = "root";
$password = "";
$db_name = "hackthon";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $db_name, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

echo "";
?>