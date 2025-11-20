<?php
session_start();
include_once '../../config/database.php';
header('Content-Type: application/json');

// Security check: Must be a logged-in Admin to manage branches
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        // --- READ (GET) ---
        case 'GET':
            $branch_id = $_GET['id'] ?? null;
            $query = "SELECT * FROM branches";
            
            if ($branch_id) {
                // If ID is provided (used by main.js for header), fetch specific branch
                $query .= " WHERE branch_id = :branch_id";
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':branch_id', $branch_id, PDO::PARAM_INT);
            } else {
                // Fetch all branches (used by branches.php)
                $query .= " ORDER BY branch_name ASC";
                $stmt = $pdo->prepare($query);
            }
            
            $stmt->execute();
            $branches = $stmt->fetchAll();
            echo json_encode($branches);
            break;

        // --- CREATE (POST) ---
        case 'POST':
            $data = json_decode(file_get_contents('php://input'));
            if (empty($data->branch_name)) {
                http_response_code(400);
                echo json_encode(['error' => 'Branch name is required.']);
                exit;
            }
            $query = "INSERT INTO branches (branch_name) VALUES (:branch_name)";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':branch_name', $data->branch_name);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Branch created.']);
            break;

        // --- UPDATE (PUT) ---
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'));
            if (empty($data->branch_name) || empty($data->branch_id)) {
                http_response_code(400);
                echo json_encode(['error' => 'Branch name and ID are required.']);
                exit;
            }
            $query = "UPDATE branches SET branch_name = :branch_name WHERE branch_id = :branch_id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':branch_name', $data->branch_name);
            $stmt->bindParam(':branch_id', $data->branch_id, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Branch updated.']);
            break;

        // --- DELETE ---
        case 'DELETE':
            if (empty($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Branch ID is required.']);
                exit;
            }
            $branch_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            
            // Use transaction to delete from stock and branches
            $pdo->beginTransaction();

            // 1. Delete associated stock records
            $stock_query = "DELETE FROM product_stock WHERE branch_id = :branch_id";
            $stock_stmt = $pdo->prepare($stock_query);
            $stock_stmt->bindParam(':branch_id', $branch_id, PDO::PARAM_INT);
            $stock_stmt->execute();

            // 2. Delete the branch
            $branch_query = "DELETE FROM branches WHERE branch_id = :branch_id";
            $branch_stmt = $pdo->prepare($branch_query);
            $branch_stmt->bindParam(':branch_id', $branch_id, PDO::PARAM_INT);
            $branch_stmt->execute();

            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => 'Branch and associated stock deleted.']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed.']);
            break;
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>