<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $month_filter = $_GET['month'] ?? ''; // 'YYYY-MM'
        
        $params = [];
        $date_condition = "";
        
        if (!empty($month_filter)) {
            $parts = explode('-', $month_filter);
            if (count($parts) == 2) {
                $y = (int)$parts[0];
                $m = (int)$parts[1];
                $date_condition = " AND MONTH(cp.created_at) = $m AND YEAR(cp.created_at) = $y ";
            }
        }

        // Base Query leveraging the new cheque_payments table
        $sql = "
            SELECT 
                cp.payment_id, cp.sale_id, cp.amount as total_amount, 
                cp.bank_name as parsed_bank, cp.cheque_number as parsed_number, 
                cp.cheque_date as parsed_date, cp.created_at as sale_date,
                cp.status, cp.customer_id,
                c.name as customer_name, c.nic as customer_nic
            FROM cheque_payments cp
            LEFT JOIN customers c ON cp.customer_id = c.customer_id
            WHERE 1=1
            $date_condition
            ORDER BY cp.cheque_date ASC, cp.created_at DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $cheques = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process dynamic metrics based natively extracted details
        $total_payments = 0;
        $cheques_to_bank = 0;
        $nearest_date = null;
        $current_date = date('Y-m-d');
        
        $processed_cheques = [];

        foreach ($cheques as &$row) {
            $amount = (float)$row['total_amount'];
            $cheque_date = $row['parsed_date'];

            // Metric: Total
            $total_payments += $amount;

            // Metric: To Bank (Assuming cheques dated today or in the future are 'to bank')
            if ($cheque_date && $cheque_date >= $current_date) {
                $cheques_to_bank += $amount;
                
                // Metric: Nearest Date
                if ($nearest_date === null || $cheque_date < $nearest_date) {
                    $nearest_date = $cheque_date;
                }
            }
            
            $processed_cheques[] = $row;
        }

        echo json_encode([
            'success' => true,
            'metrics' => [
                'total_payments' => $total_payments,
                'cheques_to_bank' => $cheques_to_bank,
                'nearest_date' => $nearest_date ?: 'N/A'
            ],
            'data' => $processed_cheques
        ]);

    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
