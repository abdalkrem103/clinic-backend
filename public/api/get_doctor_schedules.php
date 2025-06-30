<?php
require_once 'cors.php';
require_once 'config.php';

header('Content-Type: application/json');

// تفعيل التحقق من المصادقة
checkAuth();

try {
    $doctorId = isset($_GET['doctor_id']) ? $_GET['doctor_id'] : null;
    $weekStart = isset($_GET['week_start']) ? $_GET['week_start'] : null;
    
    $sql = "
        SELECT 
            ds.*,
            d.name as doctor_name,
            d.specialty as doctor_specialty
        FROM doctor_schedules ds
        LEFT JOIN doctors d ON ds.doctor_id = d.id
        WHERE 1=1
    ";
    $params = [];

    if ($doctorId) {
        $sql .= " AND ds.doctor_id = ?";
        $params[] = $doctorId;
    }
    if ($weekStart) {
        $sql .= " AND ds.week_start = ?";
        $params[] = $weekStart;
    }

    $sql .= " ORDER BY ds.week_start DESC, ds.day_of_week ASC, ds.start_time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $schedules]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}
?> 