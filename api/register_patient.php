<?php
require_once 'config.php';
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) throw new Exception('بيانات غير صالحة');

    // التحقق من الحقول المطلوبة
    $required = ['first_name','last_name','national_id','phone_number','email','date_of_birth','gender','password'];
    foreach ($required as $field) {
        if (empty($data[$field])) throw new Exception('الرجاء تعبئة جميع الحقول المطلوبة');
    }
    $national_id = $data['national_id'];
    $password = $data['password'];

    // تحقق من عدم تكرار الرقم الوطني
    $stmt = $conn->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->bind_param('s', $national_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) throw new Exception('الرقم الوطني مستخدم مسبقاً');

    // تشفير كلمة السر
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // إنشاء المستخدم في جدول users
    $role = 'patient';
    $stmt = $conn->prepare('INSERT INTO users (username, password, role, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->bind_param('sss', $national_id, $hashed_password, $role);
    if (!$stmt->execute()) throw new Exception('خطأ في إنشاء المستخدم: ' . $stmt->error);
    $user_id = $stmt->insert_id;

    // إنشاء سجل المريض
    $stmt = $conn->prepare('INSERT INTO patients (user_id, first_name, last_name, phone_number, email, date_of_birth, gender, address, blood_type, allergies, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');

    $address = $data['address'] ?? null;
    $blood_type = $data['blood_type'] ?? null;
    $allergies = $data['allergies'] ?? null;

    $stmt->bind_param('isssssssss',
        $user_id,
        $data['first_name'],
        $data['last_name'],
        $data['phone_number'],
        $data['email'],
        $data['date_of_birth'],
        $data['gender'],
        $address,
        $blood_type,
        $allergies
    );
    if (!$stmt->execute()) throw new Exception('خطأ في إنشاء سجل المريض: ' . $stmt->error);

    // جلب بيانات المريض بعد الإنشاء لإرجاعها كاملة
    $stmt = $conn->prepare('SELECT * FROM patients WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient_data = $result->fetch_assoc();

    if (!$patient_data) throw new Exception('فشل في جلب بيانات المريض بعد التسجيل');

    echo json_encode(['success' => true, 'message' => 'تم التسجيل بنجاح', 'patient' => $patient_data]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()]);
} 