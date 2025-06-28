<?php
require_once 'cors.php';
require_once 'config.php';

header('Content-Type: application/json');

// تفعيل التحقق من المصادقة
checkAuth();

try {
    // جلب قائمة المرضى مع معلومات إضافية
    $stmt = $pdo->query("
        SELECT p.*, 
               COUNT(a.id) as total_appointments,
               MAX(a.appointment_date) as last_appointment
        FROM patients p
        LEFT JOIN appointments a ON p.id = a.patient_id
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ");
    
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $patients
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
    ]);
}
?> 