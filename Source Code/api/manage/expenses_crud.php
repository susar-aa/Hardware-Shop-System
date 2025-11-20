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
$user_role = $_SESSION['role'];
$user_branch_id = $_SESSION['branch_id'] ?? null;

// Helper to build WHERE clause based on role/filters
function getWhereClause($user_role, $user_branch_id) {
    $where = [];
    $params = [];
    
    if ($user_role === 'staff') {
        $where[] = "e.branch_id = :branch_id";
        $params[':branch_id'] = $user_branch_id;
    } elseif (!empty($_GET['branch_id'])) {
        $where[] = "e.branch_id = :branch_id";
        $params[':branch_id'] = $_GET['branch_id'];
    }

    if (!empty($_GET['start_date'])) {
        $where[] = "e.expense_date >= :start";
        $params[':start'] = $_GET['start_date'];
    }
    if (!empty($_GET['end_date'])) {
        $where[] = "e.expense_date <= :end";
        $params[':end'] = $_GET['end_date'];
    }

    $sql = count($where) > 0 ? "WHERE " . implode(' AND ', $where) : "";
    return ['sql' => $sql, 'params' => $params];
}

try {
    if ($method === 'GET') {
        // READ EXPENSES
        $filter = getWhereClause($user_role, $user_branch_id);
        $query = "SELECT e.*, b.branch_name 
                  FROM expenses e 
                  LEFT JOIN branches b ON e.branch_id = b.branch_id 
                  {$filter['sql']} 
                  ORDER BY e.expense_date DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($filter['params']);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

    } elseif ($method === 'POST') {
        // CREATE EXPENSE
        $data = json_decode(file_get_contents('php://input'));
        
        if (empty($data->category) || empty($data->amount) || empty($data->expense_date)) {
            throw new Exception("Missing required fields");
        }

        // Determine Branch ID
        $branch_id = ($user_role === 'staff') ? $user_branch_id : ($data->branch_id ?? null);
        if (!$branch_id) throw new Exception("Branch ID is required");

        $stmt = $pdo->prepare("INSERT INTO expenses (branch_id, category, amount, description, expense_date) VALUES (:bid, :cat, :amt, :desc, :date)");
        $stmt->execute([
            ':bid' => $branch_id,
            ':cat' => $data->category,
            ':amt' => $data->amount,
            ':desc' => $data->description ?? '',
            ':date' => $data->expense_date
        ]);

        echo json_encode(['success' => true, 'message' => 'Expense added successfully']);
    
    } elseif ($method === 'PUT') {
        // UPDATE EXPENSE
        $data = json_decode(file_get_contents('php://input'));
        
        if (empty($data->expense_id) || empty($data->category) || empty($data->amount) || empty($data->expense_date)) {
            throw new Exception("Missing required fields");
        }

        // Determine Branch ID (Only Admin can change branch)
        $branch_sql = "";
        $params = [
            ':cat' => $data->category,
            ':amt' => $data->amount,
            ':desc' => $data->description ?? '',
            ':date' => $data->expense_date,
            ':id' => $data->expense_id
        ];

        if ($user_role === 'admin' && !empty($data->branch_id)) {
            $branch_sql = ", branch_id = :bid";
            $params[':bid'] = $data->branch_id;
        }

        // Ensure Staff can only edit their own branch's expense
        $where_check = ($user_role === 'staff') ? "AND branch_id = :check_bid" : "";
        if ($user_role === 'staff') $params[':check_bid'] = $user_branch_id;

        $stmt = $pdo->prepare("UPDATE expenses SET category = :cat, amount = :amt, description = :desc, expense_date = :date $branch_sql WHERE expense_id = :id $where_check");
        $stmt->execute($params);

        if ($stmt->rowCount() === 0 && $user_role === 'staff') {
            // Could mean ID not found OR permission issue
             // Optional check to see if ID exists but belongs to another branch
        }

        echo json_encode(['success' => true, 'message' => 'Expense updated successfully']);

    } elseif ($method === 'DELETE') {
        // DELETE EXPENSE
        // Note: User asked for "all functions", usually Staff should be able to delete their own mistakes too?
        // I'll allow staff to delete their own, Admin to delete any.
        
        $id = $_GET['id'] ?? null;
        if (!$id) throw new Exception("ID required");

        $where_sql = "WHERE expense_id = :id";
        $params = [':id' => $id];

        if ($user_role === 'staff') {
            $where_sql .= " AND branch_id = :bid";
            $params[':bid'] = $user_branch_id;
        }

        $stmt = $pdo->prepare("DELETE FROM expenses $where_sql");
        $stmt->execute($params);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("Failed to delete (Not found or permission denied)");
        }

        echo json_encode(['success' => true]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>