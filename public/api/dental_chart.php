<?php
require_once 'cors.php';
require_once 'config.php';

header('Content-Type: application/json');

// التحقق من المصادقة باستخدام التوكن
$headers = getallheaders();
$token = null;

if (isset($headers['Authorization'])) {
    $auth_header = $headers['Authorization'];
    if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        $token = $matches[1];
    }
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'لم يتم توفير توكن المصادقة']);
    exit;
}

// التحقق من صحة التوكن
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'توكن غير صالح']);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطأ في التحقق من المصادقة: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// معالجة طلبات PUT و DELETE
if ($method === 'PUT' || $method === 'DELETE') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'بيانات JSON غير صحيحة: ' . json_last_error_msg()]);
        exit;
    }
}

switch ($method) {
    case 'GET':
        try {
            $patientId = isset($_GET['patient_id']) ? $_GET['patient_id'] : null;
            
            if (!$patientId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'معرف المريض مطلوب']);
                exit;
            }

            $stmt = $pdo->prepare("
                SELECT * FROM dental_chart 
                WHERE patient_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$patientId]);
            $chartData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $chartData
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
            ]);
        }
        break;

    case 'POST':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['patient_id']) || !isset($data['tooth_id']) || !isset($data['note'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'جميع الحقول مطلوبة']);
                exit;
            }

            $status = isset($data['status']) ? $data['status'] : null;
            $serviceId = isset($data['service_id']) ? $data['service_id'] : null;

            // Check if status column exists
            $checkColumnStmt = $pdo->prepare("SHOW COLUMNS FROM dental_chart LIKE 'status'");
            $checkColumnStmt->execute();
            $statusColumnExists = $checkColumnStmt->rowCount() > 0;

            // Check if service_id column exists
            $checkServiceColumnStmt = $pdo->prepare("SHOW COLUMNS FROM dental_chart LIKE 'service_id'");
            $checkServiceColumnStmt->execute();
            $serviceColumnExists = $checkServiceColumnStmt->rowCount() > 0;

            if ($statusColumnExists && $serviceColumnExists) {
                $stmt = $pdo->prepare("
                    INSERT INTO dental_chart (
                        patient_id, tooth_id, note, status, service_id, created_at
                    ) VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $data['patient_id'],
                    $data['tooth_id'],
                    $data['note'],
                    $status,
                    $serviceId
                ]);
            } else if ($statusColumnExists) {
                $stmt = $pdo->prepare("
                    INSERT INTO dental_chart (
                        patient_id, tooth_id, note, status, created_at
                    ) VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $data['patient_id'],
                    $data['tooth_id'],
                    $data['note'],
                    $status
                ]);
            } else if ($serviceColumnExists) {
                $stmt = $pdo->prepare("
                    INSERT INTO dental_chart (
                        patient_id, tooth_id, note, service_id, created_at
                    ) VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $data['patient_id'],
                    $data['tooth_id'],
                    $data['note'],
                    $serviceId
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO dental_chart (
                        patient_id, tooth_id, note, created_at
                    ) VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $data['patient_id'],
                    $data['tooth_id'],
                    $data['note']
                ]);
            }

            $chartId = $pdo->lastInsertId();
            
            // إضافة إشعار للمريض
            try {
                $notificationStmt = $pdo->prepare("
                    INSERT INTO notifications (
                        title, message, type, user_id, created_at
                    ) VALUES (?, ?, 'system', ?, NOW())
                ");
                
                $notificationStmt->execute([
                    'تحديث خريطة الأسنان',
                    'تم إضافة ملاحظة جديدة على السن رقم ' . $data['tooth_id'],
                    $data['patient_id']
                ]);
            } catch (PDOException $e) {
                // تجاهل خطأ الإشعارات إذا فشل
                error_log('Failed to create notification: ' . $e->getMessage());
            }

            echo json_encode([
                'success' => true,
                'message' => 'تم إضافة الملاحظة بنجاح',
                'data' => ['id' => $chartId]
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'خطأ في إضافة الملاحظة: ' . $e->getMessage()
            ]);
        }
        break;

    case 'PUT':
        try {
            if (!isset($data['id']) || !isset($data['note'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'معرف الملاحظة والنص مطلوبان']);
                exit;
            }
            $status = isset($data['status']) ? $data['status'] : null;
            $serviceId = isset($data['service_id']) ? $data['service_id'] : null;

            // Check if status column exists
            $checkColumnStmt = $pdo->prepare("SHOW COLUMNS FROM dental_chart LIKE 'status'");
            $checkColumnStmt->execute();
            $statusColumnExists = $checkColumnStmt->rowCount() > 0;

            // Check if service_id column exists
            $checkServiceColumnStmt = $pdo->prepare("SHOW COLUMNS FROM dental_chart LIKE 'service_id'");
            $checkServiceColumnStmt->execute();
            $serviceColumnExists = $checkServiceColumnStmt->rowCount() > 0;

            if ($statusColumnExists && $serviceColumnExists) {
                $stmt = $pdo->prepare("
                    UPDATE dental_chart 
                    SET note = ?, status = ?, service_id = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$data['note'], $status, $serviceId, $data['id']]);
            } else if ($statusColumnExists) {
                $stmt = $pdo->prepare("
                    UPDATE dental_chart 
                    SET note = ?, status = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$data['note'], $status, $data['id']]);
            } else if ($serviceColumnExists) {
                $stmt = $pdo->prepare("
                    UPDATE dental_chart 
                    SET note = ?, service_id = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$data['note'], $serviceId, $data['id']]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE dental_chart 
                    SET note = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$data['note'], $data['id']]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'تم تحديث الملاحظة بنجاح'
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'خطأ في تحديث الملاحظة: ' . $e->getMessage()
            ]);
        }
        break;

    case 'DELETE':
        try {
            if (!isset($data['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'معرف الملاحظة مطلوب']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM dental_chart WHERE id = ?");
            
            if ($stmt->execute([$data['id']])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'تم حذف الملاحظة بنجاح'
                ]);
            } else {
                throw new PDOException('فشل في حذف الملاحظة');
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'خطأ في حذف الملاحظة: ' . $e->getMessage()
            ]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'طريقة طلب غير مدعومة']);
        break;
}
?> 