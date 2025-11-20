<?php
// Includes the database connection
include_once '../../config/database.php';
// We need to check session to make sure only logged-in users can access reports
session_start();

// Security check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit;
}

try {
    // This query joins products, stock, and branches
    // It filters where the stock is less than or equal to the reorder_level
    $query = "SELECT 
                p.name AS product_name,
                b.branch_name,
                ps.stock,
                p.reorder_level
              FROM 
                product_stock ps
              JOIN 
                products p ON ps.product_id = p.product_id
              JOIN 
                branches b ON ps.branch_id = b.branch_id
              WHERE 
                ps.stock <= p.reorder_level
              ORDER BY 
                b.branch_name, p.name";

    // --- Role-Based Filtering ---
    // If the user is 'staff', they should only see low stock for their own branch
    if ($_SESSION['role'] == 'staff' && isset($_SESSION['branch_id'])) {
        $query = "SELECT 
                    p.name AS product_name,
                    b.branch_name,
                    ps.stock,
                    p.reorder_level
                  FROM 
                    product_stock ps
                  JOIN 
                    products p ON ps.product_id = p.product_id
                  JOIN 
                    branches b ON ps.branch_id = b.branch_id
                  WHERE 
                    ps.stock <= p.reorder_level
                    AND ps.branch_id = :branch_id
                  ORDER BY 
                    p.name";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':branch_id', $_SESSION['branch_id'], PDO::PARAM_INT);
    
    } else {
        // 'admin' users see all branches
        $stmt = $pdo->prepare($query);
    }
    
    $stmt->execute();
    $lowStockItems = $stmt->fetchAll();

    // Send the data
    echo json_encode($lowStockItems);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch low stock report: ' . $e->getMessage()]);
}
?>