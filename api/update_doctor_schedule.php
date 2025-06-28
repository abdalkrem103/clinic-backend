<?php
require_once 'cors.php';
require_once 'config.php';

header('Content-Type: application/json');

// تفعيل التحقق من المصادقة
checkAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'طريقة طلب غير مدعومة']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'معرف الجدول مطلوب']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE doctor_schedules SET 
            start_time = ?,
            end_time = ?,
            slot_duration = ?,
            is_available = ?,
            notes = ?
        WHERE id = ?
    ");

    if ($stmt->execute([
        $data['start_time'],
        $data['end_time'],
        $data['slot_duration'] ?? 30,
        $data['is_available'] ?? true,
        $data['notes'] ?? null,
        $data['id']
    ])) {
        // إضافة إشعار لتحديث الجدول
        $notificationStmt = $pdo->prepare("
            INSERT INTO notifications (title, message, type) 
            VALUES (?, ?, 'schedule')
        ");
        $notificationStmt->execute([
            'تحديث جدول مواعيد',
            'تم تحديث جدول مواعيد رقم: ' . $data['id']
        ]);

        echo json_encode(['success' => true, 'message' => 'تم تحديث جدول المواعيد بنجاح']);
    } else {
        throw new PDOException('فشل في تحديث جدول المواعيد');
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطأ في تحديث جدول المواعيد: ' . $e->getMessage()]);
}
?> 