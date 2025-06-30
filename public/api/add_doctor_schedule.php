<?php
require_once 'cors.php';
require_once 'config.php';

header('Content-Type: application/json');

// تفعيل التحقق من المصادقة
checkAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'طريقة طلب غير مدعومة']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// التحقق من البيانات المطلوبة
$requiredFields = ['doctor_id', 'week_start', 'schedules'];
$missingFields = array_filter($requiredFields, function($field) use ($data) {
    return empty($data[$field]);
});

if (!empty($missingFields)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'البيانات المطلوبة غير مكتملة: ' . implode(', ', $missingFields)
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Check if there are any existing appointments linked to the schedules being deleted
    $checkAppointmentsStmt = $pdo->prepare("
        SELECT COUNT(*) FROM appointments a
        JOIN doctor_schedules ds ON a.appointment_id = ds.id AND a.doctor_id = ds.doctor_id
        WHERE ds.doctor_id = ? AND ds.week_start = ? AND a.status IN ('confirmed', 'pending')
    ");
    $checkAppointmentsStmt->execute([$data['doctor_id'], $data['week_start']]);
    $existingAppointmentsCount = $checkAppointmentsStmt->fetchColumn();

    if ($existingAppointmentsCount > 0) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'لا يمكن تعديل أو حذف جدول المواعيد لأن هناك مواعيد محجوزة مرتبطة به. الرجاء إلغاء أو إعادة جدولة المواعيد الموجودة أولاً.'
        ]);
        exit;
    }

    // حذف الجدول القديم للأسبوع المحدد
    $deleteStmt = $pdo->prepare("
        DELETE FROM doctor_schedules 
        WHERE doctor_id = ? AND week_start = ?
    ");
    $deleteStmt->execute([$data['doctor_id'], $data['week_start']]);

    // إضافة الجدول الجديد
    $insertStmt = $pdo->prepare("
        INSERT INTO doctor_schedules (
            doctor_id, week_start, day_of_week,
            start_time, end_time, slot_duration,
            is_available, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($data['schedules'] as $schedule) {
        foreach ($schedule['periods'] as $period) {
            $insertStmt->execute([
                $data['doctor_id'],
                $data['week_start'],
                $schedule['day_of_week'],
                $period['start_time'],
                $period['end_time'],
                $schedule['slot_duration'] ?? 30,
                $period['is_available'] ?? true,
                $schedule['notes'] ?? null
            ]);
        }
    }

    $pdo->commit();

    // إضافة إشعار للجدول الجديد
    $notificationStmt = $pdo->prepare("
        INSERT INTO notifications (title, message, type) 
        VALUES (?, ?, 'schedule')
    ");
    $notificationStmt->execute([
        'جدول مواعيد جديد',
        'تم إضافة جدول مواعيد جديد للطبيب بتاريخ: ' . $data['week_start']
    ]);

    echo json_encode([
        'success' => true, 
        'message' => 'تم إضافة جدول المواعيد بنجاح'
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطأ في إضافة جدول المواعيد: ' . $e->getMessage()]);
}
?> 