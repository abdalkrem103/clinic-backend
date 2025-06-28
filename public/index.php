<?php
// Railway Production Server Entry Point
header('Content-Type: application/json');

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get the request path
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Remove leading slash and get the endpoint
$endpoint = ltrim($path, '/');

// Route the request to the appropriate API file
if (file_exists("../api/{$endpoint}.php")) {
    require_once "../api/{$endpoint}.php";
} else {
    // Default response for root
    if ($endpoint === '' || $endpoint === 'index.php') {
        echo json_encode([
            'status' => 'success',
            'message' => 'Clinic Management API is running',
            'version' => '1.0.0',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Endpoint not found: ' . $endpoint
        ]);
    }
}
?> 