<?php
require_once 'cors.php';
require_once 'config.php';

header('Content-Type: application/json');

// التحقق من تسجيل الدخول - تم تعطيله مؤقتا للتصحيح
// checkAuth();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            $stmt = $pdo->query("SELECT * FROM services");
            $services = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $services]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database query failed: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data) {
            $stmt = $pdo->prepare("INSERT INTO services (name, description, price) VALUES (?, ?, ?)");
            if ($stmt->execute([$data['name'], $data['description'], $data['price']])) {
                echo json_encode(['success' => true, 'message' => 'Service added successfully!']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to add service.']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid input.']);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data && isset($data['id'])) {
            $stmt = $pdo->prepare("UPDATE services SET name = ?, description = ?, price = ? WHERE id = ?");
            if ($stmt->execute([$data['name'], $data['description'], $data['price'], $data['id']])) {
                echo json_encode(['success' => true, 'message' => 'Service updated successfully!']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update service.']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid input or missing ID.']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        break;
}
?> 