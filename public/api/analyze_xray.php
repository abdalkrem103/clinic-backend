<?php
require_once 'cors.php';
require_once 'config.php';
// require_once 'auth.php'; // تم التعليق عليه مؤقتاً

header('Content-Type: application/json');

// تفعيل عرض الأخطاء للتطوير
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function isAuthenticated() { return true; }

// التحقق من المصادقة
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'غير مصرح',
        'message_en' => 'Unauthorized'
    ]);
    exit;
}

// التحقق من أن الطلب هو POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'طريقة طلب غير صحيحة',
        'message_en' => 'Method not allowed'
    ]);
    exit;
}

try {
    // الحصول على معرف الأشعة من الطلب
    $data = json_decode(file_get_contents('php://input'), true);
    $xray_id = isset($data['xray_id']) ? intval($data['xray_id']) : 0;

    if ($xray_id <= 0) {
        throw new Exception('معرف الأشعة غير صالح');
    }

    // التحقق من وجود الأشعة في قاعدة البيانات
    $stmt = $conn->prepare("SELECT * FROM xrays WHERE id = ?");
    $stmt->bind_param("i", $xray_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $xray = $result->fetch_assoc();

    if (!$xray) {
        throw new Exception('الأشعة غير موجودة');
    }

    // التحقق من وجود الصورة
    if (empty($xray['image_data'])) {
        throw new Exception('صورة الأشعة غير موجودة');
    }

    // جلب مسار الصورة بنفس منطق get_xray.php
    $uploadDir = __DIR__ . '/../uploads/xrays/';
    $image_path = $uploadDir . $xray['image_data'];
    error_log('Trying to read file from: ' . $image_path);
    if (!file_exists($image_path)) {
        error_log('File does NOT exist: ' . $image_path);
        error_log('Files in dir: ' . print_r(scandir($uploadDir), true));
        throw new Exception('صورة الأشعة غير موجودة');
    }

    error_log('DB value: ' . $xray['image_data']);

    $image_data = file_get_contents($image_path);
    if ($image_data === false) {
        error_log('Failed to read file: ' . $image_path);
        throw new Exception('فشل في قراءة ملف الصورة');
    }
    error_log('File read successfully, size: ' . strlen($image_data));

    $base64_image = base64_encode($image_data);
    error_log('Base64 image length: ' . strlen($base64_image));

    // إعداد طلب cURL إلى خادم Python
    $ch = curl_init('http://localhost:5000/analyze');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'image' => $base64_image
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    error_log('Python response: ' . $response);

    if ($response === false) {
        $error = curl_error($ch);
        error_log('cURL Error: ' . $error);
        throw new Exception('خطأ في الاتصال بخادم التحليل: ' . $error);
    }
    curl_close($ch);

    // التحقق من رمز الاستجابة
    if ($http_code !== 200) {
        error_log("Python server returned HTTP code: " . $http_code . "\nResponse: " . $response);
        throw new Exception('خطأ في استجابة خادم التحليل: ' . $http_code);
    }

    // تحليل الاستجابة
    $analysis_result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg() . "\nResponse: " . $response);
        throw new Exception('خطأ في تحليل استجابة خادم التحليل');
    }

    // حفظ نتائج التحليل في قاعدة البيانات
    $analysis_json = json_encode($analysis_result);
    $stmt = $conn->prepare("UPDATE xrays SET analysis_result = ?, analysis_date = NOW() WHERE id = ?");
    $stmt->bind_param("si", $analysis_json, $xray_id);
    
    if (!$stmt->execute()) {
        error_log("Database update error: " . $stmt->error);
        throw new Exception('خطأ في حفظ نتائج التحليل');
    }

    // إرجاع النتائج
    echo json_encode([
        'success' => true,
        'message' => 'تم تحليل الأشعة بنجاح',
        'message_en' => 'X-ray analysis completed successfully',
        'analysis_result' => $analysis_result
    ]);

} catch (Exception $e) {
    error_log("Error in analyze_xray.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في تحليل الأشعة: ' . $e->getMessage(),
        'message_en' => 'Error analyzing x-ray: ' . $e->getMessage()
    ]);
}

function analyze_xray_locally($base64_image) {
    // تحليل بسيط للصورة
    $image_data = base64_decode($base64_image);
    $image_info = getimagesizefromstring($image_data);
    
    if ($image_info === false) {
        throw new Exception('صورة غير صالحة');
    }

    // تحديد نوع التحليل (عظام أو أسنان) بناءً على حجم الصورة
    $is_dental = $image_info[0] < $image_info[1]; // إذا كان العرض أقل من الارتفاع، فهي أشعة أسنان
    
    // قائمة الحالات المحتملة
    $conditions = [
        'dental' => [
            'طبيعي',
            'تسوس الأسنان',
            'تسوس عميق',
            'أسنان مطمورة',
            'أورام قاعدية',
            'التهاب اللثة',
            'خراج سني',
            'كسور في الأسنان',
            'تراكم الجير',
            'مشاكل في جذور الأسنان'
        ],
        'bone' => [
            'طبيعي',
            'كسور',
            'هشاشة العظام',
            'التهاب المفاصل',
            'أورام العظام',
            'التهاب العظام',
            'تشوهات العظام',
            'تكلس العظام'
        ]
    ];

    // اختيار حالة عشوائية
    $condition_type = $is_dental ? 'dental' : 'bone';
    $condition = $conditions[$condition_type][array_rand($conditions[$condition_type])];
    
    // حساب نسبة الثقة (قيمة عشوائية بين 60 و 95)
    $confidence = rand(60, 95);
    
    // تحديد مستوى الخطورة
    if ($condition === 'طبيعي') {
        $severity = 'منخفض';
    } elseif ($confidence > 85) {
        $severity = 'مرتفع جداً';
    } elseif ($confidence > 75) {
        $severity = 'مرتفع';
    } else {
        $severity = 'متوسط';
    }

    // إنشاء التقرير
    return [
        'report_id' => 'XR-' . date('YmdHis'),
        'timestamp' => date('c'),
        'primary_findings' => [
            'status' => $condition,
            'confidence' => $confidence,
            'severity_assessment' => $severity
        ],
        'recommendations' => [
            'immediate_actions' => [
                'مراجعة الطبيب المختص',
                'إجراء الفحوصات اللازمة'
            ],
            'follow_up' => [
                'متابعة دورية كل ' . ($severity === 'منخفض' ? '3 أشهر' : ($severity === 'متوسط' ? 'شهر' : 'أسبوع')),
                'إجراء فحوصات دورية'
            ],
            'prevention' => [
                'الحفاظ على نظافة الفم',
                'تجنب الأطعمة الضارة'
            ]
        ],
        'follow_up_plan' => [
            'follow_up_date' => date('c', strtotime('+30 days')),
            'priority' => $severity === 'منخفض' ? 1 : ($severity === 'متوسط' ? 2 : ($severity === 'مرتفع' ? 3 : 4))
        ],
        'metadata' => [
            'image_quality' => [
                'overall_quality' => 'جيدة',
                'brightness' => 'مناسبة',
                'contrast' => 'جيد'
            ]
        ]
    ];
}
?> 