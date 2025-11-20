<?php
session_start();
include_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in'])) { http_response_code(401); exit; }

$user_role = $_SESSION['role'];
$user_branch_id = $_SESSION['branch_id'] ?? null;

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$branch_id = $_GET['branch_id'] ?? null;
$group_by = $_GET['group_by'] ?? 'daily'; // daily, monthly, yearly

// --- Build Filters ---
$where = ["s.is_reversed = 0"]; // Exclude reversed sales from revenue
$params = [];

// Date Filter
$where[] = "DATE(s.sale_date) >= :start";
$where[] = "DATE(s.sale_date) <= :end";
$params[':start'] = $start_date;
$params[':end'] = $end_date;

// Branch Filter
if ($user_role === 'staff') {
    $where[] = "s.branch_id = :branch";
    $params[':branch'] = $user_branch_id;
} elseif ($branch_id) {
    $where[] = "s.branch_id = :branch";
    $params[':branch'] = $branch_id;
}

$where_sql = "WHERE " . implode(' AND ', $where);

// --- Determine Grouping ---
switch ($group_by) {
    case 'monthly':
        $sql_group = "DATE_FORMAT(s.sale_date, '%Y-%m')";
        $sql_label = "DATE_FORMAT(s.sale_date, '%M %Y')";
        break;
    case 'yearly':
        $sql_group = "DATE_FORMAT(s.sale_date, '%Y')";
        $sql_label = "DATE_FORMAT(s.sale_date, '%Y')";
        break;
    default: // daily
        $sql_group = "DATE(s.sale_date)";
        $sql_label = "DATE(s.sale_date)";
        break;
}

// If admin views all branches, we might want to group by branch too?
// For simple reports, let's keep it aggregated by time, unless specifically drilled down.
// But to show 'Branch' column in 'All' view, we need to group by branch IF multiple branches are involved.
// To keep it simple for charts/tables: We will just group by Time.
// IF branch filter is empty (All branches), the table will show aggregated totals for ALL branches per day.

try {
    // 1. Detailed Rows (Grouped)
    $query = "SELECT 
                $sql_label as period_label,
                COUNT(DISTINCT s.sale_id) as transaction_count,
                SUM(si.quantity) as items_sold,
                SUM(s.total_amount) as revenue
                " . ($user_role === 'admin' && !$branch_id ? ", b.branch_name" : "") . "
              FROM sales s
              JOIN sale_items si ON s.sale_id = si.sale_id
              LEFT JOIN branches b ON s.branch_id = b.branch_id
              $where_sql
              GROUP BY $sql_group " . ($user_role === 'admin' && !$branch_id ? ", b.branch_id" : "") . "
              ORDER BY s.sale_date DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Summary Totals (Overall for the period)
    $query_summary = "SELECT 
                        COUNT(DISTINCT s.sale_id) as total_transactions,
                        SUM(si.quantity) as total_items,
                        SUM(s.total_amount) as total_revenue
                      FROM sales s
                      JOIN sale_items si ON s.sale_id = si.sale_id
                      $where_sql";
    
    $stmt_sum = $pdo->prepare($query_summary);
    $stmt_sum->execute($params);
    $summary = $stmt_sum->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'summary' => $summary,
        'rows' => $rows
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>