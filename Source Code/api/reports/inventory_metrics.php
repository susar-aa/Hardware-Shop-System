<?php
session_start();
include_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in'])) { http_response_code(401); exit; }

$user_role = $_SESSION['role'];
$user_branch_id = $_SESSION['branch_id'] ?? null;
$branch_id = $_GET['branch_id'] ?? null;
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Base Filters for Stock Table
$where = [];
$params = [];

// Branch Logic
if ($user_role === 'staff') {
    $where[] = "ps.branch_id = :branch";
    $params[':branch'] = $user_branch_id;
    // For sales subquery
    $sales_branch_sql = "AND s.branch_id = :branch";
} elseif ($branch_id) {
    $where[] = "ps.branch_id = :branch";
    $params[':branch'] = $branch_id;
    $sales_branch_sql = "AND s.branch_id = :branch";
} else {
    $sales_branch_sql = ""; // Admin looking at all branches
}

$where_sql = count($where) > 0 ? "WHERE " . implode(' AND ', $where) : "";

try {
    // 1. Inventory Rows with Sold Count Subquery
    // We join the main products table with a subquery that sums sales in the date range.
    
    $query = "SELECT 
                p.name, 
                p.product_code, 
                p.cost,
                ps.stock,
                (ps.stock * p.cost) as stock_value,
                p.reorder_level,
                b.branch_name,
                COALESCE(sold_data.period_sold, 0) as period_sold
              FROM product_stock ps
              JOIN products p ON ps.product_id = p.product_id
              LEFT JOIN branches b ON ps.branch_id = b.branch_id
              
              -- LEFT JOIN to get sales count for this product/branch in the date range
              LEFT JOIN (
                  SELECT 
                    si.product_id, 
                    s.branch_id, 
                    SUM(si.quantity) as period_sold
                  FROM sale_items si
                  JOIN sales s ON si.sale_id = s.sale_id
                  WHERE DATE(s.sale_date) >= :start_date 
                    AND DATE(s.sale_date) <= :end_date
                    AND s.is_reversed = 0
                    $sales_branch_sql
                  GROUP BY si.product_id, s.branch_id
              ) sold_data ON ps.product_id = sold_data.product_id 
                          AND (ps.branch_id = sold_data.branch_id OR sold_data.branch_id IS NULL)

              $where_sql
              ORDER BY p.name ASC";

    $stmt = $pdo->prepare($query);
    
    // Bind params
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':start_date', $start_date);
    $stmt->bindValue(':end_date', $end_date);
    
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Totals
    // We can calculate totals in PHP easily from the rows we just fetched to avoid another heavy query
    $summary = [
        'total_qty' => 0,
        'total_value' => 0,
        'period_items_sold' => 0,
        'low_stock_count' => 0
    ];

    foreach ($rows as $row) {
        $summary['total_qty'] += (int)$row['stock'];
        $summary['total_value'] += (float)$row['stock_value'];
        $summary['period_items_sold'] += (int)$row['period_sold'];
        if ($row['stock'] <= $row['reorder_level']) {
            $summary['low_stock_count']++;
        }
    }

    echo json_encode([
        'summary' => $summary,
        'rows' => $rows
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>