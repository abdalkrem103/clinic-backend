<?php
require_once 'config.php';

try {
    // تحديث جدول xrays
    $pdo->exec("
        ALTER TABLE xrays 
        ADD COLUMN IF NOT EXISTS created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ");

    // تحديث جدول payments
    $pdo->exec("
        ALTER TABLE payments 
        ADD COLUMN IF NOT EXISTS total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        ADD COLUMN IF NOT EXISTS paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) DEFAULT 'cash',
        ADD COLUMN IF NOT EXISTS notes TEXT,
        ADD COLUMN IF NOT EXISTS created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ");

    echo json_encode([
        'success' => true,
        'message' => 'تم تحديث هيكل قاعدة البيانات بنجاح'
    ]);
} catch (PDOException $e) {
    error_log("خطأ في تحديث قاعدة البيانات: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في تحديث قاعدة البيانات',
        'debug' => $e->getMessage()
    ]);
}
?> 