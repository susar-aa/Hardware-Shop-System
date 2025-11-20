<?php
session_start();
include_once '../../config/database.php';
header('Content-Type: application/json');

// Security check: Ensure user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$role = $_SESSION['role'] ?? 'staff'; 

try {
    switch ($method) {
        // --- READ (GET) ---
        case 'GET':
            $query = "SELECT category_id, category_name FROM categories ORDER BY category_name ASC";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($categories);
            break;

        // --- CREATE (POST) ---
        case 'POST':
            if ($role !== 'admin') { http_response_code(403); echo json_encode(['error' => 'Permission denied.']); exit; }

            $data = json_decode(file_get_contents('php://input'));
            if (empty($data->category_name)) {
                http_response_code(400);
                echo json_encode(['error' => 'Category name is required.']);
                exit;
            }

            $query = "INSERT INTO categories (category_name) VALUES (:category_name)";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':category_name', $data->category_name);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Category created.']);
            break;

        // --- UPDATE (PUT) ---
        case 'PUT':
            if ($role !== 'admin') { http_response_code(403); echo json_encode(['error' => 'Permission denied.']); exit; }

            $data = json_decode(file_get_contents('php://input'));
            if (empty($data->category_id) || empty($data->category_name)) {
                http_response_code(400);
                echo json_encode(['error' => 'Category ID and Name are required.']);
                exit;
            }

            $query = "UPDATE categories SET category_name = :name WHERE category_id = :id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':name', $data->category_name);
            $stmt->bindParam(':id', $data->category_id, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Category updated.']);
            break;

        // --- DELETE (DELETE) ---
        case 'DELETE':
            if ($role !== 'admin') { http_response_code(403); echo json_encode(['error' => 'Permission denied.']); exit; }

            $id = $_GET['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Category ID required.']);
                exit;
            }

            // Check if used in products
            $check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = :id");
            $check->execute([':id' => $id]);
            if ($check->fetchColumn() > 0) {
                http_response_code(409); // Conflict
                echo json_encode(['error' => 'Cannot delete: Category is used by products.']);
                exit;
            }

            $query = "DELETE FROM categories WHERE category_id = :id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Category deleted.']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed.']);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>