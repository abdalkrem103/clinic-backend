<?php
// Railway Production Server Entry Point
header('Content-Type: application/json');

// Enable CORS with specific origin instead of wildcard
$allowed_origins = [
    'http://localhost:3000',
    'https://clinic-management-frontend.vercel.app',
    'https://clinic-management.vercel.app'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    // Fallback for development
    header('Access-Control-Allow-Origin: http://localhost:3000');
}

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

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