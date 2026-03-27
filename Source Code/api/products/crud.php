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
$can_write = ($_SESSION['role'] === 'admin'); 

/**
 * Helper: Generate Unique Product Code
 * Uses the first 4 letters of the name + a sequential number
 */
function generateProductCode($pdo, $name) {
    // Clean name to get alphanumeric prefix
    $clean = preg_replace('/[^A-Za-z0-9]/', '', $name);
    $prefix = strtoupper(substr($clean, 0, 4));
    
    // Fallbacks for very short names
    if (strlen($prefix) < 2) {
        $prefix = str_pad($prefix, 4, "PROD");
    } else if (strlen($prefix) < 4) {
        $prefix = str_pad($prefix, 4, "0");
    }

    // UPDATED: More robust search for the "highest" code
    // We sort by length first, then string value to ensure COOL10 comes after COOL9
    $stmt = $pdo->prepare("SELECT product_code FROM products WHERE product_code LIKE :prefix ORDER BY LENGTH(product_code) DESC, product_code DESC LIMIT 1");
    $stmt->execute([':prefix' => $prefix . '%']);
    $lastCode = $stmt->fetchColumn();

    $num = 1;
    if ($lastCode) {
        // Extract digits from the existing code
        $lastNum = (int)preg_replace('/[^0-9]/', '', $lastCode);
        $num = $lastNum + 1;
    }
    
    // NEW: Uniqueness Loop
    // This is a fail-safe. If the calculated code exists (e.g. it was hidden or sorting failed), 
    // we keep incrementing until we find a truly empty slot.
    $foundUnique = false;
    $finalCode = "";
    $attempts = 0;

    while (!$foundUnique && $attempts < 100) {
        $finalCode = $prefix . str_pad($num, 4, "0", STR_PAD_LEFT);
        
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE product_code = ?");
        $checkStmt->execute([$finalCode]);
        
        if ($checkStmt->fetchColumn() == 0) {
            $foundUnique = true;
        } else {
            $num++;
            $attempts++;
        }
    }
    
    return $finalCode;
}

try {
    switch ($method) {
        // --- READ (GET) ---
        case 'GET':
            // If exporting, fetch all without pagination
            $export = isset($_GET['export']) ? (bool)$_GET['export'] : false;
            
            // Pagination variables
            $page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 50;
            $offset = ($page - 1) * $limit;
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            
            $whereClause = "1=1";
            $params = [];
            
            if ($search !== '') {
                $whereClause .= " AND (p.name LIKE :search OR p.product_code LIKE :search)";
                $params[':search'] = "%$search%";
            }
            
            // Get Total Count for Pagination (Unless Exporting)
            $total_records = 0;
            if (!$export) {
                $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $whereClause");
                $count_stmt->execute($params);
                $total_records = (int)$count_stmt->fetchColumn();
            }

            // Fetch Products
            $query = "SELECT p.*, c.category_name 
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.category_id
                      WHERE $whereClause
                      ORDER BY p.name ASC";
            
            if (!$export) {
                $query .= " LIMIT $limit offsets $offset"; // Parameterized LIMIT is tricky in emulated prepares, inject safely since cast to int.
                // Wait, PDO syntax is LIMIT x OFFSET y
                $query = str_replace("offsets", "OFFSET", $query);
            }

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch Stock Data, BUT only for the products we just fetched to save massive memory
            if (count($products) > 0) {
                $productIds = array_column($products, 'product_id');
                $inQuery = implode(',', array_fill(0, count($productIds), '?'));
                
                $stock_query = "SELECT ps.product_id, b.branch_name, ps.stock 
                                FROM product_stock ps 
                                JOIN branches b ON ps.branch_id = b.branch_id
                                WHERE ps.product_id IN ($inQuery)";
                $stock_stmt = $pdo->prepare($stock_query);
                $stock_stmt->execute($productIds);
                $stocks = $stock_stmt->fetchAll(PDO::FETCH_ASSOC);

                $stockMap = [];
                foreach ($stocks as $s) {
                    $stockMap[$s['product_id']][$s['branch_name']] = $s['stock'];
                }

                foreach ($products as &$p) {
                    $p['stock_data'] = $stockMap[$p['product_id']] ?? [];
                }
            }

            if ($export) {
                // Return flat array for backwards compatibility with export script
                echo json_encode($products);
            } else {
                // Return paginated payload
                $total_pages = $limit > 0 ? ceil($total_records / $limit) : 1;
                echo json_encode([
                    'data' => $products,
                    'total' => $total_records,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => $total_pages
                ]);
            }
            break;

        // --- CREATE (POST) ---
        case 'POST':
            if (!$can_write) {
                throw new Exception('Permission denied', 403);
            }

            $data = json_decode(file_get_contents('php://input'));
            
            if (empty($data->name)) {
                throw new Exception('Product name is required', 400);
            }

            $is_visible = isset($data->is_visible) ? (int)$data->is_visible : 1;
            
            // NEW: Auto-generate code if left empty by user
            $product_code = trim($data->product_code ?? '');
            if (empty($product_code)) {
                $product_code = generateProductCode($pdo, $data->name);
            }

            // Final check to prevent DB errors even if auto-gen fails
            $dupCheck = $pdo->prepare("SELECT name FROM products WHERE product_code = ?");
            $dupCheck->execute([$product_code]);
            if ($existingName = $dupCheck->fetchColumn()) {
                throw new Exception("Conflict: Product code '$product_code' is already assigned to '$existingName'. Please provide a unique code or leave it blank to auto-generate.");
            }

            $query = "INSERT INTO products (category_id, product_code, name, description, price, cost, image, reorder_level, is_visible) 
                      VALUES (:category_id, :product_code, :name, :description, :price, :cost, :image, :reorder_level, :is_visible)";
            $stmt = $pdo->prepare($query);
            
            $stmt->execute([
                ':category_id' => $data->category_id,
                ':product_code' => $product_code,
                ':name' => $data->name,
                ':description' => $data->description,
                ':price' => $data->price,
                ':cost' => $data->cost,
                ':image' => $data->image,
                ':reorder_level' => $data->reorder_level,
                ':is_visible' => $is_visible
            ]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Product created successfully.',
                'generated_code' => $product_code
            ]);
            break;

        // --- UPDATE (PUT) ---
        case 'PUT':
            if (!$can_write) {
                throw new Exception('Permission denied', 403);
            }

            $data = json_decode(file_get_contents('php://input'));

            if (empty($data->product_id) || empty($data->name)) {
                throw new Exception('Product ID and name are required', 400);
            }

            $is_visible = isset($data->is_visible) ? (int)$data->is_visible : 1;
            $product_code = trim($data->product_code ?? '');

            // Update logic: If code is provided, update it. If left empty, keep existing.
            $query = "UPDATE products SET
                        category_id = :category_id,
                        product_code = COALESCE(NULLIF(:product_code, ''), product_code),
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
                ':product_code' => $product_code,
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
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed.']);
            break;
    }
} catch (Exception $e) {
    // Catch Duplicate Key errors and turn them into user-friendly messages
    if (strpos($e->getMessage(), '1062') !== false) {
        $code = 409;
        $msg = "A product with this code already exists. Please check hidden products or try a different code.";
    } else {
        $code = $e->getCode() >= 400 ? $e->getCode() : 500;
        $msg = $e->getMessage();
    }
    http_response_code($code);
    echo json_encode(['error' => $msg]);
}
?>