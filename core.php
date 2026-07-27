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

/*
|--------------------------------------------------------------------------
| Self-healing schema setup
|--------------------------------------------------------------------------
| Makes sure columns/tables the app code depends on always exist, even if
| the imported .sql file is older than the code. Safe to run on every
| request: every statement checks first before altering anything.
*/
require_once(__DIR__ . "/includes/schema_setup.php");
ensure_schema($conn);
?>