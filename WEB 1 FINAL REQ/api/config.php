<?php
// config.php - Database configuration and helper functions

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'shiftweaver');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Set charset to UTF8
$conn->set_charset("utf8");

// API Response helper
function sendResponse($data, $statusCode = 200) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Error handler
function sendError($message, $statusCode = 400) {
    sendResponse(['error' => $message], $statusCode);
}
?>