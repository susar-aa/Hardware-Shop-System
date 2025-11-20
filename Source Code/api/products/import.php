<?php
session_start();
include_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'CSV file upload failed.']);
    exit;
}

$file = $_FILES['csv_file']['tmp_name'];
$handle = fopen($file, "r");

if ($handle === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not open file.']);
    exit;
}

$header = fgetcsv($handle); // Skip header row
// Expected Order: Name, Code, CategoryID, Price, Cost, Reorder, Description, Image, Visible(1/0)

$updated = 0;
$inserted = 0;

try {
    $pdo->beginTransaction();

    $check_stmt = $pdo->prepare("SELECT product_id FROM products WHERE product_code = :code");
    
    $insert_stmt = $pdo->prepare("INSERT INTO products (name, product_code, category_id, price, cost, reorder_level, description, image, is_visible) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $update_stmt = $pdo->prepare("UPDATE products SET name=?, category_id=?, price=?, cost=?, reorder_level=?, description=?, image=?, is_visible=? WHERE product_id=?");

    while (($row = fgetcsv($handle)) !== false) {
        // Basic validation: Name is required
        if (empty($row[0])) continue;

        $name = $row[0];
        $code = $row[1] ?? '';
        $cat_id = !empty($row[2]) ? (int)$row[2] : null;
        $price = !empty($row[3]) ? (float)$row[3] : 0;
        $cost = !empty($row[4]) ? (float)$row[4] : 0;
        $reorder = !empty($row[5]) ? (int)$row[5] : 5;
        $desc = $row[6] ?? '';
        $img = $row[7] ?? '';
        $visible = isset($row[8]) ? (int)$row[8] : 1;

        $product_id = null;

        // Check if exists by code
        if (!empty($code)) {
            $check_stmt->execute([':code' => $code]);
            $product_id = $check_stmt->fetchColumn();
        }

        if ($product_id) {
            // Update
            $update_stmt->execute([$name, $cat_id, $price, $cost, $reorder, $desc, $img, $visible, $product_id]);
            $updated++;
        } else {
            // Insert
            $insert_stmt->execute([$name, $code, $cat_id, $price, $cost, $reorder, $desc, $img, $visible]);
            $inserted++;
        }
    }

    $pdo->commit();
    fclose($handle);
    echo json_encode(['success' => true, 'message' => "Import complete. Inserted: $inserted, Updated: $updated."]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fclose($handle);
    http_response_code(500);
    echo json_encode(['error' => 'Import failed: ' . $e->getMessage()]);
}
?>