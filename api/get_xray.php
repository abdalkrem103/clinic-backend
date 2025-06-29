<?php
require_once 'config.php';
require_once 'cors.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
try {
    // التحقق من وجود معرف الأشعة
    $id = $_GET['id'] ?? null;
    if (!$id) throw new Exception('معرف الأشعة مفقود|X-ray ID missing');

    // جلب بيانات الملف من قاعدة البيانات
    $stmt = $pdo->prepare("SELECT image_data FROM xrays WHERE id = ?");
    $stmt->execute([$id]);
    $xray = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$xray) throw new Exception('الأشعة غير موجودة|X-ray not found');

    // استخدام realpath للحصول على المسار المطلق لمجلد رفع الأشعات
    $uploadDir = realpath(__DIR__ . '/../uploads/xrays/');
    if (!$uploadDir) {
        throw new Exception('مسار تحميل الأشعات غير موجود|Uploads directory not found');
    }
    // بناء المسار الكامل للملف باستخدام DIRECTORY_SEPARATOR
    $path = $uploadDir . DIRECTORY_SEPARATOR . $xray['image_data'];
    error_log("Trying to read file from: " . $path);

    if (!file_exists($path)) throw new Exception('الملف غير موجود|Image file not found');

    // تحديد نوع MIME وإرسال الرؤوس اللازمة
   $mime = mime_content_type($path);
    error_log("MIME type: " . $mime);
    error_log("File size: " . filesize($path));
header("Content-Type: $mime");
header("Content-Disposition: inline; filename=\"" . basename($path) . "\"");
header("Content-Length: " . filesize($path));
header("Content-Length: " . filesize($path));

// نظف مخزن الإخراج إن وُجد أي بيانات زائدة
if (ob_get_length()) {
    ob_clean();
}
flush();

readfile($path);
    exit;
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success'   => false,
        'message'   => explode('|', $e->getMessage())[0],
        'message_en'=> explode('|', $e->getMessage())[1] ?? ''
    ]);
}
?>