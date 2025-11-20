<?php
// --- DIAGNOSTIC CODE ---
// This forces PHP to display detailed error messages, which should replace the generic HTML error.
error_reporting(E_ALL);
ini_set('display_errors', 1);
// --- END DIAGNOSTIC CODE ---

// This API creates a new sale using the dedicated 'sales' and 'sale_items' tables.
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
if (empty($data->branch_id) || empty($data->items) || !is_array($data->items)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: branch_id and items array.']);
    exit;
}

// --- CRITICAL FIX: Ensure user_id is defined and safe ---
$user_id = $_SESSION['user_id'] ?? null;
if (empty($user_id)) {
    // This handles a session that is logged in but missing the user_id index
    http_response_code(401);
    echo json_encode(['error' => 'Session invalid or missing user ID. Please log in again.']);
    exit;
}
// --- END CRITICAL FIX ---


$branch_id = $data->branch_id;
$items = $data->items;
$notes = 'Sale from billing page';
$transaction_type = 'out';
$total_amount = 0;

try {
    $pdo->beginTransaction();

    // 1. Calculate the total sale amount and prepare product data for checks
    foreach ($items as $item) {
        // FIXED: Use null coalescing (??) to ensure price defaults to 0 if undefined.
        $item_price = (float)($item->price ?? 0);
        $total_amount += ((int)$item->quantity * $item_price);
    }
    
    // --- CRITICAL FIX: Format the total amount as a string for MySQL DECIMAL type ---
    // Use sprintf to guarantee a two-decimal-place string.
    $formatted_total_amount = sprintf('%0.2f', $total_amount);

    // 2. Insert Sale Header (Invoice)
    $sale_query = "INSERT INTO sales (branch_id, user_id, total_amount) VALUES (:branch_id, :user_id, :total_amount)";
    $sale_stmt = $pdo->prepare($sale_query);
    $sale_stmt->bindParam(':branch_id', $branch_id, PDO::PARAM_INT);
    $sale_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    // Bind the formatted string
    $sale_stmt->bindParam(':total_amount', $formatted_total_amount); 
    $sale_stmt->execute();
    $sale_id = $pdo->lastInsertId();

    // 3. Loop through items, check stock, update stock, and insert line item/log
    foreach ($items as $item) {
        $product_id = $item->product_id;
        $quantity_to_sell = (int)$item->quantity;
        
        // FIXED: Get price, then format it using sprintf
        $unit_price_float = (float)($item->price ?? 0); 
        $unit_price_formatted = sprintf('%0.2f', $unit_price_float); 
        
        if ($quantity_to_sell <= 0) {
            throw new Exception("Invalid quantity for a product.", 400);
        }

        // a. Check stock and lock row (using product_stock table)
        $stock_stmt = $pdo->prepare("SELECT stock_id, stock FROM product_stock WHERE product_id = :product_id AND branch_id = :branch_id FOR UPDATE");
        $stock_stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stock_stmt->bindParam(':branch_id', $branch_id, PDO::PARAM_INT);
        $stock_stmt->execute();
        $stock_row = $stock_stmt->fetch(PDO::FETCH_ASSOC);

        $current_stock = (int)($stock_row['stock'] ?? 0);
        $stock_id = $stock_row['stock_id'] ?? null;

        // Check if stock is sufficient
        if (!$stock_id || $current_stock < $quantity_to_sell) {
            // Need to fetch product name for good error message
            $name_stmt = $pdo->prepare("SELECT name FROM products WHERE product_id = :id");
            $name_stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
            $name_stmt->execute();
            $product_name = $name_stmt->fetchColumn() ?? "Product ID $product_id";

            throw new Exception("Insufficient stock for {$product_name}. Only {$current_stock} available.", 400);
        }

        // b. Insert Sale Line Item
        $item_query = "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price) VALUES (:sale_id, :product_id, :quantity, :unit_price)";
        $item_stmt = $pdo->prepare($item_query);
        $item_stmt->bindParam(':sale_id', $sale_id, PDO::PARAM_INT);
        $item_stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $item_stmt->bindParam(':quantity', $quantity_to_sell, PDO::PARAM_INT);
        // Bind the formatted string
        $item_stmt->bindParam(':unit_price', $unit_price_formatted); 
        $item_stmt->execute();

        // c. Decrement stock
        $update_stmt = $pdo->prepare("UPDATE product_stock SET stock = stock - :quantity WHERE stock_id = :stock_id");
        $update_stmt->bindParam(':quantity', $quantity_to_sell, PDO::PARAM_INT);
        $update_stmt->bindParam(':stock_id', $stock_id, PDO::PARAM_INT);
        $update_stmt->execute();

        // d. Log stock movement (in stock_transactions)
        $log_query = "INSERT INTO stock_transactions (product_id, branch_id, user_id, transaction_type, quantity, notes, sale_id)
                      VALUES (:product_id, :branch_id, :user_id, :transaction_type, :quantity, :notes, :sale_id)";
        $log_stmt = $pdo->prepare($log_query);
        $log_stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $log_stmt->bindParam(':branch_id', $branch_id, PDO::PARAM_INT);
        $log_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $log_stmt->bindParam(':transaction_type', $transaction_type);
        $log_stmt->bindParam(':quantity', $quantity_to_sell, PDO::PARAM_INT);
        $log_stmt->bindParam(':notes', $notes);
        $log_stmt->bindParam(':sale_id', $sale_id, PDO::PARAM_INT); 
        $log_stmt->execute();
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => "Sale recorded successfully! Invoice #{$sale_id}."]);

// --- CHANGED CATCHING ORDER FOR DIAGNOSTICS ---
} catch (PDOException $pdo_e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log detailed PDO error info
    $error_info = $pdo_e->errorInfo;
    $error_message = "SQLSTATE[{$error_info[0]}]: {$error_info[1]} {$error_info[2]}";

    $code = $pdo_e->getCode() >= 400 ? $pdo_e->getCode() : 500;
    http_response_code($code);
    echo json_encode(['error' => 'Database SQL Error: ' . $error_message]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Check if the error is related to a foreign key constraint (e.g., if a table is missing)
    if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1452) {
        $error_message = 'Foreign Key Constraint Error: A required ID (Product, Branch, User, or Sale) was not found in its respective table. Check the consistency of your data.';
    } else {
        $error_message = $e->getMessage();
    }

    $code = $e->getCode() >= 400 ? e->getCode() : 500;
    http_response_code($code);
    echo json_encode(['error' => 'General PHP Error: ' . $error_message]);
}