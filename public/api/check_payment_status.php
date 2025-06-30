<?php
require_once 'cors.php';
require_once 'config.php';

header('Content-Type: application/json');

// تفعيل التحقق من المصادقة
checkAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'طريقة طلب غير مدعومة']);
    exit;
}

$paymentId = isset($_GET['id']) ? $_GET['id'] : null;
$appointmentId = isset($_GET['appointment_id']) ? $_GET['appointment_id'] : null;

try {
    if ($paymentId) {
        // التحقق من دفعة محددة
        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                a.appointment_date,
                a.appointment_time,
                d.name as doctor_name,
                pt.first_name as patient_first_name,
                pt.last_name as patient_last_name
            FROM payments p
            LEFT JOIN appointments a ON p.appointment_id = a.id
            LEFT JOIN doctors d ON a.doctor_id = d.id
            LEFT JOIN patients pt ON a.patient_id = pt.id
            WHERE p.id = ?
        ");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'الدفعة غير موجودة']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'payment' => $payment,
                'status' => $payment['status'],
                'is_paid' => $payment['status'] === 'paid'
            ]
        ]);
    } elseif ($appointmentId) {
        // التحقق من مدفوعات موعد محدد
        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                a.appointment_date,
                a.appointment_time,
                d.name as doctor_name,
                pt.first_name as patient_first_name,
                pt.last_name as patient_last_name
            FROM payments p
            LEFT JOIN appointments a ON p.appointment_id = a.id
            LEFT JOIN doctors d ON a.doctor_id = d.id
            LEFT JOIN patients pt ON a.patient_id = pt.id
            WHERE p.appointment_id = ?
        ");
        $stmt->execute([$appointmentId]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalAmount = 0;
        $paidAmount = 0;
        foreach ($payments as $payment) {
            $totalAmount += $payment['amount'];
            if ($payment['status'] === 'paid') {
                $paidAmount += $payment['amount'];
            }
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'payments' => $payments,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $totalAmount - $paidAmount,
                'is_fully_paid' => $totalAmount === $paidAmount
            ]
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'معرف الدفعة أو الموعد مطلوب']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطأ في التحقق من حالة المدفوعات: ' . $e->getMessage()]);
}
?> 