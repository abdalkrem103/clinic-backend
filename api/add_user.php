<?php
require_once 'cors.php';
require_once 'config.php';

header('Content-Type: application/json');

// تحقق من أن المستخدم الحالي أدمن
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بإضافة مستخدمين']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['username'], $data['password'], $data['role'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'جميع الحقول مطلوبة']);
    exit;
}

$username = $data['username'];
$password = password_hash($data['password'], PASSWORD_BCRYPT);
$role = $data['role'];

try {
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute([$username, $password, $role]);
    echo json_encode(['success' => true, 'message' => 'تمت إضافة المستخدم بنجاح']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'فشل في إضافة المستخدم: ' . $e->getMessage()]);
}
?> 