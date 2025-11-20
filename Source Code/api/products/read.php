<?php
// Includes the database connection
include_once '../../config/database.php';

try {
    // SQL query to get products and join with categories
    // UPDATED: Added WHERE clause to only show visible products
    $query = "SELECT 
                p.product_id, 
                p.product_code, 
                p.name, 
                p.description, 
                p.price, 
                p.image,
                c.category_id,
                c.category_name 
            FROM 
                products p
            LEFT JOIN 
                categories c ON p.category_id = c.category_id
            WHERE 
                p.is_visible = 1
            ORDER BY 
                p.name ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute();

    // Fetch all products
    $products = $stmt->fetchAll();

    // Set header *before* sending content
    header('Content-Type: application/json');

    // Check if products were found
    if ($products) {
        echo json_encode($products);
    } else {
        echo json_encode([]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to fetch products: ' . $e->getMessage()]);
}
?>