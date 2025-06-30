<?php
require_once 'cors.php';
require_once 'config.php';

header('Content-Type: application/json');

// التحقق من المصادقة - تم تعطيله مؤقتا للتصحيح
// checkAuth();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            // Re-adding the JOIN query to get appointment counts
            $stmt = $pdo->query("
                SELECT d.*, 
                       COUNT(DISTINCT a.id) as total_appointments,
                       COUNT(DISTINCT CASE WHEN a.status = 'completed' THEN a.id END) as completed_appointments
                FROM doctors d
                LEFT JOIN appointments a ON d.id = a.doctor_id
                GROUP BY d.id
                ORDER BY d.name
            ");
            $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $doctors
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database query failed: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data) {
            $stmt = $pdo->prepare("INSERT INTO doctors (name, specialty, experience, working_hours) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$data['name'], $data['specialty'], $data['experience'], $data['working_hours']])) {
                echo json_encode(['success' => true, 'message' => 'Doctor added successfully!']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to add doctor.']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid input.']);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data && isset($data['id'])) {
            $stmt = $pdo->prepare("UPDATE doctors SET name = ?, specialty = ?, experience = ?, working_hours = ? WHERE id = ?");
            if ($stmt->execute([$data['name'], $data['specialty'], $data['experience'], $data['working_hours'], $data['id']])) {
                echo json_encode(['success' => true, 'message' => 'Doctor updated successfully!']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update doctor.']);
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