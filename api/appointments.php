<?php
require_once 'cors.php';
require_once 'config.php';
require_once 'email_sender.php';

header('Content-Type: application/json');

// تفعيل التحقق من المصادقة
checkAuth();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'getDoctorSlotsAndStatus':
                    if (isset($_GET['doctor_id']) && isset($_GET['date'])) {
                        getDoctorSlotsAndStatus($_GET['doctor_id'], $_GET['date']);
                    } else {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'يجب تحديد معرف الطبيب والتاريخ']);
                    }
                    break;

                case 'getBookedDates':
                    if (isset($_GET['doctor_id'])) {
                        getBookedDates($_GET['doctor_id']);
                    } else {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'يجب تحديد معرف الطبيب']);
                    }
                    break;

                case 'getAppointmentDetails':
                    if (isset($_GET['appointment_id'])) {
                        getAppointmentDetails($_GET['appointment_id']);
                    } else {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'يجب تحديد معرف الموعد']);
                    }
                    break;

                case 'getAppointmentsByDate':
                    if (isset($_GET['date'])) {
                        getAppointmentsByDate($_GET['date']);
                    } else {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'يجب تحديد التاريخ']);
                    }
                    break;

                case 'getAllAppointments':
                    getAllAppointments();
                    break;

                case 'getAppointmentsByPatient':
                    if (isset($_GET['patient_id'])) {
                        getAppointmentsByPatient($_GET['patient_id']);
                    } else {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'يجب تحديد معرف المريض']);
                    }
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'إجراء غير صالح']);
                    break;
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'يجب تحديد الإجراء المطلوب']);
        }
        break;

    case 'POST':
        $raw_post_data = file_get_contents('php://input');
        error_log("appointments.php: Raw POST Data: " . $raw_post_data);
        $data = json_decode($raw_post_data, true);
        error_log("appointments.php: Decoded POST Data: " . print_r($data, true));
        
        if (isset($data['action'])) {
            switch ($data['action']) {
                case 'addAppointment':
                    if (isset($data['patient_id']) && isset($data['doctor_id']) && isset($data['appointment_date']) && isset($data['appointment_time'])) {
                        addAppointment($data);
                    } else {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'يجب تحديد معرف المريض والطبيب والتاريخ والوقت']);
                    }
                    break;

                case 'sendConfirmation':
                    if (isset($data['appointment_id'])) {
                        sendConfirmation($data['appointment_id']);
                    } else {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'يجب تحديد معرف الموعد']);
                    }
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'إجراء غير صالح']);
                    break;
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'يجب تحديد الإجراء المطلوب']);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (isset($data['action'])) {
            switch ($data['action']) {
                case 'updateAppointmentStatus':
                    if (isset($data['appointment_id']) && isset($data['status'])) {
                        updateAppointmentStatus($data['appointment_id'], $data['status']);
                    } else {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'يجب تحديد معرف الموعد والحالة الجديدة']);
                    }
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'إجراء غير صالح']);
                    break;
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'يجب تحديد الإجراء المطلوب']);
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (isset($data['action']) && $data['action'] === 'cancelAppointment' && isset($data['appointment_id'])) {
            cancelAppointment($data['appointment_id']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'يجب تحديد معرف الموعد للإلغاء']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'طريقة طلب غير مدعومة']);
        break;
}

function getDoctorSlotsAndStatus($doctor_id, $date) {
    global $pdo;

    try {
        // Get day of week for the given date (1 = Sunday, 7 = Saturday)
        $stmt = $pdo->prepare("SELECT DAYOFWEEK(?) as day_of_week");
        $stmt->execute([$date]);
        $dayOfWeek = $stmt->fetch(PDO::FETCH_ASSOC)['day_of_week'];

        // Get all defined schedule periods for the doctor on that day
        $stmt = $pdo->prepare("
            SELECT
                id, start_time, end_time, slot_duration, is_available
            FROM
                doctor_schedules
            WHERE
                doctor_id = ? AND day_of_week = ?
            ORDER BY
                start_time
        ");
        $stmt->execute([$doctor_id, $dayOfWeek]);
        $schedulePeriods = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $availableSlots = [];

        foreach ($schedulePeriods as $period) {
            if (!$period['is_available']) {
                continue; // Skip if the period is marked as not available
            }

            $start_time = strtotime($period['start_time']);
            $end_time = strtotime($period['end_time']);
            $slot_duration = (int)$period['slot_duration'];

            if ($slot_duration <= 0) {
                // Avoid infinite loops if slot_duration is 0 or negative
                continue;
            }

            for ($currentTime = $start_time; $currentTime < $end_time; $currentTime += ($slot_duration * 60)) {
                $slot_time = date('H:i:s', $currentTime);
                error_log("getDoctorSlotsAndStatus: Calculated slot_time: " . $slot_time);

                // Check if this specific slot is already booked for the given date
                $stmt = $pdo->prepare("
                    SELECT
                        a.id AS appointment_id,
                        a.patient_id,
                        a.status AS appointment_status
                    FROM
                        appointments a
                    WHERE
                        a.doctor_id = ?
                        AND a.appointment_date = ?
                        AND a.appointment_time = ?
                        AND a.status IN ('confirmed', 'pending')
                ");
                $stmt->execute([$doctor_id, $date, $slot_time]);
                $bookedAppointment = $stmt->fetch(PDO::FETCH_ASSOC);

                $status = $bookedAppointment ? 'booked' : 'available';
                $appointment_id = $bookedAppointment ? $bookedAppointment['appointment_id'] : null;
                $patient_id = $bookedAppointment ? $bookedAppointment['patient_id'] : null;
                $appointment_status = $bookedAppointment ? $bookedAppointment['appointment_status'] : null;

                $availableSlots[] = [
                    'schedule_slot_id' => $period['id'], // Reference to the schedule entry that generated this slot
                    'appointment_time' => $slot_time,
                    'doctor_id' => $doctor_id,
                    'status' => $status,
                    'appointment_id' => $appointment_id,
                    'patient_id' => $patient_id,
                    'appointment_status' => $appointment_status
                ];
            }
        }

        error_log("getDoctorSlotsAndStatus: Final availableSlots array before encoding: " . print_r($availableSlots, true));

        echo json_encode([
            'success' => true,
            'data' => $availableSlots
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
        ]);
    }
}

function getBookedDates($doctor_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT appointment_date 
            FROM appointments 
            WHERE doctor_id = ? AND status != 'cancelled' AND appointment_date >= CURDATE()
            ORDER BY appointment_date
        ");
        $stmt->execute([$doctor_id]);
        $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode([
            'success' => true,
            'data' => $dates
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
        ]);
    }
}

function getAppointmentDetails($appointment_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT a.*, p.first_name, p.last_name, p.phone_number, d.name as doctor_name
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            WHERE a.id = ?
        ");
        $stmt->execute([$appointment_id]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($appointment) {
            echo json_encode([
                'success' => true,
                'data' => $appointment
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'الموعد غير موجود'
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
        ]);
    }
}

function addAppointment($data) {
    global $pdo;
    try {
        $pdo->beginTransaction();

        // تحقق من عدم وجود موعد محجوز لنفس الطبيب والتاريخ والوقت
        $stmt = $pdo->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status NOT IN ('cancelled', 'completed')");
        $stmt->execute([$data['doctor_id'], $data['appointment_date'], $data['appointment_time']]);
        if ($stmt->fetch()) {
            throw new Exception('هذا الموعد محجوز بالفعل');
        }

        // تحقق من عدم وجود موعد آخر لنفس المريض في نفس التاريخ والوقت (حتى مع طبيب آخر)
        $stmt = $pdo->prepare("SELECT id FROM appointments WHERE patient_id = ? AND appointment_date = ? AND appointment_time = ? AND status NOT IN ('cancelled', 'completed')");
        $stmt->execute([$data['patient_id'], $data['appointment_date'], $data['appointment_time']]);
        if ($stmt->fetch()) {
            throw new Exception('لديك بالفعل موعد آخر في هذا الوقت مع طبيب آخر.');
        }

        // تحقق من عدد المواعيد المستقبلية غير المنفذة أو غير الملغاة لهذا المريض
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND appointment_date >= CURDATE() AND status IN ('confirmed', 'pending')");
        $stmt->execute([$data['patient_id']]);
        $count = $stmt->fetchColumn();
        $max_appointments = 3; // يمكنك تغيير الحد الأقصى هنا
        if ($count >= $max_appointments) {
            throw new Exception('لا يمكنك حجز أكثر من 3 مواعيد مستقبلية.');
        }

        // أضف الموعد الجديد
        $stmt = $pdo->prepare("INSERT INTO appointments (doctor_id, patient_id, appointment_date, appointment_time, status, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $data['doctor_id'],
            $data['patient_id'],
            $data['appointment_date'],
            $data['appointment_time'],
            $data['status'] ?? 'confirmed', // الافتراضي هو مؤكد
            $data['notes'] ?? null
        ]);
        $appointment_id = $pdo->lastInsertId();

        // إرسال بريد إلكتروني للتأكيد
        // الحصول على تفاصيل الموعد للإيميل
        $stmt = $pdo->prepare("
            SELECT p.email as patient_email, p.first_name as patient_name, d.name as doctor_name, a.appointment_date, a.appointment_time
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            WHERE a.id = ?
        ");
        $stmt->execute([$appointment_id]);
        $appointmentDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($appointmentDetails) {
            $emailSubject = 'تم تأكيد حجز موعدك';
            $emailBody = "
                <h2>تم تأكيد موعدك</h2>
                <p>مرحباً {$appointmentDetails['patient_name']},</p>
                <p>تم تأكيد حجز موعدك بنجاح مع الدكتور {$appointmentDetails['doctor_name']}.</p>
                <p>تفاصيل الموعد:</p>
                <ul>
                    <li>التاريخ: " . date('d-m-Y', strtotime($appointmentDetails['appointment_date'])) . "</li>
                    <li>الوقت: " . date('h:i A', strtotime($appointmentDetails['appointment_time'])) . "</li>
                </ul>
                <p>نرجو الحضور قبل الموعد بـ 10 دقائق.</p>
            ";
            
            // لا نتحقق من نتيجة الإرسال هنا لمنع فشل العملية كلها إذا فشل الإيميل
            sendEmail(
                $appointmentDetails['patient_email'],
                $emailSubject,
                $emailBody
            );
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'تم حجز الموعد بنجاح وإرسال تأكيد بالبريد الإلكتروني.', 'appointment_id' => $appointment_id]);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateAppointmentStatus($appointment_id, $status) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();

        // التحقق من وجود الموعد
        $stmt = $pdo->prepare("
            SELECT a.*, p.email as patient_email, CONCAT(p.first_name, ' ', p.last_name) as patient_name, d.name as doctor_name 
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            WHERE a.id = ?
        ");
        $stmt->execute([$appointment_id]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$appointment) {
            throw new Exception('الموعد غير موجود');
        }

        // تحديث حالة الموعد
        $stmt = $pdo->prepare("
            UPDATE appointments 
            SET status = ?
            WHERE id = ?
        ");
        $stmt->execute([$status, $appointment_id]);

        // إرسال بريد إلكتروني حسب الحالة
        $emailSubject = '';
        $emailBody = '';

        switch ($status) {
            case 'confirmed':
                $emailSubject = 'تأكيد الموعد';
                $emailBody = "
                    <h2>تم تأكيد موعدك</h2>
                    <p>مرحباً {$appointment['patient_name']},</p>
                    <p>تم تأكيد موعدك مع الدكتور {$appointment['doctor_name']}.</p>
                    <p>تفاصيل الموعد:</p>
                    <ul>
                        <li>التاريخ: {$appointment['appointment_date']}</li>
                        <li>الوقت: {$appointment['appointment_time']}</li>
                        <li>حالة الموعد: مؤكد</li>
                    </ul>
                    <p>نرجو الحضور قبل الموعد بـ 10 دقائق.</p>
                ";
                break;

            case 'cancelled':
                $emailSubject = 'إلغاء الموعد';
                $emailBody = "
                    <h2>تم إلغاء موعدك</h2>
                    <p>مرحباً {$appointment['patient_name']},</p>
                    <p>نأسف لإعلامك أنه تم إلغاء موعدك مع الدكتور {$appointment['doctor_name']}.</p>
                    <p>تفاصيل الموعد الملغي:</p>
                    <ul>
                        <li>التاريخ: {$appointment['appointment_date']}</li>
                        <li>الوقت: {$appointment['appointment_time']}</li>
                    </ul>
                    <p>يمكنك حجز موعد جديد من خلال موقعنا.</p>
                ";
                break;

            case 'completed':
                $emailSubject = 'اكتمال الموعد';
                $emailBody = "
                    <h2>اكتمل موعدك</h2>
                    <p>مرحباً {$appointment['patient_name']},</p>
                    <p>نود إعلامك بأن موعدك مع الدكتور {$appointment['doctor_name']} قد اكتمل.</p>
                    <p>تفاصيل الموعد:</p>
                    <ul>
                        <li>التاريخ: {$appointment['appointment_date']}</li>
                        <li>الوقت: {$appointment['appointment_time']}</li>
                    </ul>
                    <p>نتمنى لك الشفاء العاجل.</p>
                ";
                break;
        }

        if ($emailSubject && $emailBody) {
            $emailResult = sendEmail(
                $appointment['patient_email'],
                $emailSubject,
                $emailBody
            );
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'تم تحديث حالة الموعد بنجاح',
            'email_sent' => isset($emailResult) ? $emailResult['status'] === 'success' : false
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function cancelAppointment($appointment_id) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();

        // تحديث حالة الموعد
        $stmt = $pdo->prepare("
            UPDATE appointments 
            SET status = 'cancelled'
            WHERE id = ?
        ");
        $stmt->execute([$appointment_id]);

        if ($stmt->rowCount() > 0) {
            // إضافة إشعار بإلغاء الموعد
            $stmt = $pdo->prepare("
                INSERT INTO notifications (
                    user_id, type, message, created_at
                ) SELECT 
                    p.user_id,
                    'appointment_cancellation',
                    'تم إلغاء موعدك',
                    NOW()
                FROM appointments a
                JOIN patients p ON a.patient_id = p.id
                WHERE a.id = ?
            ");
            $stmt->execute([$appointment_id]);

            $pdo->commit();
            echo json_encode([
                'success' => true,
                'message' => 'تم إلغاء الموعد بنجاح'
            ]);
        } else {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'الموعد غير موجود'
            ]);
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
        ]);
    }
}

function sendConfirmation($appointment_id) {
    global $pdo;
    
    try {
        // الحصول على معلومات الموعد والمريض والطبيب
        $stmt = $pdo->prepare("
            SELECT a.*, p.email as patient_email, p.name as patient_name, d.name as doctor_name 
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            WHERE a.id = ?
        ");
        $stmt->execute([$appointment_id]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$appointment) {
            throw new Exception('الموعد غير موجود');
        }

        if ($appointment['status'] !== 'confirmed') {
            throw new Exception('لا يمكن إرسال تأكيد لموعد غير مؤكد');
        }

        // إرسال بريد إلكتروني تأكيدي
        $emailBody = "
            <h2>تأكيد الموعد</h2>
            <p>مرحباً {$appointment['patient_name']},</p>
            <p>هذا تأكيد لموعدك مع الدكتور {$appointment['doctor_name']}.</p>
            <p>تفاصيل الموعد:</p>
            <ul>
                <li>التاريخ: {$appointment['appointment_date']}</li>
                <li>الوقت: {$appointment['appointment_time']}</li>
                <li>حالة الموعد: مؤكد</li>
            </ul>
            <p>نرجو الحضور قبل الموعد بـ 10 دقائق.</p>
            <p>إذا كنت ترغب في إلغاء أو تغيير الموعد، يرجى التواصل معنا قبل 24 ساعة من الموعد.</p>
        ";

        $emailResult = sendEmail(
            $appointment['patient_email'],
            'تأكيد الموعد',
            $emailBody
        );

        echo json_encode([
            'success' => true,
            'message' => 'تم إرسال تأكيد الموعد بنجاح',
            'email_sent' => $emailResult['status'] === 'success'
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function getAppointmentsByDate($date) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT a.*, p.first_name, p.last_name, d.name as doctor_name
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            WHERE a.appointment_date = ? AND a.status != 'cancelled'
            ORDER BY a.appointment_time ASC
        ");
        $stmt->execute([$date]);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $appointments
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
        ]);
    }
}

function getAllAppointments() {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT a.*, 
                   p.first_name as patient_first_name, 
                   p.last_name as patient_last_name, 
                   d.name as doctor_name
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ");
        $stmt->execute();
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // تسجيل البيانات قبل المعالجة
        error_log("Raw appointments data: " . print_r($appointments, true));

        // تحويل الأسماء إلى الأسماء المستخدمة في الواجهة الأمامية
        foreach ($appointments as &$appointment) {
            $appointment['patient_first_name'] = $appointment['patient_first_name'] ?? '';
            $appointment['patient_last_name'] = $appointment['patient_last_name'] ?? '';
        }

        // تسجيل البيانات بعد المعالجة
        error_log("Processed appointments data: " . print_r($appointments, true));

        echo json_encode([
            'success' => true,
            'data' => $appointments
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
        ]);
    }
}

function getAppointmentsByPatient($patient_id) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT a.*, p.first_name, p.last_name, d.name as doctor_name
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            WHERE a.patient_id = ?
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ");
        $stmt->execute([$patient_id]);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $appointments
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
        ]);
    }
}
?> 