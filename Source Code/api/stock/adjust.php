<?php
// This API handles both Stock In and Stock Out adjustments
session_start();
include_once '../../config/database.php';
header('Content-Type: application/json');

// Security check: Ensure user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit;
}

// Check for POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed.']);
    exit;
}

// Get data from request body
$data = json_decode(file_get_contents('php://input'));

// --- Basic Validation ---
// Note: branch_id might not be sent by staff if UI is disabled, but we handle it below
if (empty($data->product_id) || empty($data->quantity) || empty($data->type)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: product, quantity, and type.']);
    exit;
}

if ($data->quantity <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Quantity must be greater than zero.']);
    exit;
}

if (!in_array($data->type, ['in', 'out'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid transaction type.']);
    exit;
}

// --- Assign variables ---
$product_id = $data->product_id;
$quantity = $data->quantity;
$transaction_type = $data->type;
$notes = $data->notes ?? null;

// FIXED: Get user_id *only* from the session.
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    http_response_code(401);
    echo json_encode(['error' => 'Session invalid. Please log in again.']);
    exit;
}

// --- ROLE-BASED BRANCH SELECTION ---
$user_role = $_SESSION['role'] ?? 'staff';

if ($user_role === 'staff') {
    // STAFF: Force use of their assigned branch from session
    if (empty($_SESSION['branch_id'])) {
        http_response_code(403); // Forbidden
        echo json_encode(['error' => 'You are not assigned to a branch. Contact admin.']);
        exit;
    }
    $branch_id = $_SESSION['branch_id'];
} else {
    // ADMIN: Must provide a branch_id
    if (empty($data->branch_id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Branch ID is required for Admin adjustments.']);
        exit;
    }
    $branch_id = $data->branch_id;
}
// --- END ROLE CHECK ---

// --- Database Transaction ---
try {
    $pdo->beginTransaction();

    // 1. Check current stock and lock the row for update
    $stock_check_query = "SELECT stock_id, stock FROM product_stock WHERE product_id = :product_id AND branch_id = :branch_id FOR UPDATE";
    $stock_stmt = $pdo->prepare($stock_check_query);
    $stock_stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stock_stmt->bindParam(':branch_id', $branch_id, PDO::PARAM_INT);
    $stock_stmt->execute();
    $current_stock_row = $stock_stmt->fetch(PDO::FETCH_ASSOC);

    $current_stock = 0;
    $stock_id = null;
    if ($current_stock_row) {
        $current_stock = (int)$current_stock_row['stock'];
        $stock_id = $current_stock_row['stock_id'];
    }

    // 2. Process transaction based on type
    if ($transaction_type === 'in') {
        // --- Stock In ---
        if ($stock_id) {
            // Record exists, UPDATE it
            $update_stock_query = "UPDATE product_stock SET stock = stock + :quantity WHERE stock_id = :stock_id";
            $update_stmt = $pdo->prepare($update_stock_query);
            $update_stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            $update_stmt->bindParam(':stock_id', $stock_id, PDO::PARAM_INT);
            $update_stmt->execute();
        } else {
            // No record, INSERT it
            $insert_stock_query = "INSERT INTO product_stock (product_id, branch_id, stock) VALUES (:product_id, :branch_id, :quantity)";
            $insert_stmt = $pdo->prepare($insert_stock_query);
            $insert_stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $insert_stmt->bindParam(':branch_id', $branch_id, PDO::PARAM_INT);
            $insert_stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            $insert_stmt->execute();
        }
    } else {
        // --- Stock Out ---
        if (!$stock_id || $current_stock < $quantity) {
            throw new Exception("Insufficient stock. Only {$current_stock} available.", 400);
        }
        
        $update_stock_query = "UPDATE product_stock SET stock = stock - :quantity WHERE stock_id = :stock_id";
        $update_stmt = $pdo->prepare($update_stock_query);
        $update_stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $update_stmt->bindParam(':stock_id', $stock_id, PDO::PARAM_INT);
        $update_stmt->execute();
    }

    // 3. Log the transaction
    $log_query = "INSERT INTO stock_transactions (product_id, branch_id, user_id, transaction_type, quantity, notes)
                  VALUES (:product_id, :branch_id, :user_id, :transaction_type, :quantity, :notes)";
    $log_stmt = $pdo->prepare($log_query);
    $log_stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $log_stmt->bindParam(':branch_id', $branch_id, PDO::PARAM_INT);
    $log_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $log_stmt->bindParam(':transaction_type', $transaction_type);
    $log_stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
    $log_stmt->bindParam(':notes', $notes);
    $log_stmt->execute();
    
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Stock updated successfully.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $code = $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($code);
    echo json_encode(['error' => $e->getMessage()]);
}
?>