<?php
session_start();
require_once("../core.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$theme = isset($_POST['theme']) && in_array($_POST['theme'], ['light', 'dark'], true)
    ? $_POST['theme']
    : 'light';

$_SESSION['theme'] = $theme;

$stmt = $conn->prepare("UPDATE users SET theme = ? WHERE id = ?");
$stmt->bind_param("si", $theme, $_SESSION['user_id']);
$stmt->execute();

header('Content-Type: application/json');
echo json_encode(['theme' => $theme]);
