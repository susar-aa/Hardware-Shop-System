<?php
session_start();
include_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] === '') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$data = json_decode(file_get_contents('php://input'));

if (empty($data->sale_id) || empty($data->items) || empty($data->branch_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required data']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    foreach ($data->items as $item) {
        $productId = $item->product_id;
        $returnQty = (int)$item->quantity;

        // 0. Fetch original sale item to get the accurate unit price and current quantity
        // We lock the row to prevent race conditions
        $stmt = $pdo->prepare("SELECT quantity, unit_price FROM sale_items WHERE sale_id = :sid AND product_id = :pid FOR UPDATE");
        $stmt->execute([':sid' => $data->sale_id, ':pid' => $productId]);
        $saleItem = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$saleItem) {
            throw new Exception("Product ID $productId not found in this sale.");
        }

        $currentSoldQty = (int)$saleItem['quantity'];
        $unitPrice = (float)$saleItem['unit_price'];
        
        if ($returnQty > $currentSoldQty) {
            throw new Exception("Cannot return more items ($returnQty) than currently recorded on the invoice ($currentSoldQty).");
        }

        $refundAmount = $returnQty * $unitPrice;

        // 1. Add Stock Back (Inventory +)
        $stock_stmt = $pdo->prepare("UPDATE product_stock SET stock = stock + :qty WHERE product_id = :pid AND branch_id = :bid");
        $stock_stmt->execute([
            ':qty' => $returnQty,
            ':pid' => $productId,
            ':bid' => $data->branch_id
        ]);

        // 2. Log in stock_transactions (Audit Trail)
        $log_stmt = $pdo->prepare("INSERT INTO stock_transactions 
            (product_id, branch_id, user_id, transaction_type, quantity, notes, sale_id) 
            VALUES (:pid, :bid, :uid, 'in', :qty, :notes, :sid)");
        
        $note = "Product Return - Sale #" . $data->sale_id . " (" . $data->reason . ")";
        
        $log_stmt->execute([
            ':pid' => $productId,
            ':bid' => $data->branch_id,
            ':uid' => $user_id,
            ':qty' => $returnQty,
            ':notes' => $note,
            ':sid' => $data->sale_id
        ]);

        // 3. Record in sale_returns table (Permanent Return Record)
        $return_stmt = $pdo->prepare("INSERT INTO sale_returns 
            (sale_id, product_id, branch_id, user_id, quantity, refund_amount, reason) 
            VALUES (:sid, :pid, :bid, :uid, :qty, :refund, :reason)");
            
        $return_stmt->execute([
            ':sid' => $data->sale_id,
            ':pid' => $productId,
            ':bid' => $data->branch_id,
            ':uid' => $user_id,
            ':qty' => $returnQty,
            ':refund' => $refundAmount,
            ':reason' => $data->reason
        ]);

        // 4. UPDATE SALE DATA (Modify Original Invoice)
        // A. Update Item Quantity
        $newQty = $currentSoldQty - $returnQty;
        if ($newQty > 0) {
            // Reduce quantity if items remain
            $update_item = $pdo->prepare("UPDATE sale_items SET quantity = :qty WHERE sale_id = :sid AND product_id = :pid");
            $update_item->execute([':qty' => $newQty, ':sid' => $data->sale_id, ':pid' => $productId]);
        } else {
            // Remove item row completely if fully returned
            $delete_item = $pdo->prepare("DELETE FROM sale_items WHERE sale_id = :sid AND product_id = :pid");
            $delete_item->execute([':sid' => $data->sale_id, ':pid' => $productId]);
        }

        // B. Update Sale Header Total
        // We subtract the refund amount from the invoice total
        $update_header = $pdo->prepare("UPDATE sales SET total_amount = total_amount - :refund WHERE sale_id = :sid");
        $update_header->execute([':refund' => $refundAmount, ':sid' => $data->sale_id]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Returns processed. Invoice and Stock updated successfully.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Server Error: ' . $e->getMessage()]);
}
?>