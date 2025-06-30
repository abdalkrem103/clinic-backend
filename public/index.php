<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = ltrim($path, '/');

if (empty($path)) {
    echo json_encode(['status' => 'success', 'message' => 'Clinic Management API']);
    exit();
}

if (strpos($path, 'api/') === 0) {
    $api_path = substr($path, 4); // إزالة 'api/' من البداية
    $api_file = __DIR__ . '/../api/' . $api_path;
    if (file_exists($api_file)) {
        require_once $api_file;
        exit();
    }
}
http_response_code(404);
echo json_encode(['status' => 'error', 'message' => 'Endpoint not found']); 