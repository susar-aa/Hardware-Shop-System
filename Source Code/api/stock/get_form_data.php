<?php
// This API fetches products and branches for the stock adjustment forms
session_start();
include_once '../../config/database.php';

// Security check: Ensure user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit;
}

$products = [];
$branches = [];

// --- 1. Fetch Products ---
try {
    // UPDATED: Include product_code and price, which are needed by billing.js
    $product_query = "SELECT product_id, product_code, name, price FROM products ORDER BY name ASC";
    $product_stmt = $pdo->prepare($product_query);
    $product_stmt->execute();
    $products = $product_stmt->fetchAll();
} catch (PDOException $e) {
    // Log error, but don't crash the whole script
    // error_log('Failed to fetch products: ' . $e->getMessage());
    // $products remains an empty array
}

// --- 2. Fetch Branches ---
try {
    $branch_query = "SELECT branch_id, branch_name FROM branches ORDER BY branch_name ASC";
    $branch_stmt = $pdo->prepare($branch_query);
    $branch_stmt->execute();
    $branches = $branch_stmt->fetchAll();
} catch (PDOException $e) {
    // This will likely fail if the 'branches' table doesn't exist.
    // We catch it and let $branches remain an empty array.
    // error_log('Failed to fetch branches: ' . $e->getMessage());
}

// --- 3. Return combined data ---
header('Content-Type: application/json');
echo json_encode([
    'products' => $products,
    'branches' => $branches
]);
?>