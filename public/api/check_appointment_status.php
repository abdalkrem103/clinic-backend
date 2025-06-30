<?php
require_once 'cors.php';
require_once 'config.php';

header('Content-Type: application/json');

// تفعيل التحقق من المصادقة
checkAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'طريقة طلب غير مدعومة']);
    exit;
}

$appointmentId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$appointmentId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'معرف الموعد مطلوب']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            p.first_name as patient_first_name,
            p.last_name as patient_last_name,
            p.phone_number as patient_phone,
            d.name as doctor_name,
            d.specialty as doctor_specialty
        FROM appointments a
        LEFT JOIN patients p ON a.patient_id = p.id
        LEFT JOIN doctors d ON a.doctor_id = d.id
        WHERE a.id = ?
    ");
    $stmt->execute([$appointmentId]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'الموعد غير موجود']);
        exit;
    }

    // التحقق من حالة الموعد
    $status = $appointment['status'];
    $appointmentDate = strtotime($appointment['appointment_date']);
    $currentDate = time();
    $timeDiff = $appointmentDate - $currentDate;

    // تحديث حالة الموعد إذا لزم الأمر
    if ($status === 'pending') {
        if ($timeDiff < -3600) { // أكثر من ساعة متأخر
            $updateStmt = $pdo->prepare("UPDATE appointments SET status = 'missed' WHERE id = ?");
            $updateStmt->execute([$appointmentId]);
            $status = 'missed';
        } elseif ($timeDiff <= 0 && $timeDiff > -3600) { // في الوقت الحالي
            $updateStmt = $pdo->prepare("UPDATE appointments SET status = 'in_progress' WHERE id = ?");
            $updateStmt->execute([$appointmentId]);
            $status = 'in_progress';
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'appointment' => $appointment,
            'current_status' => $status,
            'time_remaining' => $timeDiff > 0 ? $timeDiff : 0
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطأ في التحقق من حالة الموعد: ' . $e->getMessage()]);
}
?> 