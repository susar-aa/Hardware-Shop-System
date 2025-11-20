<?php
// Includes the database connection
// This file is public and only reads categories.
include_once '../../config/database.php';

try {
    // SQL query to get categories
    $query = "SELECT category_id, category_name FROM categories ORDER BY category_name ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
    // Set header before output
    header('Content-Type: application/json');
    echo json_encode($categories);

} catch (PDOException $e) {
    // Handle query error
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to fetch categories: ' . $e->getMessage()]);
}
?>