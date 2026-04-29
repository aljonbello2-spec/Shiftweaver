<?php
// api/post_shift.php - Handle shift form submissions

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Validate required fields
    $required = ['title', 'description', 'startTime', 'endTime', 'payRate', 'location', 'managerId'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            sendError("Missing required field: $field", 400);
        }
    }
    
    // Sanitize inputs
    $title = $conn->real_escape_string(trim($data['title']));
    $description = $conn->real_escape_string(trim($data['description']));
    $startTime = $conn->real_escape_string($data['startTime']);
    $endTime = $conn->real_escape_string($data['endTime']);
    $payRate = (float)$data['payRate'];
    $location = $conn->real_escape_string(trim($data['location']));
    $managerId = (int)$data['managerId'];
    $skills = isset($data['skills']) ? $conn->real_escape_string($data['skills']) : '';
    
    // Validate pay rate
    if ($payRate <= 0) {
        sendError("Pay rate must be greater than 0", 400);
    }
    
    // Validate times
    if (strtotime($startTime) >= strtotime($endTime)) {
        sendError("End time must be after start time", 400);
    }
    
    $query = "INSERT INTO shifts (title, description, start_time, end_time, pay_rate, location, manager_id, skills_required, status, created_at) 
              VALUES ('$title', '$description', '$startTime', '$endTime', $payRate, '$location', $managerId, '$skills', 'open', NOW())";
    
    if ($conn->query($query)) {
        $shiftId = $conn->insert_id;
        sendResponse([
            'success' => true,
            'message' => 'Shift posted successfully!',
            'shiftId' => $shiftId
        ], 201);
    } else {
        sendError('Failed to post shift: ' . $conn->error, 500);
    }
} else {
    sendError('Method not allowed', 405);
}
?>