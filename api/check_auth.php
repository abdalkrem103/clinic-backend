<?php
require_once 'cors.php';
require_once 'config.php';

// التحقق من وجود التوكن في الرأس
$headers = getallheaders();
$token = null;

if (isset($headers['Authorization'])) {
    $auth_header = $headers['Authorization'];
    if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        $token = $matches[1];
    }
}

if (!$token) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'لم يتم توفير توكن المصادقة'
    ]);
    exit;
}

try {
    // التحقق من صحة التوكن
    $stmt = $conn->prepare("SELECT * FROM users WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // إزالة البيانات الحساسة
        unset($user['password']);
        unset($user['token']);
        
        echo json_encode([
            'success' => true,
            'message' => 'المصادقة ناجحة',
            'data' => [
                'user' => $user
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'توكن غير صالح'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ أثناء التحقق من المصادقة'
    ]);
}
?> 