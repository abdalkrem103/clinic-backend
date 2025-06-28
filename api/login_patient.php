<?php
require_once 'config.php';
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $data = json_decode(file_get_contents('php://input'), true);

    error_log("login_patient.php: Raw POST data received: " . file_get_contents('php://input'));
    error_log("login_patient.php: Decoded data: " . print_r($data, true));

    if (!$data) throw new Exception('بيانات غير صالحة');
    $national_id = $data['national_id'] ?? '';
    $password = $data['password'] ?? '';

    error_log("login_patient.php: National ID: " . $national_id . ", Password: " . $password);

    if (!$national_id || !$password) throw new Exception('الرجاء إدخال الرقم الوطني وكلمة السر');

    // جلب المستخدم من جدول users (بالرقم الوطني والدور 'patient')
    $stmt = $conn->prepare('SELECT id, password FROM users WHERE username = ? AND role = ?');
    $role = 'patient';
    $stmt->bind_param('ss', $national_id, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        error_log("login_patient.php: User not found or role mismatch for national_id: " . $national_id);
        throw new Exception('الرقم الوطني أو كلمة السر غير صحيحة');
    }

    error_log("login_patient.php: User found. Hashed password from DB: " . $user['password']);

    // تحقق من كلمة السر
    if (!password_verify($password, $user['password'])) {
        error_log("login_patient.php: Password verification failed for national_id: " . $national_id);
        throw new Exception('الرقم الوطني أو كلمة السر غير صحيحة');
    }

    // جلب بيانات المريض المرتبطة من جدول patients
    $stmt = $conn->prepare('SELECT * FROM patients WHERE user_id = ?');
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient = $result->fetch_assoc();

    if (!$patient) {
        error_log("login_patient.php: Patient data not found for user_id: " . $user['id']);
        throw new Exception('لم يتم العثور على بيانات المريض');
    }

    // يمكنك هنا إنشاء توكن أو جلسة إذا أردت
    echo json_encode(['success' => true, 'message' => 'تم تسجيل الدخول بنجاح', 'patient' => $patient]);

} catch (Exception $e) {
    error_log("login_patient.php Error: " . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()]);
} 