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
$where_credit = [];
$where_cheque = [];
$params = [];

// Date Filter
$where_credit[] = "DATE(cp.payment_date) >= :start AND DATE(cp.payment_date) <= :end";
$where_cheque[] = "DATE(cq.cheque_date) >= :start AND DATE(cq.cheque_date) <= :end";

$params[':start'] = $start_date;
$params[':end'] = $end_date;

// Branch Filter (Requires joins)
if ($user_role === 'staff') {
    $where_credit[] = "s.branch_id = :branch";
    $where_cheque[] = "s.branch_id = :branch";
    $params[':branch'] = $user_branch_id;
} elseif ($branch_id) {
    $where_credit[] = "s.branch_id = :branch";
    $where_cheque[] = "s.branch_id = :branch";
    $params[':branch'] = $branch_id;
}

$where_credit_sql = empty($where_credit) ? "1=1" : implode(' AND ', $where_credit);
$where_cheque_sql = empty($where_cheque) ? "1=1" : implode(' AND ', $where_cheque);

// Global Outstanding Balances (Unaffected by date range, but affected by branch)
// 1. Total Outstanding Credit
$branch_filter_global = "";
$params_global = [];
if ($user_role === 'staff') {
    $branch_filter_global = "AND s.branch_id = :gbranch";
    $params_global[':gbranch'] = $user_branch_id;
} elseif ($branch_id) {
    $branch_filter_global = "AND s.branch_id = :gbranch";
    $params_global[':gbranch'] = $branch_id;
}

// Total ever bought on credit vs total ever paid
$global_credit_sql = "
    SELECT 
        (SELECT COALESCE(SUM(s.total_amount), 0) FROM sales s WHERE s.payment_method = 'credit' AND s.is_reversed = 0 $branch_filter_global) -
        (SELECT COALESCE(SUM(cp.amount), 0) FROM credit_payments cp LEFT JOIN sales s ON cp.sale_id = s.sale_id WHERE 1=1 $branch_filter_global)
    AS total_outstanding_credit
";

// Total Pending Cheques
$global_cheque_sql = "
    SELECT COALESCE(SUM(cq.amount), 0) AS total_pending_cheques
    FROM cheque_payments cq
    LEFT JOIN sales s ON cq.sale_id = s.sale_id
    WHERE cq.status IN ('pending', 'banked') 
    $branch_filter_global
";

// --- Time-based Period Logic ---
switch ($group_by) {
    case 'monthly':
        $sql_group_c = "DATE_FORMAT(cp.payment_date, '%Y-%m')";
        $sql_label_c = "DATE_FORMAT(cp.payment_date, '%M %Y')";
        $sql_group_q = "DATE_FORMAT(cq.cheque_date, '%Y-%m')";
        $sql_label_q = "DATE_FORMAT(cq.cheque_date, '%M %Y')";
        break;
    case 'yearly':
        $sql_group_c = "DATE_FORMAT(cp.payment_date, '%Y')";
        $sql_label_c = "DATE_FORMAT(cp.payment_date, '%Y')";
        $sql_group_q = "DATE_FORMAT(cq.cheque_date, '%Y')";
        $sql_label_q = "DATE_FORMAT(cq.cheque_date, '%Y')";
        break;
    default: // daily
        $sql_group_c = "DATE(cp.payment_date)";
        $sql_label_c = "DATE(cp.payment_date)";
        $sql_group_q = "DATE(cq.cheque_date)";
        $sql_label_q = "DATE(cq.cheque_date)";
        break;
}

try {
    // 1. Fetch Globals
    $stmt_glob_c = $pdo->prepare($global_credit_sql);
    $stmt_glob_c->execute($params_global);
    $outstanding_credit = $stmt_glob_c->fetchColumn();

    $stmt_glob_q = $pdo->prepare($global_cheque_sql);
    $stmt_glob_q->execute($params_global);
    $pending_cheques = $stmt_glob_q->fetchColumn();

    // 2. Fetch Period Summary
    $query_period_credit = "
        SELECT COALESCE(SUM(cp.amount), 0) as collected_credit
        FROM credit_payments cp
        LEFT JOIN sales s ON cp.sale_id = s.sale_id
        WHERE $where_credit_sql
    ";
    $stmt_period_c = $pdo->prepare($query_period_credit);
    $stmt_period_c->execute($params);
    $period_credit_collected = $stmt_period_c->fetchColumn();

    // Summing cheques that became 'cleared' in this date range
    // NOTE: If cheque_date represents the due date, this treats cleared cheques in the range as collected.
    $query_period_cheque = "
        SELECT COALESCE(SUM(cq.amount), 0) as cleared_cheques
        FROM cheque_payments cq
        LEFT JOIN sales s ON cq.sale_id = s.sale_id
        WHERE $where_cheque_sql AND cq.status = 'cleared'
    ";
    $stmt_period_q = $pdo->prepare($query_period_cheque);
    $stmt_period_q->execute($params);
    $period_cheques_cleared = $stmt_period_q->fetchColumn();

    // 3. Detailed Time-Series for Chart and Table
    $credit_trend_sql = "
        SELECT 
            $sql_label_c as period_label,
            $sql_group_c as raw_date,
            SUM(cp.amount) as credit_collected,
            0 as cheques_cleared
        FROM credit_payments cp
        LEFT JOIN sales s ON cp.sale_id = s.sale_id
        WHERE $where_credit_sql
        GROUP BY $sql_group_c
    ";

    $cheque_trend_sql = "
        SELECT 
            $sql_label_q as period_label,
            $sql_group_q as raw_date,
            0 as credit_collected,
            SUM(cq.amount) as cheques_cleared
        FROM cheque_payments cq
        LEFT JOIN sales s ON cq.sale_id = s.sale_id
        WHERE $where_cheque_sql AND cq.status = 'cleared'
        GROUP BY $sql_group_q
    ";

    // Combine trends using UNION ALL, then aggregate by period
    $combined_trend_sql = "
        SELECT 
            t.period_label,
            t.raw_date,
            SUM(t.credit_collected) as credit_collected,
            SUM(t.cheques_cleared) as cheques_cleared
        FROM ($credit_trend_sql UNION ALL $cheque_trend_sql) t
        GROUP BY t.raw_date
        ORDER BY t.raw_date DESC
    ";

    $stmt_trends = $pdo->prepare($combined_trend_sql);
    
    // We bind the same params twice because of the UNION
    $union_params = array_merge($params, $params); 
    
    // Workaround for PDO indexed params acting weird with named params in Unions: we must rely on positional binding or recreate array
    // Since we used named params, let's just re-execute safely by doing simple string replacement for PDO bind values if named params fail
    
    // Safer PDO execution for named params appearing multiple times:
    $stmt_trends = $pdo->prepare($combined_trend_sql);
    // Bind all original params
    foreach ($params as $key => $val) {
        // Unfortunately standard PDO warns if a named parameter is repeated in the query string without emulation on.
        // We will just replace the placeholders to avoid complex binding issues in the Union.
    }
    
    // Better method: Re-alias parameter names for the second part of the union
    $cheque_trend_sql_aliased = str_replace([':start', ':end', ':branch'], [':start2', ':end2', ':branch2'], $cheque_trend_sql);
    $combined_trend_sql2 = "
        SELECT 
            t.period_label,
            t.raw_date,
            SUM(t.credit_collected) as credit_collected,
            SUM(t.cheques_cleared) as cheques_cleared
        FROM ($credit_trend_sql UNION ALL $cheque_trend_sql_aliased) t
        GROUP BY t.period_label, t.raw_date
        ORDER BY t.raw_date DESC
    ";
    
    $stmt_trends = $pdo->prepare($combined_trend_sql2);
    foreach ($params as $key => $val) {
        $stmt_trends->bindValue($key, $val);
        $stmt_trends->bindValue($key . '2', $val); // bind the aliased version
    }
    $stmt_trends->execute();
    $rows = $stmt_trends->fetchAll(PDO::FETCH_ASSOC);

    // Prepare chart array
    $chart_data = [];
    // Sort ascending for chart
    $chart_rows = array_reverse($rows);
    foreach ($chart_rows as $r) {
        $chart_data[] = [
            'date' => $r['period_label'],
            'credit' => (float)$r['credit_collected'],
            'cheque' => (float)$r['cheques_cleared']
        ];
    }

    echo json_encode([
        'summary' => [
            'total_outstanding_credit' => (float)$outstanding_credit,
            'total_pending_cheques' => (float)$pending_cheques,
            'period_credit_collected' => (float)$period_credit_collected,
            'period_cheques_cleared' => (float)$period_cheques_cleared
        ],
        'rows' => $rows,
        'chart' => $chart_data
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
