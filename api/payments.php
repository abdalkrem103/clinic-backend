<?php
require_once 'cors.php';
require_once 'config.php';

header('Content-Type: application/json');

// تفعيل عرض الأخطاء للتطوير
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// معالجة الأخطاء العامة
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// تفعيل التحقق من المصادقة
checkAuth();

// إنشاء جدول payment_services إذا لم يكن موجوداً
try {
    $createTableSql = "
        CREATE TABLE IF NOT EXISTS payment_services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            payment_id INT NOT NULL,
            service_id INT NOT NULL,
            service_name VARCHAR(255) NOT NULL,
            service_price DECIMAL(10,2) NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            dental_chart_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ";
    $pdo->exec($createTableSql);
} catch (PDOException $e) {
    error_log("خطأ في إنشاء جدول payment_services: " . $e->getMessage());
}

// التأكد من وجود جميع الأعمدة المطلوبة في جدول payments
try {
    // التحقق من وجود الأعمدة وإضافتها إذا لم تكن موجودة
    $columns = [
        'payment_status' => "VARCHAR(50) DEFAULT 'paid'",
        'remaining_amount' => 'DECIMAL(10,2) DEFAULT 0',
        'transaction_id' => 'VARCHAR(255) NULL',
        'payment_gateway' => 'VARCHAR(100) NULL',
        'payment_url' => 'TEXT NULL'
    ];
    
    foreach ($columns as $columnName => $columnDefinition) {
        $checkColumnSql = "SHOW COLUMNS FROM payments LIKE '$columnName'";
        $checkStmt = $pdo->prepare($checkColumnSql);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() == 0) {
            $addColumnSql = "ALTER TABLE payments ADD COLUMN $columnName $columnDefinition";
            $pdo->exec($addColumnSql);
        }
    }
} catch (PDOException $e) {
    error_log("خطأ في تحديث جدول payments: " . $e->getMessage());
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            $patientId = isset($_GET['patient_id']) ? $_GET['patient_id'] : null;
            $paymentId = isset($_GET['id']) ? $_GET['id'] : null;
            
            if ($paymentId) {
                // جلب دفعة محددة مع خدماتها
                $sql = "
                    SELECT 
                        p.*,
                        pt.first_name as patient_first_name,
                        pt.last_name as patient_last_name
                    FROM payments p
                    LEFT JOIN patients pt ON p.patient_id = pt.id
                    WHERE p.id = ?
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$paymentId]);
                $payment = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($payment) {
                    // جلب الخدمات المرتبطة بالدفعة
                    $servicesSql = "
                        SELECT * FROM payment_services 
                        WHERE payment_id = ?
                        ORDER BY id ASC
                    ";
                    $servicesStmt = $pdo->prepare($servicesSql);
                    $servicesStmt->execute([$paymentId]);
                    $services = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);

                    $payment['services'] = $services;
                }

                echo json_encode(['success' => true, 'data' => $payment]);
            } else {
                // جلب جميع الدفعات
            $sql = "
                SELECT 
                    p.*,
                    pt.first_name as patient_first_name,
                    pt.last_name as patient_last_name
                FROM payments p
                LEFT JOIN patients pt ON p.patient_id = pt.id
                WHERE 1=1
            ";
            $params = [];

            if ($patientId) {
                $sql .= " AND p.patient_id = ?";
                $params[] = $patientId;
            }

                $sql .= " ORDER BY p.payment_date DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // جلب الخدمات لكل دفعة
                foreach ($payments as &$payment) {
                    $servicesSql = "
                        SELECT * FROM payment_services 
                        WHERE payment_id = ?
                        ORDER BY id ASC
                    ";
                    $servicesStmt = $pdo->prepare($servicesSql);
                    $servicesStmt->execute([$payment['id']]);
                    $payment['services'] = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);
                }

            echo json_encode(['success' => true, 'data' => $payments]);
            }
        } catch (PDOException $e) {
            error_log("خطأ في قاعدة البيانات في payments.php: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'خطأ في قاعدة البيانات',
                'debug' => $e->getMessage()
            ]);
        }
        break;

    case 'POST':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['patient_id']) || !isset($data['total_amount']) || !isset($data['paid_amount'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'البيانات المطلوبة غير مكتملة']);
                exit;
            }

            // بدء المعاملة
            $pdo->beginTransaction();

            try {
                // إدخال الدفعة الرئيسية
            $sql = "
                INSERT INTO payments (
                    patient_id, total_amount, paid_amount, 
                        payment_method, notes, payment_date
                ) VALUES (?, ?, ?, ?, ?, NOW())
            ";

            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([
                $data['patient_id'],
                $data['total_amount'],
                $data['paid_amount'],
                $data['payment_method'] ?? 'cash',
                $data['notes'] ?? null
            ])) {
                $paymentId = $pdo->lastInsertId();

                    // إضافة الخدمات المرتبطة بالدفعة
                    if (isset($data['services']) && is_array($data['services'])) {
                        $serviceSql = "
                            INSERT INTO payment_services (
                                payment_id, service_id, service_name, 
                                service_price, quantity, dental_chart_id
                            ) VALUES (?, ?, ?, ?, ?, ?)
                        ";
                        $serviceStmt = $pdo->prepare($serviceSql);

                        foreach ($data['services'] as $service) {
                            $serviceStmt->execute([
                                $paymentId,
                                $service['service_id'],
                                $service['service_name'],
                                $service['service_price'],
                                $service['quantity'],
                                $service['dental_chart_id']
                            ]);
                        }
                    }

                    // تأكيد المعاملة
                    $pdo->commit();

                echo json_encode([
                    'success' => true,
                        'message' => 'تم إضافة الدفعة بنجاح',
                    'data' => ['id' => $paymentId]
                ]);
            } else {
                    throw new PDOException('فشل في إضافة الدفعة');
            }
            } catch (Exception $e) {
                // التراجع عن المعاملة في حالة الخطأ
                $pdo->rollBack();
                throw $e;
            }

            file_put_contents('php_custom_error.log', print_r($data, true), FILE_APPEND);
        } catch (PDOException $e) {
            error_log("خطأ في payments.php POST: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'خطأ في إضافة الدفعة',
                'debug' => $e->getMessage()
            ]);
        }
        break;

    case 'PUT':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'معرف المدفوعات مطلوب']);
                exit;
            }

            $sql = "
                UPDATE payments SET 
                    total_amount = ?,
                    paid_amount = ?,
                    payment_method = ?,
                    notes = ?
                WHERE id = ?
            ";

            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([
                $data['total_amount'],
                $data['paid_amount'],
                $data['payment_method'] ?? 'cash',
                $data['notes'] ?? null,
                $data['id']
            ])) {
                echo json_encode(['success' => true, 'message' => 'تم تحديث المدفوعات بنجاح']);
            } else {
                throw new PDOException('فشل في تحديث المدفوعات');
            }
        } catch (PDOException $e) {
            error_log("خطأ في payments.php PUT: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'خطأ في تحديث المدفوعات',
                'debug' => $e->getMessage()
            ]);
        }
        break;

    case 'DELETE':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'معرف الدفعة مطلوب']);
                exit;
            }

            // بدء المعاملة
            $pdo->beginTransaction();

            try {
                // حذف الخدمات المرتبطة أولاً
                $servicesStmt = $pdo->prepare("DELETE FROM payment_services WHERE payment_id = ?");
                $servicesStmt->execute([$data['id']]);

                // ثم حذف الدفعة
            $stmt = $pdo->prepare("DELETE FROM payments WHERE id = ?");
            if ($stmt->execute([$data['id']])) {
                    // تأكيد المعاملة
                    $pdo->commit();
                    echo json_encode(['success' => true, 'message' => 'تم حذف الدفعة بنجاح']);
            } else {
                    throw new PDOException('فشل في حذف الدفعة');
                }
            } catch (Exception $e) {
                // التراجع عن المعاملة في حالة الخطأ
                $pdo->rollBack();
                throw $e;
            }
        } catch (PDOException $e) {
            error_log("خطأ في payments.php DELETE: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'خطأ في حذف الدفعة',
                'debug' => $e->getMessage()
            ]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'طريقة طلب غير مدعومة']);
        break;
}
?> 