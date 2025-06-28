<?php
require_once 'cors.php';
require_once 'config.php';

header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بتعديل المستخدمين']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['id'], $data['username'], $data['role'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'جميع الحقول مطلوبة']);
    exit;
}

$id = intval($data['id']);
$username = $data['username'];
$role = $data['role'];

try {
    if (!empty($data['password'])) {
        $password = password_hash($data['password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?");
        $stmt->execute([$username, $password, $role, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
        $stmt->execute([$username, $role, $id]);
    }
    echo json_encode(['success' => true, 'message' => 'تم تحديث بيانات المستخدم بنجاح']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'فشل في تحديث المستخدم: ' . $e->getMessage()]);
}
?> 