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
    $payment_id = $_POST['payment_id'] ?? null;

    if (!$payment_id) {
        echo json_encode(['success' => false, 'error' => 'Payment ID missing']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM cheque_payments WHERE payment_id = ?");
        $stmt->execute([$payment_id]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
