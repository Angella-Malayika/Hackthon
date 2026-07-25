<?php
require_once("../core.php");

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$level = $data['level'] ?? 1;

// Start the level session
$_SESSION['level' . $level . '_started'] = true;
$_SESSION['level' . $level . '_completed'] = false;

echo json_encode(['success' => true, 'message' => 'Level started']);
