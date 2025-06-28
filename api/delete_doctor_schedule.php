<?php
require_once 'cors.php';
require_once 'config.php';

header('Content-Type: application/json');

// تفعيل التحقق من المصادقة
checkAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
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
    // التحقق من وجود مواعيد مرتبطة بالجدول
    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) FROM appointments
        WHERE appointment_id = ?
        AND status != 'cancelled'
    ");
    $checkStmt->execute([$data['id']]);
    
    if ($checkStmt->fetchColumn() > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'لا يمكن حذف الجدول لوجود مواعيد مرتبطة به']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM doctor_schedules WHERE id = ?");
    if ($stmt->execute([$data['id']])) {
        // إضافة إشعار لحذف الجدول
        $notificationStmt = $pdo->prepare("
            INSERT INTO notifications (title, message, type) 
            VALUES (?, ?, 'schedule')
        ");
        $notificationStmt->execute([
            'حذف جدول مواعيد',
            'تم حذف جدول مواعيد رقم: ' . $data['id']
        ]);

        echo json_encode(['success' => true, 'message' => 'تم حذف جدول المواعيد بنجاح']);
    } else {
        throw new PDOException('فشل في حذف جدول المواعيد');
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطأ في حذف جدول المواعيد: ' . $e->getMessage()]);
}
?> 