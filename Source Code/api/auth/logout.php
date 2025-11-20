<?php
session_start();
session_destroy(); // Destroy all session data
header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>