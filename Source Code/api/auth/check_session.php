<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    echo json_encode([
        'authenticated' => true,
        'user' => [
            'user_id' => $_SESSION['user_id'],
            'name' => $_SESSION['name'],
            'role' => $_SESSION['role'],
            'branch_id' => $_SESSION['branch_id'] ?? null
        ]
    ]);
} else {
    http_response_code(401);
    echo json_encode(['authenticated' => false]);
}
?>