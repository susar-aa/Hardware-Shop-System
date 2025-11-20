<?php
// --- DATABASE CONFIGURATION ---
// Edit these settings to match your MySQL database credentials.

$host = 'localhost'; // or 'localhost'
$db_name = 'inventory_db';
$username = 'root'; // Your MySQL username
$password = ''; // Your MySQL password

// --- DO NOT EDIT BELOW THIS LINE ---

// Set DSN (Data Source Name)
$dsn = 'mysql:host=' . $host . ';dbname=' . $db_name . ';charset=utf8';

// Set PDO options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Create PDO instance
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Handle connection error
    // In a production environment, you would log this error, not echo it.
    // For development, we'll send a clear error message.
    http_response_code(500);
    // Set header *before* sending content
    header('Content-Type: application/json'); 
    echo json_encode(
        ['error' => 'Database connection failed: ' . $e->getMessage()]
    );
    // Stop script execution
    exit;
}

// Set header to return JSON
// REMOVED from here. Each API file will set its own header.
// header('Content-Type: application/json');

?>