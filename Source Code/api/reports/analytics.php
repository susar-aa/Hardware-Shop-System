<?php
session_start();
include_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in'])) { http_response_code(401); exit; }

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$branch_id = $_GET['branch_id'] ?? null;
$user_role = $_SESSION['role'];
$user_branch = $_SESSION['branch_id'] ?? null;

// Security Filter
$branch_filter_sql = "";
$params = [':start' => $start_date, ':end' => $end_date];

if ($user_role === 'staff') {
    $branch_filter_sql = "AND branch_id = :branch";
    $params[':branch'] = $user_branch;
} elseif ($branch_id) {
    $branch_filter_sql = "AND branch_id = :branch";
    $params[':branch'] = $branch_id;
}

try {
    // 1. REVENUE & COGS (Cost of Goods Sold)
    // FIX: Revenue is calculated as SUM(si.quantity * si.unit_price) to avoid duplication from joins
    // COGS is calculated as SUM(si.quantity * p.cost)
    $sales_query = "
        SELECT 
            SUM(si.quantity * si.unit_price) as revenue,
            SUM(si.quantity * p.cost) as cogs
        FROM sales s
        JOIN sale_items si ON s.sale_id = si.sale_id
        JOIN products p ON si.product_id = p.product_id
        WHERE DATE(s.sale_date) BETWEEN :start AND :end
        AND s.is_reversed = 0
        $branch_filter_sql
    ";

    // 2. EXPENSES
    $expense_query = "
        SELECT SUM(amount) as total_expenses 
        FROM expenses 
        WHERE expense_date BETWEEN :start AND :end
        $branch_filter_sql
    ";

    // Execute Queries
    // A. Totals
    // RE-BINDING approach for clarity
    $sales_sql_final = str_replace("branch_id", "s.branch_id", $sales_query);
    $stmt_sales = $pdo->prepare($sales_sql_final);
    $stmt_sales->execute($params);
    $sales_data = $stmt_sales->fetch(PDO::FETCH_ASSOC);

    $expense_sql_final = str_replace("branch_id", "branch_id", $expense_query); // expenses table has branch_id
    $stmt_exp = $pdo->prepare($expense_sql_final);
    $stmt_exp->execute($params);
    $exp_data = $stmt_exp->fetch(PDO::FETCH_ASSOC);

    // B. Chart
    // Chart: Sales
    // FIX: Also fix the chart query to sum line items instead of header totals
    $c_sales_sql = "
        SELECT 
            DATE(s.sale_date) as d, 
            SUM(si.quantity * si.unit_price) as val 
        FROM sales s 
        JOIN sale_items si ON s.sale_id = si.sale_id
        WHERE DATE(s.sale_date) BETWEEN :start AND :end 
        AND s.is_reversed = 0 " . ($user_role==='staff' || $branch_id ? "AND s.branch_id = :branch" : "") . " 
        GROUP BY d";
        
    $stmt_cs = $pdo->prepare($c_sales_sql);
    $stmt_cs->execute($params);
    $sales_chart = $stmt_cs->fetchAll(PDO::FETCH_KEY_PAIR); // [date => val]

    // Chart: Expenses
    $c_exp_sql = "SELECT expense_date as d, SUM(amount) as val FROM expenses WHERE expense_date BETWEEN :start AND :end " . ($user_role==='staff' || $branch_id ? "AND branch_id = :branch" : "") . " GROUP BY d";
    $stmt_ce = $pdo->prepare($c_exp_sql);
    $stmt_ce->execute($params);
    $exp_chart = $stmt_ce->fetchAll(PDO::FETCH_KEY_PAIR);

    // Merge for Chart (Get all unique dates)
    $all_dates = array_unique(array_merge(array_keys($sales_chart), array_keys($exp_chart)));
    sort($all_dates);
    
    $chart_data = [];
    foreach($all_dates as $date) {
        $chart_data[] = [
            'date' => $date,
            'revenue' => $sales_chart[$date] ?? 0,
            'expense' => $exp_chart[$date] ?? 0
        ];
    }

    // Calculate Profit
    $revenue = (float)($sales_data['revenue'] ?? 0);
    $cogs = (float)($sales_data['cogs'] ?? 0);
    $expenses = (float)($exp_data['total_expenses'] ?? 0);
    $gross_profit = $revenue - $cogs;
    $net_profit = $gross_profit - $expenses;

    echo json_encode([
        'totals' => [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'expenses' => $expenses,
            'gross_profit' => $gross_profit,
            'net_profit' => $net_profit
        ],
        'chart' => $chart_data
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>