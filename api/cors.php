<?php
// السماح بالوصول من جميع النطاقات المطلوبة
$allowed_origins = [
    'http://localhost:3000',
    'http://localhost',
    'http://127.0.0.1:3000',
    'http://127.0.0.1',
    'https://clinic-management-frontend.vercel.app',
    'https://clinic-management.vercel.app'
];

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}

// السماح بالطرق المسموح بها
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

// السماح بالرؤوس المسموح بها
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');

// السماح بإرسال الكوكيز
header('Access-Control-Allow-Credentials: true');

// معالجة طلبات OPTIONS
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// التأكد من أن جميع الاستجابات تحتوي على رأس Content-Type
if (!headers_sent()) {
header('Content-Type: application/json; charset=UTF-8');
}
?> 