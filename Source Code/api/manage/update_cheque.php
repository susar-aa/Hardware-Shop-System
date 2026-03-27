<?php
session_start();
include_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $payment_id = $_POST['payment_id'] ?? null;

    if (!$payment_id) {
        echo json_encode(['success' => false, 'error' => 'Payment ID missing']);
        exit;
    }

    try {
        if ($action === 'update_status') {
            $status = $_POST['status'] ?? 'pending';
            $stmt = $pdo->prepare("UPDATE cheque_payments SET status = ? WHERE payment_id = ?");
            $stmt->execute([$status, $payment_id]);
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'update_details') {
            $bank_name = $_POST['bank_name'] ?? '';
            $cheque_number = $_POST['cheque_number'] ?? '';
            $cheque_date = $_POST['cheque_date'] ?? '';
            $amount = $_POST['amount'] ?? 0;

            $stmt = $pdo->prepare("UPDATE cheque_payments SET bank_name = ?, cheque_number = ?, cheque_date = ?, amount = ? WHERE payment_id = ?");
            $stmt->execute([$bank_name, $cheque_number, $cheque_date, $amount, $payment_id]);
            echo json_encode(['success' => true]);
            exit;
        }
        
        echo json_encode(['success' => false, 'error' => 'Invalid action']);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
