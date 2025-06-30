<?php

// تفعيل عرض الأخطاء لأغراض التطوير
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// بدء الجلسة في بداية التنفيذ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'cors.php';

// قراءة بيانات الاتصال من متغيرات البيئة Railway
// (مع قيم افتراضية للاستخدام المحلي)
define('DB_HOST', getenv('MYSQLHOST') ?: 'localhost');
define('DB_NAME', getenv('MYSQL_DATABASE') ?: 'clinic_management');
define('DB_USER', getenv('MYSQLUSER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');

// التحقق من وجود بيانات الاتصال
if (!DB_HOST || !DB_NAME || !DB_USER) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'بيانات الاتصال بقاعدة البيانات غير مكتملة في متغيرات البيئة.']);
    exit();
}

// الاتصال بقاعدة البيانات (mysqli)
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'فشل الاتصال بقاعدة البيانات: ' . $conn->connect_error]);
    exit();
}

// الاتصال بقاعدة البيانات (PDO)
$pdo = null;
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// Authentication check function
function checkAuth() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'غير مصرح']);
        exit;
    }

    // التحقق من صحة التوكن
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT token FROM users WHERE id = ? AND token = ?");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['token']]);
        if (!$stmt->fetch()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'جلسة غير صالحة']);
            exit;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في التحقق من المصادقة']);
        exit;
    }
}

// Role check function
function checkRole($requiredRole) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $requiredRole) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'غير مصرح بالدور المطلوب']);
        exit;
    }
}
?> 