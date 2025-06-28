<?php
require_once 'cors.php';
require_once 'config.php';
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $patientId = isset($_GET['patient_id']) ? $_GET['patient_id'] : null;

            if ($patientId) {
                $stmt = $pdo->prepare("SELECT * FROM xrays WHERE patient_id = ? ORDER BY created_at DESC");
                $stmt->execute([$patientId]);
            } else {
                $stmt = $pdo->query("SELECT * FROM xrays ORDER BY created_at DESC");
            }

            $xrays = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $xrays]);
            break;

        case 'POST':
            if (!isset($_FILES['xray_file']) || !is_uploaded_file($_FILES['xray_file']['tmp_name'])) {
                throw new Exception('ملف الأشعة غير موجود|X-ray file not found');
            }

            $patientId = $_POST['patient_id'] ?? null;
            if (!$patientId) {
                throw new Exception('معرف المريض مفقود|Patient ID is missing');
            }

            $stmt = $pdo->prepare("SELECT id FROM patients WHERE id = ?");
            $stmt->execute([$patientId]);
            if (!$stmt->fetch()) {
                throw new Exception('المريض غير موجود|Patient not found');
            }

            $file = $_FILES['xray_file'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('نوع غير مدعوم|Unsupported file type');
            }

            if ($file['size'] > 10 * 1024 * 1024) {
                throw new Exception('الملف كبير جداً|File too large');
            }

            // تحديد مجلد رفع الأشعة
            $uploadDir = __DIR__ . '/../uploads/xrays/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            // تحويل المجلد إلى مسار مطلق
            $uploadDir = realpath($uploadDir);
            if (!$uploadDir) {
                throw new Exception('فشل تحديد مسار تحميل الأشعات|Unable to resolve uploads directory path');
            }

            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '_' . time() . '.' . $ext;
            $filePath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                throw new Exception('فشل حفظ الملف|Failed to move file');
            }

            $stmt = $pdo->prepare("INSERT INTO xrays (patient_id, image_data, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$patientId, $fileName]);
            $xrayId = $pdo->lastInsertId();

            echo json_encode([
                'success' => true,
                'data' => ['id' => $xrayId, 'image_data' => $fileName]
            ]);
            break;

        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? null;
            if (!$id) {
                throw new Exception('معرف مفقود|X-ray ID missing');
            }

            $stmt = $pdo->prepare("SELECT image_data FROM xrays WHERE id = ?");
            $stmt->execute([$id]);
            $xray = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$xray) {
                throw new Exception('الأشعة غير موجودة|X-ray not found');
            }

            // استخدام realpath لتحديد المجلد المطلق
            $uploadDir = realpath(__DIR__ . '/../uploads/xrays/');
            if (!$uploadDir) {
                throw new Exception('مسار تحميل الأشعات غير موجود|Uploads directory not found');
            }
            $filePath = $uploadDir . DIRECTORY_SEPARATOR . $xray['image_data'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $stmt = $pdo->prepare("DELETE FROM xrays WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['success' => true]);
            break;

        default:
            throw new Exception('طريقة طلب غير مدعومة|Unsupported request method');
    }
} catch (Exception $e) {
    http_response_code(400);
    list($ar, $en) = explode('|', $e->getMessage() . '|');
    echo json_encode(['success' => false, 'message' => $ar, 'message_en' => $en ?: $ar]);
}
?>