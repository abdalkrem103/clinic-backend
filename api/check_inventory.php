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

$itemId = isset($_GET['id']) ? $_GET['id'] : null;

try {
    if ($itemId) {
        // التحقق من عنصر محدد
        $stmt = $pdo->prepare("
            SELECT 
                i.*,
                c.name as category_name,
                s.name as supplier_name
            FROM inventory i
            LEFT JOIN inventory_categories c ON i.category_id = c.id
            LEFT JOIN suppliers s ON i.supplier_id = s.id
            WHERE i.id = ?
        ");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'العنصر غير موجود']);
            exit;
        }

        // التحقق من حالة المخزون
        $status = 'sufficient';
        if ($item['quantity'] <= $item['minimum_quantity']) {
            $status = 'low';
        }
        if ($item['quantity'] == 0) {
            $status = 'out_of_stock';
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'item' => $item,
                'status' => $status,
                'needs_restock' => $status === 'low' || $status === 'out_of_stock'
            ]
        ]);
    } else {
        // التحقق من جميع العناصر
        $stmt = $pdo->prepare("
            SELECT 
                i.*,
                c.name as category_name,
                s.name as supplier_name
            FROM inventory i
            LEFT JOIN inventory_categories c ON i.category_id = c.id
            LEFT JOIN suppliers s ON i.supplier_id = s.id
            WHERE i.quantity <= i.minimum_quantity
            ORDER BY i.quantity ASC
        ");
        $stmt->execute();
        $lowStockItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => [
                'low_stock_items' => $lowStockItems,
                'total_low_stock' => count($lowStockItems)
            ]
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطأ في التحقق من المخزون: ' . $e->getMessage()]);
}
?> 