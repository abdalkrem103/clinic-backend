<?php
require_once 'cors.php';
require_once 'config.php';
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['user_id'])) {
            getNotifications($_GET['user_id']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'يجب تحديد معرف المستخدم']);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (isset($data['action'])) {
            switch ($data['action']) {
                case 'saveSettings':
                    if (isset($data['user_id']) && isset($data['settings'])) {
                        saveSettings($data['user_id'], $data['settings']);
                    } else {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'البيانات المطلوبة غير مكتملة لحفظ الإعدادات']);
                    }
                    break;
                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'إجراء غير صالح']);
                    break;
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'يجب تحديد الإجراء المطلوب']);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (isset($data['action'])) {
            switch ($data['action']) {
                case 'markAsRead':
                    if (isset($data['notification_id'])) {
                        markAsRead($data['notification_id']);
                    } else {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'يجب تحديد معرف الإشعار']);
                    }
                    break;

                case 'markAllAsRead':
                    if (isset($data['user_id'])) {
                        markAllAsRead($data['user_id']);
                } else {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'يجب تحديد معرف المستخدم']);
                }
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'إجراء غير صالح']);
                    break;
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'يجب تحديد الإجراء المطلوب']);
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (isset($data['action']) && $data['action'] === 'deleteNotification' && isset($data['notification_id'])) {
            deleteNotification($data['notification_id']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'يجب تحديد معرف الإشعار للحذف']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'طريقة طلب غير مدعومة']);
        break;
}

function getNotifications($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? OR user_id IS NULL
            ORDER BY created_at DESC 
            LIMIT 50
        ");
        $stmt->execute([$user_id]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // جلب إعدادات الإشعارات للمستخدم
        $settingsStmt = $pdo->prepare("
            SELECT setting_name, setting_value FROM user_notification_settings
            WHERE user_id = ?
        ");
        $settingsStmt->execute([$user_id]);
        $dbSettings = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // إعدادات افتراضية
        $defaultSettings = [
            'email_notifications' => true,
            'sms_notifications' => false,
            'appointment_reminders' => true,
            'inventory_alerts' => true,
            'payment_reminders' => true
        ];
        
        // دمج الإعدادات من قاعدة البيانات مع الإعدادات الافتراضية
        $userSettings = array_merge($defaultSettings, array_map(function($value) { return (bool)$value; }, $dbSettings));

        echo json_encode([
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'settings' => $userSettings
            ]
        ]);
             } catch (PDOException $e) {
                 http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
        ]);
             }
}

function saveSettings($user_id, $settings) {
    global $pdo;
    try {
        $pdo->beginTransaction();

        foreach ($settings as $name => $value) {
            $stmt = $pdo->prepare("
                INSERT INTO user_notification_settings (user_id, setting_name, setting_value)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE setting_value = ?
            ");
            $stmt->execute([
                $user_id,
                $name,
                (int)$value, // تحويل القيمة البوليانية إلى عدد صحيح (0 أو 1)
                (int)$value
            ]);
        }
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'تم حفظ الإعدادات بنجاح']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في حفظ الإعدادات: ' . $e->getMessage()]);
    }
}

function markAsRead($notification_id) {
    global $pdo;
    
             try {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$notification_id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'تم تحديث حالة الإشعار بنجاح'
            ]);
                 } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'الإشعار غير موجود'
            ]);
                 }
             } catch (PDOException $e) {
                 http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
        ]);
             }
}

function markAllAsRead($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1, updated_at = NOW()
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$user_id]);

        echo json_encode([
            'success' => true,
            'message' => 'تم تحديث حالة جميع الإشعارات بنجاح'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
        ]);
    }
}

function deleteNotification($notification_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
        $stmt->execute([$notification_id]);

                     if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'تم حذف الإشعار بنجاح'
            ]);
                     } else {
                         http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'الإشعار غير موجود'
            ]);
                 }
             } catch (PDOException $e) {
                 http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
        ]);
         }
}
?> 