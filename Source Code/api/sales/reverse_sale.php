<?php
// This API handles the reversal of a sales transaction header.
session_start();
include_once '../../config/database.php';
header('Content-Type: application/json');

// Security check: Must be Admin to reverse sales
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied. Only Admins can reverse sales.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'));
$sale_id = $data->sale_id ?? null;
$reversal_notes = $data->reversal_notes ?? 'Sale reversal/refund.';

if (!$sale_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Sale ID is required for reversal.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$reversal_type = 'in'; 
$notes_prefix = 'Reversal of Sale #';

try {
    $pdo->beginTransaction();

    // 1. Fetch the original 'sales' header and items. Lock sales row.
    $sale_header_query = "SELECT * FROM sales WHERE sale_id = :id AND is_reversed = 0 FOR UPDATE";
    $sale_header_stmt = $pdo->prepare($sale_header_query);
    $sale_header_stmt->bindParam(':id', $sale_id, PDO::PARAM_INT);
    $sale_header_stmt->execute();
    $sale_header = $sale_header_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale_header) {
        throw new Exception("Sale transaction not found or already reversed.", 404);
    }
    
    $branch_id = $sale_header['branch_id'];

    // Get all line items for this sale
    $items_query = "SELECT product_id, quantity, unit_price FROM sale_items WHERE sale_id = :sale_id";
    $items_stmt = $pdo->prepare($items_query);
    $items_stmt->bindParam(':sale_id', $sale_id, PDO::PARAM_INT);
    $items_stmt->execute();
    $sale_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);


    // 2. Loop through all items and add stock back
    foreach ($sale_items as $item) {
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];
        
        // a. Add stock back to product_stock table
        $stock_update_query = "UPDATE product_stock 
                               SET stock = stock + :quantity 
                               WHERE product_id = :product_id AND branch_id = :branch_id";
        $stock_update_stmt = $pdo->prepare($stock_update_query);
        $stock_update_stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $stock_update_stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stock_update_stmt->bindParam(':branch_id', $branch_id, PDO::PARAM_INT);
        $stock_update_stmt->execute();

        // b. Log the reversal transaction (for each item)
        $log_query = "INSERT INTO stock_transactions (product_id, branch_id, user_id, transaction_type, quantity, notes, sale_id)
                      VALUES (:product_id, :branch_id, :user_id, :transaction_type, :quantity, :notes, :sale_id)";
        $log_stmt = $pdo->prepare($log_query);
        $log_stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $log_stmt->bindParam(':branch_id', $branch_id, PDO::PARAM_INT);
        $log_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $log_stmt->bindParam(':transaction_type', $reversal_type);
        $log_stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $log_stmt->bindParam(':notes', $notes_prefix . $sale_id . ' - ' . $reversal_notes);
        $log_stmt->bindParam(':sale_id', $sale_id, PDO::PARAM_INT);
        $log_stmt->execute();
    }


    // 3. Mark original sale header as reversed
    $mark_reversed_query = "UPDATE sales SET is_reversed = 1, reversal_user_id = :user_id WHERE sale_id = :id";
    $mark_reversed_stmt = $pdo->prepare($mark_reversed_query);
    $mark_reversed_stmt->bindParam(':id', $sale_id, PDO::PARAM_INT);
    $mark_reversed_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $mark_reversed_stmt->execute();

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => "Sale #{$sale_id} successfully reversed and stock added back."]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $code = $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($code);
    echo json_encode(['error' => $e->getMessage()]);
}
?>