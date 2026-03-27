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

// Global state for the import batch to prevent collisions within the same file
$generated_codes_this_batch = [];

/**
 * Robust Product Code Generator
 */
function generateProductCodeImport($pdo, $name, &$batch_tracker) {
    // 1. Generate Prefix
    $clean = preg_replace('/[^A-Za-z0-9]/', '', $name);
    $prefix = strtoupper(substr($clean, 0, 4));
    if (strlen($prefix) < 2) $prefix = str_pad($prefix, 4, "PROD");
    else if (strlen($prefix) < 4) $prefix = str_pad($prefix, 4, "0");

    // 2. Find highest existing numeric suffix in DB
    // IMPORTANT: Sort by LENGTH first so COOL10 comes after COOL9
    $stmt = $pdo->prepare("SELECT product_code FROM products WHERE product_code LIKE :prefix ORDER BY LENGTH(product_code) DESC, product_code DESC LIMIT 1");
    $stmt->execute([':prefix' => $prefix . '%']);
    $lastCode = $stmt->fetchColumn();

    $num = 1;
    if ($lastCode) {
        $lastNum = (int)preg_replace('/[^0-9]/', '', $lastCode);
        $num = $lastNum + 1;
    }
    
    // 3. Uniqueness Loop (Fail-safe)
    $foundUnique = false;
    $finalCode = "";
    $attempts = 0;

    while (!$foundUnique && $attempts < 200) {
        $candidate = $prefix . str_pad($num, 4, "0", STR_PAD_LEFT);
        
        // Check 1: Is it in the Database (even hidden ones)?
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE product_code = ?");
        $checkStmt->execute([$candidate]);
        $existsInDb = ($checkStmt->fetchColumn() > 0);

        // Check 2: Have we already used it in this specific import batch?
        $existsInBatch = in_array($candidate, $batch_tracker);

        if (!$existsInDb && !$existsInBatch) {
            $finalCode = $candidate;
            $batch_tracker[] = $candidate; // Mark as used
            $foundUnique = true;
        } else {
            $num++;
            $attempts++;
        }
    }
    
    return $finalCode;
}

$debug_log = [];
$header = fgetcsv($handle); 

if (isset($header[0])) {
    $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);
}

$updated = 0;
$inserted = 0;
$stock_updated = 0;

try {
    $pdo->beginTransaction();

    // Load Categories and Branches
    $catMap = [];
    foreach ($pdo->query("SELECT category_id, category_name FROM categories")->fetchAll() as $cat) {
        $catMap[strtolower(trim($cat['category_name']))] = $cat['category_id'];
    }

    $branchMap = [];
    foreach ($pdo->query("SELECT branch_id, branch_name FROM branches")->fetchAll() as $br) {
        $branchMap[strtolower(trim($br['branch_name']))] = $br['branch_id'];
    }

    // Header Mapping
    $colIndex = [];
    $isHeaderSplit = false;
    foreach ($header as $idx => $colName) {
        $cleanCol = strtolower(trim($colName));
        if (strpos($cleanCol, 'is visible (1=yes') !== false && strpos($cleanCol, '0=no)') === false) $isHeaderSplit = true;
        $storageIdx = ($isHeaderSplit && $idx > 8) ? $idx - 1 : $idx;
        $colIndex[$cleanCol] = $storageIdx;
    }

    // Statements
    $stmt_by_code = $pdo->prepare("SELECT product_id FROM products WHERE product_code = :code");
    $stmt_by_name = $pdo->prepare("SELECT product_id FROM products WHERE name = :name");
    $insert_stmt = $pdo->prepare("INSERT INTO products (name, product_code, category_id, price, cost, reorder_level, description, image, is_visible) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $update_stmt = $pdo->prepare("UPDATE products SET name=?, category_id=?, price=?, cost=?, reorder_level=?, description=?, image=?, is_visible=?, product_code=? WHERE product_id=?");

    $check_stock_stmt = $pdo->prepare("SELECT stock_id FROM product_stock WHERE product_id = :pid AND branch_id = :bid");
    $update_stock_stmt = $pdo->prepare("UPDATE product_stock SET stock = :stock WHERE stock_id = :sid");
    $insert_stock_stmt = $pdo->prepare("INSERT INTO product_stock (product_id, branch_id, stock) VALUES (:pid, :bid, :stock)");

    $rowCount = 0;
    while (($row = fgetcsv($handle)) !== false) {
        $rowCount++;
        $nameIdx = $colIndex['name'] ?? 0;
        if (empty($row[$nameIdx])) continue;

        $name = trim($row[$nameIdx]);
        $code = trim($row[$colIndex['code'] ?? 1] ?? '');
        $cat_name = trim($row[$colIndex['category name'] ?? 2] ?? '');
        $price = (float)($row[$colIndex['price'] ?? 3] ?? 0);
        $cost = (float)($row[$colIndex['cost'] ?? 4] ?? 0);
        $reorder = (int)($row[$colIndex['reorder level'] ?? 5] ?? 5);
        $desc = $row[$colIndex['description'] ?? 6] ?? '';
        $img = $row[$colIndex['image url'] ?? 7] ?? '';
        $visIdx = $colIndex['is visible (1=yes, 0=no)'] ?? ($colIndex['is visible (1=yes'] ?? 8);
        $visible = isset($row[$visIdx]) ? (int)$row[$visIdx] : 1;

        $cat_id = $catMap[strtolower($cat_name)] ?? null;
        $product_id = null;

        // 1. Identify Existing Product
        if (!empty($code)) {
            $stmt_by_code->execute([':code' => $code]);
            $product_id = $stmt_by_code->fetchColumn();
        }
        if (!$product_id) {
            $stmt_by_name->execute([':name' => $name]);
            $product_id = $stmt_by_name->fetchColumn();
        }

        // 2. Save / Update
        if ($product_id) {
            $existing_code = $pdo->query("SELECT product_code FROM products WHERE product_id = $product_id")->fetchColumn();
            $finalCode = (!empty($code)) ? $code : $existing_code;
            $update_stmt->execute([$name, $cat_id, $price, $cost, $reorder, $desc, $img, $visible, $finalCode, $product_id]);
            $updated++;
        } else {
            if (empty($code)) {
                $code = generateProductCodeImport($pdo, $name, $generated_codes_this_batch);
                $debug_log[] = "Row $rowCount: Generated code '$code' for '$name'";
            }
            $insert_stmt->execute([$name, $code, $cat_id, $price, $cost, $reorder, $desc, $img, $visible]);
            $product_id = $pdo->lastInsertId();
            $inserted++;
        }

        // 3. Handle Stock
        foreach ($branchMap as $bName => $bId) {
            if (isset($colIndex[$bName])) {
                $idx = $colIndex[$bName];
                $rawVal = isset($row[$idx]) ? trim($row[$idx]) : '';
                if ($rawVal !== '' && is_numeric($rawVal)) {
                    $stockVal = (int)$rawVal;
                    $check_stock_stmt->execute([':pid' => $product_id, ':bid' => $bId]);
                    $stock_id = $check_stock_stmt->fetchColumn();
                    if ($stock_id) $update_stock_stmt->execute([':stock' => $stockVal, ':sid' => $stock_id]);
                    else $insert_stock_stmt->execute([':pid' => $product_id, ':bid' => $bId, ':stock' => $stockVal]);
                    $stock_updated++;
                }
            }
        }
    }

    $pdo->commit();
    fclose($handle);
    echo json_encode([
        'success' => true, 
        'message' => "Import complete.\nInsert: $inserted, Update: $updated, Stock Updates: $stock_updated.",
        'debug' => $debug_log
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fclose($handle);
    http_response_code(500);
    
    $msg = $e->getMessage();
    if (strpos($msg, '1062') !== false) {
        $msg = "Duplicate Code Detected: " . $msg . ". This usually means a product with that code already exists in the database (possibly hidden).";
    }
    
    echo json_encode(['error' => 'Import failed: ' . $msg, 'debug' => $debug_log]);
}
?>