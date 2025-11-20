<?php
// This API fetches the current stock for one product at one branch
session_start();
include_once '../../config/database.php';
header('Content-Type: application/json');

// Security check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

// Validation
if (empty($_GET['product_id']) || empty($_GET['branch_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Product ID and Branch ID are required.']);
    exit;
}

$product_id = filter_var($_GET['product_id'], FILTER_VALIDATE_INT);
$branch_id = filter_var($_GET['branch_id'], FILTER_VALIDATE_INT);

try {
    $query = "SELECT stock FROM product_stock WHERE product_id = :product_id AND branch_id = :branch_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->bindParam(':branch_id', $branch_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // Found a stock record
        echo json_encode(['stock' => (int)$result['stock']]);
    } else {
        // No stock record, so stock is 0
        echo json_encode(['stock' => 0]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>