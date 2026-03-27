<?php
session_start();
include_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$user_id = $_SESSION['user_id'];
$branch_id = $_SESSION['branch_id'] ?? null; // If admin, they might select branch via POST

try {
    if ($method === 'POST') {
        // Record a Payment
        $data = json_decode(file_get_contents('php://input'));
        
        if (empty($data->customer_id) || empty($data->amount)) {
            throw new Exception("Customer and Amount are required");
        }
        
        // If Admin provided a branch_id, use it. Else use session branch (for staff)
        // If Admin didn't provide, require it or default? Better require.
        // For now, let's assume if session branch is null (admin), we need it in data.
        $pay_branch = $branch_id;
        if(!$pay_branch && !empty($data->branch_id)) {
            $pay_branch = $data->branch_id;
        }
        if(!$pay_branch) throw new Exception("Branch ID required for payment record");

        $stmt = $pdo->prepare("INSERT INTO credit_payments (customer_id, branch_id, user_id, amount, notes) VALUES (:cid, :bid, :uid, :amt, :notes)");
        $stmt->execute([
            ':cid' => $data->customer_id,
            ':bid' => $pay_branch,
            ':uid' => $user_id,
            ':amt' => $data->amount,
            ':notes' => $data->notes ?? ''
        ]);

        echo json_encode(['success' => true, 'message' => 'Payment recorded successfully']);

    } elseif ($method === 'GET') {
        // Get Payment History for a Customer
        $customer_id = $_GET['customer_id'] ?? null;
        if (!$customer_id) throw new Exception("Customer ID required");

        $start_date = $_GET['start_date'] ?? null;
        $end_date = $_GET['end_date'] ?? null;

        $sql = "
            SELECT cp.*, b.branch_name, u.name as user_name 
            FROM credit_payments cp
            LEFT JOIN branches b ON cp.branch_id = b.branch_id
            LEFT JOIN users u ON cp.user_id = u.user_id
            WHERE cp.customer_id = :cid
        ";
        
        $params = [':cid' => $customer_id];
        
        if ($start_date) {
            $sql .= " AND DATE(cp.payment_date) >= :start_date";
            $params[':start_date'] = $start_date;
        }
        if ($end_date) {
            $sql .= " AND DATE(cp.payment_date) <= :end_date";
            $params[':end_date'] = $end_date;
        }
        
        $sql .= " ORDER BY cp.payment_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>