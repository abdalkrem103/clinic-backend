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

// Handle /api/ prefix
if (strpos($endpoint, 'api/') === 0) {
    $endpoint = substr($endpoint, 4); // Remove 'api/' prefix
}

// Ensure .php extension only once
if (!str_ends_with($endpoint, '.php')) {
    $endpoint .= '.php';
}

$api_file_path = "../api/{$endpoint}";

// Debug information
$debug_info = [
    'request_uri' => $request_uri,
    'path' => $path,
    'endpoint' => $endpoint,
    'file_path' => $api_file_path,
    'file_exists' => file_exists($api_file_path),
    'current_dir' => __DIR__,
    'api_dir' => realpath(__DIR__ . '/../api'),
    'api_files' => file_exists("../api/") ? scandir("../api/") : 'API directory not found'
];

// Route the request to the appropriate API file
if (file_exists($api_file_path)) {
    require_once $api_file_path;
} else {
    // Default response for root
    if ($endpoint === '' || $endpoint === 'index.php') {
        echo json_encode([
            'status' => 'success',
            'message' => 'Clinic Management API is running',
            'version' => '1.0.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'debug' => $debug_info
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Endpoint not found: ' . $endpoint,
            'debug' => $debug_info
        ]);
    }
}
?> 