<?php
session_start();
header('Content-Type: application/json');

// Security check: Ensure user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed.']);
    exit;
}

// Check if file was uploaded without errors
if (!isset($_FILES['mediaFile']) || $_FILES['mediaFile']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    $error_message = 'No file uploaded or an error occurred.';
    switch ($_FILES['mediaFile']['error']) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $error_message = 'File is too large (exceeds server limit).';
            break;
        case UPLOAD_ERR_PARTIAL:
            $error_message = 'File was only partially uploaded.';
            break;
        case UPLOAD_ERR_NO_FILE:
            $error_message = 'No file was selected.';
            break;
    }
    echo json_encode(['error' => $error_message]);
    exit;
}

// --- Server-side 2MB Validation ---
$max_size = 2 * 1024 * 1024; // 2 MB in bytes
if ($_FILES['mediaFile']['size'] > $max_size) {
    http_response_code(413); // Payload Too Large
    echo json_encode(['error' => 'File exceeds 2MB limit.']);
    exit;
}

// --- File Type Validation ---
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$file_ext = strtolower(pathinfo($_FILES['mediaFile']['name'], PATHINFO_EXTENSION));

if (!in_array($file_ext, $allowed_extensions)) {
    http_response_code(415); // Unsupported Media Type
    echo json_encode(['error' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.']);
    exit;
}

// --- Sanitize filename and create a unique name ---
$upload_dir = '../../uploads/';
// Create a new unique filename to prevent overwrites and security issues
$safe_filename = uniqid('img_', true) . '.' . $file_ext;
$target_path = $upload_dir . $safe_filename;

// Move the file from temp location to the uploads directory
if (move_uploaded_file($_FILES['mediaFile']['tmp_name'], $target_path)) {
    echo json_encode([
        'success' => true,
        'message' => 'File uploaded successfully.',
        'filePath' => 'uploads/' . $safe_filename
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to move uploaded file. Check directory permissions.']);
}
?>