<?php
require_once 'cors.php';
require_once 'config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'طريقة طلب غير صحيحة']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

error_log("login.php: Raw POST data received: " . file_get_contents('php://input'));
error_log("login.php: Decoded data: " . print_r($data, true));

if (!isset($data['username']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'بيانات غير مكتملة']);
    exit;
}

try {
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    error_log("login.php: Username: " . $username . ", Password: " . $password);

    if (!$username || !$password) throw new Exception('الرجاء إدخال اسم المستخدم وكلمة السر');

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    error_log("login.php: User data fetched: " . print_r($user, true));
    if ($user) {
        error_log("login.php: Hashed password from DB: " . $user['password']);
        $password_matches = password_verify($password, $user['password']);
        error_log("login.php: password_verify result: " . ($password_matches ? 'true' : 'false'));
        error_log("login.php: Hashed password for '123456' using password_hash(): " . password_hash('123456', PASSWORD_DEFAULT));
    } else {
        error_log("login.php: User with username " . $username . " not found in DB.");
    }

    if ($user && $password_matches) {
        $token = bin2hex(random_bytes(32));
        
        $stmt = $pdo->prepare("UPDATE users SET token = ?, last_login = NOW() WHERE id = ?");
        $stmt->execute([$token, $user['id']]);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['token'] = $token;

        unset($user['password']);

        echo json_encode([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'data' => [
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role']
                ],
                'token' => $token
            ]
        ]);
    } else {
        error_log("login.php: User not found for username: " . $username);
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة']);
    }
} catch (Exception $e) {
    error_log("login.php Error: " . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()]);
}