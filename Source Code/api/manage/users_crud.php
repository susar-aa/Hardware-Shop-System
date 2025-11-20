<?php
session_start();
include_once '../../config/database.php';
header('Content-Type: application/json');

// Security check: Must be a logged-in Admin to manage users
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
            // Select all users BUT never send the password hash
            // UPDATED: Added branch_id to the query
            $query = "SELECT user_id, name, email, role, branch_id FROM users ORDER BY name ASC";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $users = $stmt->fetchAll();
            echo json_encode($users);
            break;

        // --- CREATE (POST) ---
        case 'POST':
            $data = json_decode(file_get_contents('php://input'));
            if (empty($data->name) || empty($data->email) || empty($data->role) || empty($data->password)) {
                http_response_code(400);
                echo json_encode(['error' => 'Name, email, role, and password are required.']);
                exit;
            }
            // Hash the password
            $hashed_password = password_hash($data->password, PASSWORD_BCRYPT);

            // UPDATED: Handle branch_id
            $branch_id = ($data->role === 'staff' && !empty($data->branch_id)) ? $data->branch_id : null;

            $query = "INSERT INTO users (name, email, role, password, branch_id) VALUES (:name, :email, :role, :password, :branch_id)";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':name', $data->name);
            $stmt->bindParam(':email', $data->email);
            $stmt->bindParam(':role', $data->role);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':branch_id', $branch_id, $branch_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'User created.']);
            break;

        // --- UPDATE (PUT) ---
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'));
            if (empty($data->name) || empty($data->email) || empty($data->role) || empty($data->user_id)) {
                http_response_code(400);
                echo json_encode(['error' => 'Name, email, role, and user ID are required.']);
                exit;
            }

            // UPDATED: Handle branch_id
            $branch_id = ($data->role === 'staff' && !empty($data->branch_id)) ? $data->branch_id : null;

            // Check if password needs to be updated
            if (!empty($data->password)) {
                // Update with password
                $hashed_password = password_hash($data->password, PASSWORD_BCRYPT);
                $query = "UPDATE users SET name = :name, email = :email, role = :role, password = :password, branch_id = :branch_id WHERE user_id = :user_id";
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':password', $hashed_password);
            } else {
                // Update without password
                $query = "UPDATE users SET name = :name, email = :email, role = :role, branch_id = :branch_id WHERE user_id = :user_id";
                $stmt = $pdo->prepare($query);
            }
            
            $stmt->bindParam(':name', $data->name);
            $stmt->bindParam(':email', $data->email);
            $stmt->bindParam(':role', $data->role);
            $stmt->bindParam(':branch_id', $branch_id, $branch_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $data->user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'User updated.']);
            break;

        // --- DELETE ---
        case 'DELETE':
            if (empty($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'User ID is required.']);
                exit;
            }
            $user_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
            
            // Prevent user from deleting themselves
            if ($user_id == $_SESSION['user_id']) {
                 http_response_code(403);
                 echo json_encode(['error' => 'You cannot delete your own account.']);
                 exit;
            }

            $query = "DELETE FROM users WHERE user_id = :user_id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'User deleted.']);
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
    // Check for duplicate username
    if ($e->errorInfo[1] == 1062) {
         http_response_code(409); // Conflict
         echo json_encode(['error' => 'This email is already in use.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>