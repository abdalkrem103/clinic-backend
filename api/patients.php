<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_custom_error.log');

require_once 'cors.php';
require_once 'config.php';
require_once 'email_sender.php';

header('Content-Type: application/json');

// تفعيل التحقق من المصادقة
// checkAuth(); // تم تعطيل التحقق من المصادقة مؤقتاً

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            $id = isset($_GET['id']) ? $_GET['id'] : null;
            $name = isset($_GET['name']) ? $_GET['name'] : null;
            $phone_number = isset($_GET['phone_number']) ? $_GET['phone_number'] : null;
            
            if ($id) {
                // جلب بيانات مريض محدد
                $sql = "
                    SELECT 
                        p.*,
                        COALESCE(SUM(pay.total_amount), 0) as total_amount,
                        COALESCE(SUM(pay.paid_amount), 0) as paid_amount
                    FROM patients p
                    LEFT JOIN payments pay ON p.id = pay.patient_id
                    WHERE p.id = ?
                    GROUP BY p.id
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $patient = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($patient) {
                    // حساب المبلغ المتبقي
                    $patient['remaining_amount'] = $patient['total_amount'] - $patient['paid_amount'];
                    echo json_encode(['success' => true, 'data' => $patient]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'المريض غير موجود']);
                }
            } else if ($name || $phone_number) {
                // جلب قائمة المرضى بناءً على معايير البحث (الاسم أو رقم الهاتف)
                $sql = "
                    SELECT 
                        p.*,
                        COALESCE(SUM(pay.total_amount), 0) as total_amount,
                        COALESCE(SUM(pay.paid_amount), 0) as paid_amount
                    FROM patients p
                    LEFT JOIN payments pay ON p.id = pay.patient_id
                    WHERE 1=1";
                
                $params = [];
                $conditions = [];
                if ($name) {
                    // استخدام LIKE للبحث الجزئي في الاسم الأول أو الأخير
                    $conditions[] = "p.first_name LIKE ? OR p.last_name LIKE ?";
                    $params[] = '%' . $name . '%';
                    $params[] = '%' . $name . '%';
                }
                if ($phone_number) {
                    // استخدام LIKE للبحث الجزئي في رقم الهاتف
                    $conditions[] = "p.phone_number LIKE ?";
                    $params[] = '%' . $phone_number . '%';
                }

                if (count($conditions) > 0) {
                    $sql .= " AND (" . implode(" OR ", $conditions) . ")";
                }

                $sql .= " GROUP BY p.id ORDER BY p.created_at DESC";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // حساب المبلغ المتبقي لكل مريض
                foreach ($patients as &$patient) {
                    $patient['remaining_amount'] = $patient['total_amount'] - $patient['paid_amount'];
                }

                echo json_encode(['success' => true, 'data' => $patients]);

            } else {
                // جلب قائمة المرضى
                $sql = "
                    SELECT 
                        p.*,
                        COALESCE(SUM(pay.total_amount), 0) as total_amount,
                        COALESCE(SUM(pay.paid_amount), 0) as paid_amount
                    FROM patients p
                    LEFT JOIN payments pay ON p.id = pay.patient_id
                    GROUP BY p.id
                    ORDER BY p.created_at DESC
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // حساب المبلغ المتبقي لكل مريض
                foreach ($patients as &$patient) {
                    $patient['remaining_amount'] = $patient['total_amount'] - $patient['paid_amount'];
                }

                echo json_encode(['success' => true, 'data' => $patients]);
            }
        } catch (PDOException $e) {
            error_log("خطأ في قاعدة البيانات في patients.php: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'خطأ في قاعدة البيانات',
                'debug' => $e->getMessage()
            ]);
        }
        break;

    case 'POST':
        $raw_post_data = file_get_contents('php://input');
        $data = json_decode($raw_post_data, true);

        if (isset($data['action'])) {
            switch ($data['action']) {
                case 'sendResetOtp':
                    error_log('Reached sendResetOtp: email=' . $data['email']);
                    sendResetOtp($data['email']);
                    break;
                case 'resetPassword':
                    resetPassword($data['email'], $data['otp'], $data['newPassword']);
                    break;
                // ... باقي الأكشنات ...
            }
        } else {
            try {
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (!isset($data['first_name']) || !isset($data['last_name']) || !isset($data['phone_number'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'البيانات المطلوبة غير مكتملة']);
                    exit;
                }

                // التحقق من عدم وجود مريض بنفس رقم الهاتف لتجنب التكرار
                $checkStmt = $pdo->prepare("SELECT id FROM patients WHERE phone_number = ?");
                $checkStmt->execute([$data['phone_number']]);
                if ($checkStmt->fetch()) {
                    http_response_code(409); // Conflict
                    echo json_encode(['success' => false, 'message' => 'مريض بنفس رقم الهاتف موجود بالفعل']);
                    exit;
                }

                $sql = "
                    INSERT INTO patients (
                        first_name, last_name, phone_number, email, 
                        address, date_of_birth, gender, blood_type, 
                        allergies, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ";

                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([
                    $data['first_name'],
                    $data['last_name'],
                    $data['phone_number'],
                    $data['email'] ?? null,
                    $data['address'] ?? null,
                    $data['date_of_birth'] ?? null,
                    $data['gender'] ?? null,
                    $data['blood_type'] ?? null,
                    $data['allergies'] ?? null,
                ])) {
                    $patientId = $pdo->lastInsertId();
                    echo json_encode([
                        'success' => true,
                        'message' => 'تم إضافة المريض بنجاح',
                        'data' => ['id' => $patientId]
                    ]);
                } else {
                    throw new PDOException('فشل في إضافة المريض');
                }
            } catch (PDOException $e) {
                error_log("خطأ في patients.php POST: " . $e->getMessage());
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'خطأ في إضافة المريض',
                    'debug' => $e->getMessage()
                ]);
            }
        }
        break;

    case 'PUT':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'معرف المريض مطلوب']);
                exit;
            }

            // التحقق من وجود المريض
            $checkStmt = $pdo->prepare("SELECT id FROM patients WHERE id = ?");
            $checkStmt->execute([$data['id']]);
            if (!$checkStmt->fetch()) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'المريض غير موجود']);
                exit;
            }

            $sql = "
                UPDATE patients SET 
                    first_name = ?,
                    last_name = ?,
                    phone_number = ?,
                    email = ?,
                    address = ?,
                    date_of_birth = ?,
                    gender = ?,
                    blood_type = ?,
                    allergies = ?
                WHERE id = ?
            ";

            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([
                $data['first_name'],
                $data['last_name'],
                $data['phone_number'],
                $data['email'] ?? null,
                $data['address'] ?? null,
                $data['date_of_birth'] ?? null,
                $data['gender'] ?? null,
                $data['blood_type'] ?? null,
                $data['allergies'] ?? null,
                $data['id']
            ])) {
                echo json_encode(['success' => true, 'message' => 'تم تحديث بيانات المريض بنجاح']);
            } else {
                throw new PDOException('فشل في تحديث بيانات المريض');
            }
        } catch (PDOException $e) {
            error_log("خطأ في patients.php PUT: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'خطأ في تحديث بيانات المريض',
                'debug' => $e->getMessage()
            ]);
        }
        break;

    case 'DELETE':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'معرف المريض مطلوب']);
                exit;
            }

            // التحقق من وجود مواعيد أو مدفوعات مرتبطة
            $checkStmt = $pdo->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM appointments WHERE patient_id = ?) as appointments_count,
                    (SELECT COUNT(*) FROM payments WHERE patient_id = ?) as payments_count,
                    (SELECT COUNT(*) FROM xrays WHERE patient_id = ?) as xrays_count
            ");
            $checkStmt->execute([$data['id'], $data['id'], $data['id']]);
            $counts = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($counts['appointments_count'] > 0 || $counts['payments_count'] > 0 || $counts['xrays_count'] > 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => 'لا يمكن حذف المريض لوجود مواعيد أو مدفوعات أو أشعة مرتبطة به'
                ]);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM patients WHERE id = ?");
            if ($stmt->execute([$data['id']])) {
                echo json_encode(['success' => true, 'message' => 'تم حذف المريض بنجاح']);
            } else {
                throw new PDOException('فشل في حذف المريض');
            }
        } catch (PDOException $e) {
            error_log("خطأ في patients.php DELETE: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'خطأ في حذف المريض',
                'debug' => $e->getMessage()
            ]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'طريقة طلب غير مدعومة']);
        break;
}

// دوال استعادة كلمة السر:
function sendResetOtp($email) {
    global $pdo;
    try {
        error_log('sendResetOtp: email=' . $email);
        // تحقق من وجود البريد
        $stmt = $pdo->prepare("SELECT id FROM patients WHERE email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        error_log('sendResetOtp: found=' . print_r($row, true));
        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'البريد الإلكتروني غير مسجل']);
            return;
        }
        // توليد كود عشوائي
        $otp = rand(100000, 999999);
        // حذف أكواد سابقة
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
        // حفظ الكود
        $pdo->prepare("INSERT INTO password_resets (email, otp_code) VALUES (?, ?)")->execute([$email, $otp]);
        // إرسال الكود للبريد
        $subject = "كود استعادة كلمة السر";
        $body = "كود التحقق الخاص بك هو: $otp\nصالح لمدة 10 دقائق فقط.";
        $result = sendEmail($email, $subject, $body);
        error_log('sendEmail result: ' . print_r($result, true));
        echo json_encode(['success' => true, 'message' => 'تم إرسال كود التحقق إلى بريدك الإلكتروني.']);
    } catch (Exception $e) {
        error_log('sendResetOtp Exception: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
    }
}

function resetPassword($email, $otp, $newPassword) {
    global $pdo;
    // تحقق من الكود وصلاحيته (10 دقائق)
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND otp_code = ? AND created_at >= (NOW() - INTERVAL 10 MINUTE)");
    $stmt->execute([$email, $otp]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'الكود غير صحيح أو منتهي الصلاحية']);
        return;
    }
    // جلب user_id المرتبط بالبريد
    $stmt = $pdo->prepare("SELECT user_id FROM patients WHERE email = ?");
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || !$row['user_id']) {
        echo json_encode(['success' => false, 'message' => 'لا يوجد حساب مستخدم مرتبط بهذا البريد']);
        return;
    }
    $user_id = $row['user_id'];
    // تحديث كلمة السر في جدول users
    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update->execute([$hashed, $user_id]);
    // حذف الكود بعد الاستخدام
    $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
    echo json_encode(['success' => true, 'message' => 'تم تغيير كلمة السر بنجاح!']);
}
