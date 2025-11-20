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
    // We join sale_items to products to get the cost *at the moment of query*
    // Note: In a perfect system, cost is snapshot at sale time, but this is standard for simple inventory.
    $sales_query = "
        SELECT 
            SUM(s.total_amount) as revenue,
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

    // 3. CHART DATA (Daily Revenue vs Expenses)
    // This query groups data by date for the chart
    $chart_query = "
        SELECT 
            DATE(date_col) as date,
            SUM(revenue) as revenue,
            SUM(expense) as expense
        FROM (
            SELECT DATE(sale_date) as date_col, total_amount as revenue, 0 as expense
            FROM sales WHERE DATE(sale_date) BETWEEN :start AND :end AND is_reversed = 0 $branch_filter_sql
            UNION ALL
            SELECT expense_date as date_col, 0 as revenue, amount as expense
            FROM expenses WHERE expense_date BETWEEN :start AND :end $branch_filter_sql
        ) combined
        GROUP BY DATE(date_col)
        ORDER BY date ASC
    ";

    // Execute Queries
    // A. Totals
    $stmt = $pdo->prepare($sales_query);
    // Need to fix params for sales query (uses s.branch_id, expenses uses branch_id)
    // We'll just bind by name, PDO handles reuse
    // Actually, for safety with different column names in raw SQL string concatenation above,
    // let's replace the generic SQL var with table specific alias in the SQL strings above.
    // Adjusted: $branch_filter_sql above is generic. 
    // Let's rewrite the execution slightly for safety.
    
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
    // The UNION query is tricky with params. We need to replace the string injection securely or bind carefully.
    // To simplify, we'll run separate chart queries and merge in PHP.
    
    // Chart: Sales
    $c_sales_sql = "SELECT DATE(sale_date) as d, SUM(total_amount) as val FROM sales s WHERE DATE(sale_date) BETWEEN :start AND :end AND is_reversed = 0 " . ($user_role==='staff' || $branch_id ? "AND s.branch_id = :branch" : "") . " GROUP BY d";
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