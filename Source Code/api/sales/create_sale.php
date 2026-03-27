<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$data = json_decode(file_get_contents('php://input'));

if (empty($data->branch_id) || empty($data->items)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$branch_id = $data->branch_id;
$payment_method = $data->payment_method ?? 'cash';
$customer_id = $data->customer_id ?? null;
$cheque_details_obj = $data->cheque_details ?? null; // Keep it as object/array to extract details
$cheque_details_json = $cheque_details_obj ? json_encode($cheque_details_obj) : null; // Keep JSON for backwards compatibility if needed, or we can just ignore it in sales.

// Credit sales are 'pending', cash/cheque are 'paid'
$payment_status = ($payment_method === 'credit') ? 'pending' : 'paid';

$total_amount = 0;
foreach ($data->items as $item) {
    $total_amount += ((int)$item->quantity * (float)($item->price ?? 0));
}
$formatted_total = sprintf('%0.2f', $total_amount);

try {
    $pdo->beginTransaction();

    // 1. Insert Sale Header
    $sale_query = "INSERT INTO sales (branch_id, user_id, total_amount, payment_method, cheque_details, customer_id, payment_status) 
                   VALUES (:bid, :uid, :total, :method, :cheque, :cid, :status)";
    $stmt = $pdo->prepare($sale_query);
    $stmt->execute([
        ':bid' => $branch_id,
        ':uid' => $user_id,
        ':total' => $formatted_total,
        ':method' => $payment_method,
        ':cheque' => $cheque_details_json, // Keep populating JSON on sales just in case
        ':cid' => $customer_id,
        ':status' => $payment_status
    ]);
    $sale_id = $pdo->lastInsertId();

    // 1.5 Handle Cheque Payment Record
    if ($payment_method === 'cheque' && $cheque_details_obj) {
        $chq_bank = $cheque_details_obj->cheque_bank ?? '';
        $chq_date = $cheque_details_obj->cheque_date ?? '';
        $chq_num = $cheque_details_obj->cheque_number ?? '';

        $chq_query = "INSERT INTO cheque_payments (sale_id, customer_id, user_id, branch_id, amount, bank_name, cheque_number, cheque_date, status) 
                      VALUES (:sid, :cid, :uid, :bid, :amt, :bank, :num, :cdate, 'pending')";
        $chq_stmt = $pdo->prepare($chq_query);
        $chq_stmt->execute([
            ':sid' => $sale_id,
            ':cid' => $customer_id,
            ':uid' => $user_id,
            ':bid' => $branch_id,
            ':amt' => $formatted_total,
            ':bank' => $chq_bank,
            ':num' => $chq_num,
            ':cdate' => $chq_date
        ]);
    }

    // 2. Insert Items & Update Stock
    foreach ($data->items as $item) {
        $pid = $item->product_id;
        $qty = (int)$item->quantity;
        $uprice = sprintf('%0.2f', (float)$item->price);

        // Update Stock
        $upd_stmt = $pdo->prepare("UPDATE product_stock SET stock = stock - :qty WHERE product_id = :pid AND branch_id = :bid");
        $upd_stmt->execute([':qty' => $qty, ':pid' => $pid, ':bid' => $branch_id]);

        // Record Sale Item
        $item_stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
        $item_stmt->execute([$sale_id, $pid, $qty, $uprice]);

        // Audit Log
        $log_stmt = $pdo->prepare("INSERT INTO stock_transactions (product_id, branch_id, user_id, transaction_type, quantity, sale_id, notes) VALUES (?, ?, ?, 'out', ?, ?, 'Sale processed')");
        $log_stmt->execute([$pid, $branch_id, $user_id, $qty, $sale_id]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'sale_id' => $sale_id]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>