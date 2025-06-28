<?php
require_once 'cors.php';
require_once 'config.php';

header('Content-Type: application/json');

// checkAuth(); // Decide if authentication is needed for this endpoint - تم تعطيله مؤقتا

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->query("SELECT * FROM services"); // Or filter if needed
        $services = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $services]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database query failed: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
}
?> 