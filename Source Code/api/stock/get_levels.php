<?php
// This API fetches all stock levels for a specific branch
session_start();
include_once '../../config/database.php';
header('Content-Type: application/json');

// Security check: Ensure user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit;
}

$user_role = $_SESSION['role'] ?? 'staff';

// --- ROLE-BASED BRANCH SELECTION ---
if ($user_role === 'staff') {
    // STAFF: Force use of their assigned branch from session
    if (empty($_SESSION['branch_id'])) {
        http_response_code(403);
        echo json_encode(['error' => 'You are not assigned to a branch. Contact admin.']);
        exit;
    }
    $branch_id = $_SESSION['branch_id'];
} else {
    // ADMIN: Must provide a branch_id via GET request
    if (empty($_GET['branch_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Branch ID is required.']);
        exit;
    }
    $branch_id = filter_var($_GET['branch_id'], FILTER_VALIDATE_INT);
}
// --- END ROLE CHECK ---

if ($branch_id === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid Branch ID.']);
    exit;
}

try {
    // 1. Fetch all products
    // 2. LEFT JOIN product_stock for the specific branch
    // This ensures all products are listed, even those with 0 stock at this branch
    // Uses 'product_stock' table instead of 'stock'
    $query = "SELECT 
                p.product_id, 
                p.name AS product_name, 
                p.product_code, 
                p.reorder_level,
                COALESCE(s.stock, 0) AS stock
            FROM 
                products p
            LEFT JOIN 
                product_stock s ON p.product_id = s.product_id AND s.branch_id = :branch_id
            ORDER BY 
                p.name ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':branch_id', $branch_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $stock_levels = $stmt->fetchAll();

    echo json_encode($stock_levels);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>