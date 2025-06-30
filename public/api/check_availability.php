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

$doctorId = isset($_GET['doctor_id']) ? $_GET['doctor_id'] : null;
$date = isset($_GET['date']) ? $_GET['date'] : null;

if (!$doctorId || !$date) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'معرف الطبيب والتاريخ مطلوبان']);
    exit;
}

try {
    // جلب جدول الطبيب للتاريخ المحدد
    $scheduleStmt = $pdo->prepare("
        SELECT * FROM doctor_schedules 
        WHERE doctor_id = ? 
        AND week_start <= ? 
        AND DATE_ADD(week_start, INTERVAL 6 DAY) >= ?
        AND day_of_week = DAYOFWEEK(?)
        AND is_available = 1
    ");
    $scheduleStmt->execute([$doctorId, $date, $date, $date]);
    $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);

    if (!$schedule) {
        echo json_encode([
            'success' => true,
            'available' => false,
            'message' => 'لا يوجد جدول متاح لهذا اليوم'
        ]);
        exit;
    }

    // جلب المواعيد المحجوزة
    $appointmentsStmt = $pdo->prepare("
        SELECT appointment_time 
        FROM appointments 
        WHERE doctor_id = ? 
        AND appointment_date = ? 
        AND status != 'cancelled'
    ");
    $appointmentsStmt->execute([$doctorId, $date]);
    $bookedTimes = $appointmentsStmt->fetchAll(PDO::FETCH_COLUMN);

    // توليد المواعيد المتاحة
    $availableSlots = [];
    $startTime = strtotime($schedule['start_time']);
    $endTime = strtotime($schedule['end_time']);
    $duration = $schedule['slot_duration'] * 60; // تحويل الدقائق إلى ثواني

    for ($time = $startTime; $time < $endTime; $time += $duration) {
        $slotTime = date('H:i', $time);
        if (!in_array($slotTime, $bookedTimes)) {
            $availableSlots[] = $slotTime;
        }
    }

    echo json_encode([
        'success' => true,
        'available' => true,
        'data' => [
            'schedule' => $schedule,
            'available_slots' => $availableSlots,
            'slot_duration' => $schedule['slot_duration']
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطأ في التحقق من توفر المواعيد: ' . $e->getMessage()]);
}
?> 