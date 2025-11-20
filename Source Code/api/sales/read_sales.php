<?php
// This API fetches sale headers with filtering based on user role and date.
session_start();
include_once '../../config/database.php';
header('Content-Type: application/json');

// Security check: Ensure user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit;
}

$user_role = $_SESSION['role'];
// CRITICAL FIX: The branch_id MUST be fetched from the user's session data
$user_branch_id = $_SESSION['branch_id'] ?? null; 

// Get filters from query string
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;
$filter_branch_id = $_GET['branch_id'] ?? null;

$where_clauses = [];
$bindings = [];

try {
    // --- Role-Based Filtering ---
    if ($user_role === 'staff' && $user_branch_id) {
        // Staff: Lock to their assigned branch
        $where_clauses[] = "s.branch_id = :user_branch_id";
        $bindings[':user_branch_id'] = $user_branch_id;
    } elseif ($user_role === 'admin' && $filter_branch_id) {
        // Admin: Filter by selected branch (if provided)
        $where_clauses[] = "s.branch_id = :filter_branch_id";
        $bindings[':filter_branch_id'] = $filter_branch_id;
    }
    
    // --- Date Filtering ---
    if ($start_date) {
        $where_clauses[] = "DATE(s.sale_date) >= :start_date";
        $bindings[':start_date'] = $start_date;
    }
    if ($end_date) {
        $where_clauses[] = "DATE(s.sale_date) <= :end_date";
        $bindings[':end_date'] = $end_date;
    }

    $where_sql = count($where_clauses) > 0 ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

    // --- Main Query ---
    // We query the 'sales' header table and aggregate total items sold for display.
    $query = "SELECT 
                s.sale_id, 
                s.sale_date, 
                s.total_amount, 
                s.is_reversed,
                s.reversal_user_id,
                b.branch_name,
                u.name AS user_name,
                -- Get total items sold for display
                SUM(si.quantity) AS total_items_sold
              FROM 
                sales s
              LEFT JOIN
                sale_items si ON s.sale_id = si.sale_id
              LEFT JOIN 
                branches b ON s.branch_id = b.branch_id
              LEFT JOIN
                users u ON s.user_id = u.user_id
              {$where_sql}
              GROUP BY s.sale_id
              ORDER BY s.sale_date DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($bindings);
    $sales = $stmt->fetchAll();

    echo json_encode($sales);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>