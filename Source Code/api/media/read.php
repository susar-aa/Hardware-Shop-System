<?php
session_start();
// No database connection needed unless you store images in DB
// We will scan a directory.

// Security check: Ensure user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit;
}

// Define the directory to scan.
// This assumes 'uploads' is in the root, relative to the project dir.
// The path is relative to *this* file (api/media/read.php).
$upload_dir = '../../uploads/';
$web_dir = 'uploads/'; // The web-accessible path

$images = [];

// Check if directory exists
if (!is_dir($upload_dir)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Uploads directory not found. Looked for: ' . realpath($upload_dir)]);
    exit;
}

$files = scandir($upload_dir);

foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        // Only include common web image formats
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            // Send the web-accessible URL
            $images[] = $web_dir . $file;
        }
    }
}

// Return newest images first
rsort($images);

header('Content-Type: application/json');
echo json_encode($images);
?>