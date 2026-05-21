<?php
session_start();
require 'db_connection.php';

header('Content-Type: application/json');

// Check if the employee is logged in
if (!isset($_SESSION['employee_logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$employee_id = $_SESSION['employee_id'];
$data = json_decode(file_get_contents('php://input'), true);
$location = $data['location'] ?? null;

if ($location) {
    // Update location in the attendance table for the currently punched-in session
    $stmt = $conn->prepare("
        UPDATE attendance
        SET current_location = ?, current_location_updated_at = NOW()
        WHERE employee_id = ? AND punch_out_time IS NULL
    ");
    $stmt->bind_param('si', $location, $employee_id);
    $stmt->execute();

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Invalid location data']);
}
?>
