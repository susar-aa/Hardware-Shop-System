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

if ($method === 'GET') {
    $customer_id = $_GET['id'] ?? null;
    
    if (!$customer_id) {
        echo json_encode(['success' => false, 'error' => 'Customer ID missing']);
        exit;
    }

    try {
        // 1. Get Basic Customer Details
        $stmt_cus = $pdo->prepare("SELECT * FROM customers WHERE customer_id = ?");
        $stmt_cus->execute([$customer_id]);
        $customer = $stmt_cus->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            echo json_encode(['success' => false, 'error' => 'Customer not found']);
            exit;
        }

        // 2. Get Overall Credit Balance
        // Summing all partial payments for invoices that belong to the customer where balance > 0
        // Or reading from an aggregated credit table. Since `credit_payments` records partial payments:
        $stmt_credit = $pdo->prepare("
            SELECT SUM(amount) as total_credits
            FROM credit_payments 
            WHERE customer_id = ?
        ");
        $stmt_credit->execute([$customer_id]);
        $total_credits = $stmt_credit->fetchColumn() ?: 0;
        
        // Wait, 'credit_payments' acts like sales/debt or payments *made* against debt?
        // Let's get the 5 most recent sales that were made with 'credit' payment option:
        $stmt_credit_sales = $pdo->prepare("
            SELECT sale_id, total_amount, sale_date
            FROM sales
            WHERE customer_id = ? AND payment_method = 'credit'
            ORDER BY sale_date DESC LIMIT 10
        ");
        $stmt_credit_sales->execute([$customer_id]);
        $credit_history = $stmt_credit_sales->fetchAll(PDO::FETCH_ASSOC);

        // 3. Get Recent Cheque History
        $stmt_cheques = $pdo->prepare("
            SELECT payment_id, sale_id, amount, bank_name, cheque_number, cheque_date, status
            FROM cheque_payments
            WHERE customer_id = ?
            ORDER BY cheque_date DESC LIMIT 10
        ");
        $stmt_cheques->execute([$customer_id]);
        $cheque_history = $stmt_cheques->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'customer' => $customer,
            'credit_records' => $credit_history,
            'cheque_records' => $cheque_history
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
