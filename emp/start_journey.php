<?php
session_start();
include 'db_connection.php';

// Always return JSON
header('Content-Type: application/json');

// Validate session
if (!isset($_SESSION['employee_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "User not logged in"
    ]);
    exit;
}

$user_id = $_SESSION['employee_id'];

// Validate request
if (!isset($_POST['start_lat']) || !isset($_POST['start_lng'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing coordinates"
    ]);
    exit;
}

$lat = $_POST['start_lat'];
$lng = $_POST['start_lng'];

// Disable warnings from breaking JSON
mysqli_report(MYSQLI_REPORT_OFF);

// Remove previous journey start
$conn->query("DELETE FROM journey_start WHERE user_id='$user_id'");

// Insert new start point
$insert = $conn->query("
    INSERT INTO journey_start (user_id, start_lat, start_lng)
    VALUES ('$user_id','$lat','$lng')
");

if ($insert) {
    echo json_encode([
        "status" => "started",
        "message" => "Journey Started Successfully"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $conn->error
    ]);
}
?>
