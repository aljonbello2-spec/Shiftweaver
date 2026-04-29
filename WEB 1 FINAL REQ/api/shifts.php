<?php
// api/shifts.php - Handle shift operations

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : null;

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// GET - Fetch shifts
if ($method === 'GET') {
    if ($action === 'getAll') {
        getOpenShifts();
    } elseif ($action === 'getMetrics') {
        getMetrics();
    } else {
        sendError('Invalid action', 400);
    }
}

// POST - Create new shift
if ($method === 'POST') {
    if ($action === 'create') {
        createShift();
    } else {
        sendError('Invalid action', 400);
    }
}

// Function: Get all open shifts
function getOpenShifts() {
    global $conn;
    
    $query = "SELECT * FROM shifts WHERE status = 'open' ORDER BY created_at DESC LIMIT 20";
    $result = $conn->query($query);
    
    if (!$result) {
        sendError('Query failed: ' . $conn->error, 500);
    }
    
    $shifts = [];
    while ($row = $result->fetch_assoc()) {
        $shifts[] = $row;
    }
    
    sendResponse(['success' => true, 'data' => $shifts]);
}

// Function: Get platform metrics
function getMetrics() {
    global $conn;
    
    // Count open shifts
    $openShifts = $conn->query("SELECT COUNT(*) as count FROM shifts WHERE status = 'open'")->fetch_assoc()['count'];
    
    // Count active workers
    $activeWorkers = $conn->query("SELECT COUNT(DISTINCT worker_id) as count FROM applications WHERE status = 'active'")->fetch_assoc()['count'];
    
    // Count matches today
    $today = date('Y-m-d');
    $matches = $conn->query("SELECT COUNT(*) as count FROM applications WHERE created_at >= '$today' AND status = 'matched'")->fetch_assoc()['count'];
    
    sendResponse([
        'success' => true,
        'data' => [
            'openShifts' => (int)$openShifts,
            'activeWorkers' => (int)$activeWorkers,
            'matches' => (int)$matches
        ]
    ]);
}

// Function: Create a new shift
function createShift() {
    global $conn;
    
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Validate required fields
    $required = ['title', 'description', 'startTime', 'endTime', 'payRate', 'location', 'managerId'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            sendError("Missing required field: $field", 400);
        }
    }
    
    $title = $conn->real_escape_string($data['title']);
    $description = $conn->real_escape_string($data['description']);
    $startTime = $conn->real_escape_string($data['startTime']);
    $endTime = $conn->real_escape_string($data['endTime']);
    $payRate = (float)$data['payRate'];
    $location = $conn->real_escape_string($data['location']);
    $managerId = (int)$data['managerId'];
    $skills = isset($data['skills']) ? $conn->real_escape_string($data['skills']) : '';
    
    $query = "INSERT INTO shifts (title, description, start_time, end_time, pay_rate, location, manager_id, skills_required, status, created_at) 
              VALUES ('$title', '$description', '$startTime', '$endTime', $payRate, '$location', $managerId, '$skills', 'open', NOW())";
    
    if ($conn->query($query)) {
        $shiftId = $conn->insert_id;
        sendResponse([
            'success' => true,
            'message' => 'Shift created successfully',
            'shiftId' => $shiftId
        ], 201);
    } else {
        sendError('Failed to create shift: ' . $conn->error, 500);
    }
}
?>