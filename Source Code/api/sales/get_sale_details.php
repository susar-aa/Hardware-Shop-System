<?php
session_start();
include_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (empty($_GET['sale_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Sale ID is required']);
    exit;
}

$sale_id = filter_var($_GET['sale_id'], FILTER_VALIDATE_INT);

try {
    // 1. Fetch Header
    $query = "SELECT s.sale_id, s.sale_date, s.total_amount, s.branch_id, s.is_reversed, b.branch_name 
              FROM sales s 
              LEFT JOIN branches b ON s.branch_id = b.branch_id
              WHERE s.sale_id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id', $sale_id, PDO::PARAM_INT);
    $stmt->execute();
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        http_response_code(404);
        echo json_encode(['error' => 'Sale not found.']);
        exit;
    }

    if ($sale['is_reversed']) {
        http_response_code(400);
        echo json_encode(['error' => 'This sale has already been fully reversed.']);
        exit;
    }

    // 2. Fetch Items
    $queryItems = "SELECT si.product_id, si.quantity, si.unit_price, p.name as product_name, p.product_code
                   FROM sale_items si
                   LEFT JOIN products p ON si.product_id = p.product_id
                   WHERE si.sale_id = :id";
    $stmtItems = $pdo->prepare($queryItems);
    $stmtItems->bindParam(':id', $sale_id, PDO::PARAM_INT);
    $stmtItems->execute();
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    $sale['items'] = $items;

    echo json_encode($sale);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>