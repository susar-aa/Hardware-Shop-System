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

try {
    // Common Balance Subquery Logic
    $balance_sql = "
        (
            COALESCE((SELECT SUM(s.total_amount) FROM sales s WHERE s.customer_id = c.customer_id AND s.payment_method = 'credit' AND s.is_reversed = 0), 0)
            -
            COALESCE((SELECT SUM(cp.amount) FROM credit_payments cp WHERE cp.customer_id = c.customer_id), 0)
        ) as current_balance,
        COALESCE((SELECT SUM(s.total_amount) FROM sales s WHERE s.customer_id = c.customer_id AND s.payment_method = 'credit' AND s.is_reversed = 0), 0) as total_debt,
        COALESCE((SELECT SUM(cp.amount) FROM credit_payments cp WHERE cp.customer_id = c.customer_id), 0) as total_paid
    ";

    if ($method === 'GET') {
        $search = $_GET['search'] ?? '';
        $id = $_GET['id'] ?? null;
        
        // New Parameters for Credit filtering
        $credit_tab = $_GET['credit_tab'] ?? ''; // 'ongoing', 'completed'
        $credit_month = $_GET['credit_month'] ?? ''; // 'YYYY-MM'
        
        $total_credit_history_sql = "
            (
                (SELECT COUNT(*) FROM sales s WHERE s.customer_id = c.customer_id AND s.payment_method = 'credit' AND s.is_reversed = 0)
                +
                (SELECT COUNT(*) FROM credit_payments cp WHERE cp.customer_id = c.customer_id)
            ) as total_credit_history
        ";

        if ($id) {
            // Get single customer details with balance
            $stmt = $pdo->prepare("SELECT c.*, $balance_sql, $total_credit_history_sql FROM customers c WHERE c.customer_id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        } else {
            // List / Search
            $sql = "SELECT * FROM (SELECT c.*, $balance_sql, $total_credit_history_sql FROM customers c) as derived WHERE 1=1";
            $params = [];
            
            if ($search) {
                // FIXED: Using unique parameter names to avoid issues when emulation is disabled
                $sql .= " AND (name LIKE :s1 OR phone LIKE :s2)";
                $params[':s1'] = "%$search%";
                $params[':s2'] = "%$search%";
            }
            
            // Tab filtering
            if ($credit_tab === 'ongoing') {
                $sql .= " AND current_balance > 0";
            } else if ($credit_tab === 'completed') {
                $sql .= " AND current_balance <= 0 AND total_credit_history > 0";
            }
            
            // Date filtering (checks if there is a credit sale OR credit payment in the month)
            if (!empty($credit_month)) {
                $parts = explode('-', $credit_month);
                if (count($parts) == 2) {
                    $y = (int)$parts[0];
                    $m = (int)$parts[1];
                    $sql .= " AND (
                        EXISTS (SELECT 1 FROM sales s WHERE s.customer_id = derived.customer_id AND s.payment_method = 'credit' AND MONTH(s.sale_date) = $m AND YEAR(s.sale_date) = $y)
                        OR
                        EXISTS (SELECT 1 FROM credit_payments cp WHERE cp.customer_id = derived.customer_id AND MONTH(cp.payment_date) = $m AND YEAR(cp.payment_date) = $y)
                    )";
                }
            }

            $sql .= " ORDER BY name ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }

    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'));
        if (empty($data->name)) throw new Exception("Customer Name is required");

        $stmt = $pdo->prepare("INSERT INTO customers (name, nic, phone, address) VALUES (:name, :nic, :phone, :address)");
        $stmt->execute([
            ':name' => $data->name,
            ':nic' => $data->nic ?? '',
            ':phone' => $data->phone ?? '',
            ':address' => $data->address ?? ''
        ]);
        echo json_encode(['success' => true, 'message' => 'Customer added', 'customer_id' => $pdo->lastInsertId()]);

    } elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'));
        if (empty($data->customer_id) || empty($data->name)) throw new Exception("ID and Name required");

        $stmt = $pdo->prepare("UPDATE customers SET name=:name, nic=:nic, phone=:phone, address=:address WHERE customer_id=:id");
        $stmt->execute([
            ':name' => $data->name,
            ':nic' => $data->nic ?? '',
            ':phone' => $data->phone ?? '',
            ':address' => $data->address ?? '',
            ':id' => $data->customer_id
        ]);
        echo json_encode(['success' => true, 'message' => 'Customer updated']);

    } elseif ($method === 'DELETE') {
        $data = json_decode(file_get_contents('php://input'));
        $id = $data->customer_id ?? ($_GET['id'] ?? null);
        
        if (!$id) throw new Exception("Customer ID required for deletion.");

        // 1. Check for ongoing credit
        $stmt = $pdo->prepare("SELECT $balance_sql FROM customers c WHERE c.customer_id = :id");
        $stmt->execute([':id' => $id]);
        $cust_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cust_data && (float)$cust_data['current_balance'] > 0) {
            throw new Exception("Cannot delete customer: There is an outstanding credit balance of LKR " . number_format($cust_data['current_balance'], 2));
        }

        // 2. Check for ongoing cheques
        $stmt_cheques = $pdo->prepare("SELECT COUNT(*) FROM cheque_payments WHERE customer_id = :id AND status IN ('pending', 'banked')");
        $stmt_cheques->execute([':id' => $id]);
        $ongoing_cheques = $stmt_cheques->fetchColumn();

        if ($ongoing_cheques > 0) {
            throw new Exception("Cannot delete customer: There are $ongoing_cheques pending or banked cheques connected to this account.");
        }

        // 3. Safe to delete. We can just delete the customer.
        // Wait, do we want to delete them or just mark them? Assuming hard delete because there are no foreign key cascads or we want to clean up.
        // If there are completed sales/cheques, deleting them might break previous records unless customer_id is nullable.
        // It's nullable in cheque_payments and sales. 
        $stmt_del_sales = $pdo->prepare("UPDATE sales SET customer_id = NULL WHERE customer_id = :id");
        $stmt_del_sales->execute([':id' => $id]);
        
        $stmt_del_chq = $pdo->prepare("UPDATE cheque_payments SET customer_id = NULL WHERE customer_id = :id");
        $stmt_del_chq->execute([':id' => $id]);

        $stmt_del_cred = $pdo->prepare("UPDATE credit_payments SET customer_id = NULL WHERE customer_id = :id");
        $stmt_del_cred->execute([':id' => $id]);

        $stmt_del = $pdo->prepare("DELETE FROM customers WHERE customer_id = :id");
        $stmt_del->execute([':id' => $id]);
        
        echo json_encode(['success' => true, 'message' => 'Customer successfully deleted']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>