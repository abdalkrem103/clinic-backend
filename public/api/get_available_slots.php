<?php
require_once 'cors.php';
require_once 'config.php';

header('Content-Type: application/json');

// تفعيل عرض الأخطاء للتطوير
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// تعطيل التحقق من المصادقة مؤقتاً
// checkAuth();

$method = $_SERVER['REQUEST_METHOD'];

error_log("get_available_slots.php: Request received with method: " . $method); // Log request method

if ($method === 'GET') {
    try {
        $doctor_id = isset($_GET['doctor_id']) ? $_GET['doctor_id'] : null;
        $appointment_date = isset($_GET['appointment_date']) ? $_GET['appointment_date'] : null;

        error_log("get_available_slots.php: Received doctor_id: " . $doctor_id . ", appointment_date: " . $appointment_date); // Log received parameters

        if (!$doctor_id || !$appointment_date) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'معرف الطبيب وتاريخ الموعد مطلوبان']);
            error_log("get_available_slots.php: Missing doctor_id or appointment_date."); // Log missing parameters error
            exit();
        }

        // تحديد اليوم من الأسبوع للتاريخ المحدد (0=الأحد, 6=السبت)
        $timestamp = strtotime($appointment_date);
        $day_of_week = date('w', $timestamp);
        error_log("get_available_slots.php: Date " . $appointment_date . " is day of week: " . $day_of_week); // Log day of week

        // يمكن تعديل هذا ليناسب نظام الأيام في قاعدة البيانات إذا كان مختلفاً (مثلاً 1=الإثنين..7=الأحد)
        // بناءً على وصفك السابق يبدو أنه يستخدم الأرقام.

        // 1. جلب الجدول الزمني العام للطبيب في هذا اليوم
        $sql_schedule = "
            SELECT start_time, end_time, slot_duration
            FROM doctor_schedules
            WHERE doctor_id = ? AND day_of_week = ?
            LIMIT 1
        ";
        error_log("get_available_slots.php: Fetching schedule with doctor_id=" . $doctor_id . " and day_of_week=" . $day_of_week); // Log schedule query parameters
        $stmt_schedule = $pdo->prepare($sql_schedule);
        $stmt_schedule->execute([$doctor_id, $day_of_week]);
        $schedule = $stmt_schedule->fetch(PDO::FETCH_ASSOC);

        if (!$schedule) {
            // لا يوجد جدول زمني محدد لهذا الطبيب في هذا اليوم
            error_log("get_available_slots.php: No schedule found for doctor_id=" . $doctor_id . " on day_of_week=" . $day_of_week); // Log no schedule found
            echo json_encode(['success' => true, 'data' => []]);
            exit();
        }

        error_log("get_available_slots.php: Schedule found: " . json_encode($schedule)); // Log found schedule

        $start_time = strtotime($schedule['start_time']);
        $end_time = strtotime($schedule['end_time']);
        $slot_duration_minutes = $schedule['slot_duration']; // مدة الموعد بالدقائق

        // 2. جلب المواعيد المحجوزة لهذا الطبيب في هذا التاريخ
        // نعتبر أي موعد حالته ليست 'pending' على أنه محجوز
        $sql_booked = "
            SELECT appointment_time
            FROM appointments
            WHERE doctor_id = ? AND appointment_date = ? AND status != 'pending'
        ";
        error_log("get_available_slots.php: Fetching booked appointments for doctor_id=" . $doctor_id . " on appointment_date=" . $appointment_date); // Log booked query parameters
        $stmt_booked = $pdo->prepare($sql_booked);
        $stmt_booked->execute([$doctor_id, $appointment_date]);
        $booked_slots = $stmt_booked->fetchAll(PDO::FETCH_COLUMN);

        error_log("get_available_slots.php: Booked slots found: " . json_encode($booked_slots)); // Log booked slots

        // تحويل أوقات المواعيد المحجوزة إلى صيغة سهلة للمقارنة (HH:mm:ss)
        $booked_times = array_map(function($time) { return date('H:i:s', strtotime($time)); }, $booked_slots);

        error_log("get_available_slots.php: Booked times formatted: " . json_encode($booked_times)); // Log formatted booked times

        // 3. توليد الأوقات المتاحة بناءً على الجدول الزمني واستبعاد المحجوزة
        $availableSlots = [];
        $current_time = $start_time;

        error_log("get_available_slots.php: Generating available slots from " . date('H:i:s', $start_time) . " to " . date('H:i:s', $end_time) . " with duration " . $slot_duration_minutes . " minutes."); // Log slot generation parameters

        while ($current_time < $end_time) {
            $slot_time = date('H:i:s', $current_time);

            // التحقق مما إذا كان هذا الوقت محجوزاً
            if (!in_array($slot_time, $booked_times)) {
                // هذا الوقت متاح، أضفه إلى القائمة
                // يمكن هنا بناء كائن slot يشبه ما تتوقعه الواجهة الأمامية
                // مثال بسيط: نرجع الوقت فقط
                 $availableSlots[] = [
                     'id' => null, // أو توليد ID مؤقت إذا لزم الأمر في الواجهة الأمامية
                     'doctor_id' => $doctor_id,
                     'appointment_date' => $appointment_date,
                     'appointment_time' => date('H:i', $current_time), // تنسيق HH:mm
                 ];
            }

            // الانتقال إلى الوقت التالي بناءً على مدة الموعد
            $current_time = strtotime("+{$slot_duration_minutes} minutes", $current_time);
        }

        error_log("get_available_slots.php: Generated " . count($availableSlots) . " available slots."); // Log number of slots generated
        error_log("get_available_slots.php: Available slots data: " . json_encode($availableSlots)); // Log generated slots data

        echo json_encode(['success' => true, 'data' => $availableSlots]);

    } catch (PDOException $e) {
        error_log("خطأ في قاعدة البيانات في get_available_slots.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'خطأ في قاعدة البيانات',
            'debug' => $e->getMessage()
        ]);
    } catch (Exception $e) {
        error_log("خطأ عام في get_available_slots.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'حدث خطأ أثناء معالجة الطلب',
            'debug' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    error_log("get_available_slots.php: Invalid request method: " . $method); // Log invalid method
    echo json_encode(['success' => false, 'message' => 'طريقة طلب غير مدعومة']);
}

error_log("get_available_slots.php: Script finished."); // Log script finish

?> 