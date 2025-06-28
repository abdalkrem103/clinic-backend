<?php
require_once 'cors.php';
require_once 'config.php';

header('Content-Type: application/json');

// تفعيل التحقق من المصادقة
checkAuth();

try {
    // جلب الإشعارات من قاعدة البيانات
    $stmt = $pdo->query("
        SELECT * FROM notifications 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $notifications
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ أثناء جلب الإشعارات: ' . $e->getMessage()
    ]);
}
?> 