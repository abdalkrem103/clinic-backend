<?php
require_once 'cors.php';
require_once 'config.php';

header('Content-Type: application/json');
// Removed duplicate CORS headers, relying on cors.php
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: GET, POST, PUT');
// header('Access-Control-Allow-Headers: Content-Type');

// التحقق من المصادقة - تم تعطيله مؤقتا للتصحيح
// checkAuth();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Handle GET request (fetch inventory)
        try {
            $stmt = $pdo->query("SELECT * FROM inventory");
            $inventory = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $inventory]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database query failed: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        // Handle POST request (add inventory item)
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data) {
            // Example: Insert new item
            $stmt = $pdo->prepare("INSERT INTO inventory (item_name, quantity, min_threshold) VALUES (?, ?, ?)");
            if ($stmt->execute([$data['item_name'], $data['quantity'], $data['min_threshold']])) {
                 echo json_encode(['success' => true, 'message' => 'Item added successfully!']);
            } else {
                 http_response_code(500);
                 echo json_encode(['success' => false, 'message' => 'Failed to add item.']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid input.']);
        }
        break;

    case 'PUT':
        // Handle PUT request (update inventory item)
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data && isset($data['id'])) {
            // Example: Update item
            $stmt = $pdo->prepare("UPDATE inventory SET item_name = ?, quantity = ?, min_threshold = ? WHERE id = ?");
            if ($stmt->execute([$data['item_name'], $data['quantity'], $data['min_threshold'], $data['id']])) {
                 echo json_encode(['success' => true, 'message' => 'Item updated successfully!']);
            } else {
                 http_response_code(500);
                 echo json_encode(['success' => false, 'message' => 'Failed to update item.']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid input or missing ID.']);
        }
        break;

    // Add other cases for DELETE, etc. if needed

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        break;
}
?> 