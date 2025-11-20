<?php
session_start();
include_once '../../config/database.php';
header('Content-Type: application/json');

// Security check: Ensure user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$can_write = ($_SESSION['role'] === 'admin'); // Only admin can create, update, delete

try {
    switch ($method) {
        // --- READ (GET) ---
        case 'GET':
            // Admin Panel needs to see ALL products, even hidden ones, so they can be managed.
            $query = "SELECT p.*, c.category_name 
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.category_id
                      ORDER BY p.name ASC";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            echo json_encode($stmt->fetchAll());
            break;

        // --- CREATE (POST) ---
        case 'POST':
            if (!$can_write) {
                throw new Exception('Permission denied', 403);
            }

            $data = json_decode(file_get_contents('php://input'));
            
            // Basic validation
            if (empty($data->name)) {
                throw new Exception('Product name is required', 400);
            }

            // Handle is_visible (Default to 1/True if missing)
            $is_visible = isset($data->is_visible) ? (int)$data->is_visible : 1;

            $query = "INSERT INTO products (category_id, product_code, name, description, price, cost, image, reorder_level, is_visible) 
                      VALUES (:category_id, :product_code, :name, :description, :price, :cost, :image, :reorder_level, :is_visible)";
            $stmt = $pdo->prepare($query);
            
            $stmt->execute([
                ':category_id' => $data->category_id,
                ':product_code' => $data->product_code,
                ':name' => $data->name,
                ':description' => $data->description,
                ':price' => $data->price,
                ':cost' => $data->cost,
                ':image' => $data->image,
                ':reorder_level' => $data->reorder_level,
                ':is_visible' => $is_visible
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Product created successfully.']);
            break;

        // --- UPDATE (PUT) ---
        case 'PUT':
            if (!$can_write) {
                throw new Exception('Permission denied', 403);
            }

            $data = json_decode(file_get_contents('php://input'));

            // Basic validation
            if (empty($data->product_id) || empty($data->name)) {
                throw new Exception('Product ID and name are required', 400);
            }

            // Handle is_visible
            $is_visible = isset($data->is_visible) ? (int)$data->is_visible : 1;

            $query = "UPDATE products SET
                        category_id = :category_id,
                        product_code = :product_code,
                        name = :name,
                        description = :description,
                        price = :price,
                        cost = :cost,
                        image = :image,
                        reorder_level = :reorder_level,
                        is_visible = :is_visible
                      WHERE product_id = :product_id";
            $stmt = $pdo->prepare($query);

            $stmt->execute([
                ':category_id' => $data->category_id,
                ':product_code' => $data->product_code,
                ':name' => $data->name,
                ':description' => $data->description,
                ':price' => $data->price,
                ':cost' => $data->cost,
                ':image' => $data->image,
                ':reorder_level' => $data->reorder_level,
                ':is_visible' => $is_visible,
                ':product_id' => $data->product_id
            ]);

            echo json_encode(['success' => true, 'message' => 'Product updated successfully.']);
            break;

        // --- DELETE (DELETE) ---
        case 'DELETE':
            if (!$can_write) {
                throw new Exception('Permission denied', 403);
            }

            // Get product_id from query string (e.g., .../crud.php?id=123)
            if (empty($_GET['id'])) {
                throw new Exception('Product ID is required', 400);
            }
            $product_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

            $query = "DELETE FROM products WHERE product_id = :product_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':product_id' => $product_id]);

            echo json_encode(['success' => true, 'message' => 'Product deleted successfully.']);
            break;

        default:
            http_response_code(405); // Method Not Allowed
            echo json_encode(['error' => 'Method not allowed.']);
            break;
    }
} catch (Exception $e) {
    // Set appropriate error code
    $code = $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($code);
    echo json_encode(['error' => $e->getMessage()]);
}
?>