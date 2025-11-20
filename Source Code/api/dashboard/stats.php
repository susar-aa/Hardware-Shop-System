<?php
session_start();
include_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_role = $_SESSION['role'];
$user_branch_id = $_SESSION['branch_id'] ?? null;

// Filters
// FIXED: Added 's.' prefix to avoid ambiguity in joins
$where_clauses = ["s.is_reversed = 0"];
$params = [];

// Role-based Branch Logic
if ($user_role === 'staff') {
    $where_clauses[] = "s.branch_id = :branch_id";
    $params[':branch_id'] = $user_branch_id;
} elseif (!empty($_GET['branch_id'])) {
    $where_clauses[] = "s.branch_id = :branch_id";
    $params[':branch_id'] = $_GET['branch_id'];
}

$where_sql = "WHERE " . implode(' AND ', $where_clauses);

try {
    // 1. Sales Summary Cards
    // FIXED: Added alias 's' to table sales
    $summary_query = "
        SELECT 
            SUM(CASE WHEN DATE(s.sale_date) = CURDATE() THEN s.total_amount ELSE 0 END) as sales_today,
            SUM(CASE WHEN DATE(s.sale_date) = CURDATE() - INTERVAL 1 DAY THEN s.total_amount ELSE 0 END) as sales_yesterday,
            SUM(CASE WHEN YEARWEEK(s.sale_date, 1) = YEARWEEK(CURDATE(), 1) THEN s.total_amount ELSE 0 END) as sales_week,
            SUM(CASE WHEN DATE_FORMAT(s.sale_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') THEN s.total_amount ELSE 0 END) as sales_month
        FROM sales s
        $where_sql
    ";
    
    $stmt = $pdo->prepare($summary_query);
    $stmt->execute($params);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Chart Data (Last 7 Days)
    // FIXED: Added alias 's' to table sales
    $chart_query = "
        SELECT DATE(s.sale_date) as date, SUM(s.total_amount) as total
        FROM sales s
        $where_sql AND s.sale_date >= DATE(NOW()) - INTERVAL 7 DAY
        GROUP BY DATE(s.sale_date)
        ORDER BY date ASC
    ";
    $stmt_chart = $pdo->prepare($chart_query);
    $stmt_chart->execute($params);
    $chart_data = $stmt_chart->fetchAll(PDO::FETCH_KEY_PAIR);

    $final_chart = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $final_chart[] = [
            'date' => date('M d', strtotime($d)),
            'total' => $chart_data[$d] ?? 0
        ];
    }

    // 3. Recent Transactions
    // (Already had alias 's', but now $where_sql uses 's.branch_id' so it works correctly)
    $recent_query = "
        SELECT s.sale_id, s.sale_date, s.total_amount, u.name as user_name
        FROM sales s
        LEFT JOIN users u ON s.user_id = u.user_id
        $where_sql
        ORDER BY s.sale_date DESC
        LIMIT 5
    ";
    $stmt_recent = $pdo->prepare($recent_query);
    $stmt_recent->execute($params);
    $recent_sales = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);

    // 4. Low Stock Count
    // This query is independent of the main sales 's' alias, so we handle filters separately or reuse logic if applicable.
    // The stock table needs its own filtering logic for branch.
    
    $stock_where = [];
    $stock_params = [];
    
    if ($user_role === 'staff') {
        $stock_where[] = "ps.branch_id = :branch_id";
        $stock_params[':branch_id'] = $user_branch_id;
    } elseif (!empty($_GET['branch_id'])) {
        $stock_where[] = "ps.branch_id = :branch_id";
        $stock_params[':branch_id'] = $_GET['branch_id'];
    }
    
    $stock_where_sql = count($stock_where) > 0 ? "AND " . implode(' AND ', $stock_where) : "";

    $stock_query = "
        SELECT COUNT(*) as count
        FROM product_stock ps
        JOIN products p ON ps.product_id = p.product_id
        WHERE ps.stock <= p.reorder_level $stock_where_sql
    ";
    $stmt_stock = $pdo->prepare($stock_query);
    $stmt_stock->execute($stock_params);
    $low_stock = $stmt_stock->fetch(PDO::FETCH_ASSOC)['count'];

    echo json_encode([
        'cards' => $summary,
        'chart' => $final_chart,
        'recent' => $recent_sales,
        'low_stock' => $low_stock
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>