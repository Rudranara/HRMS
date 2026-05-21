<?php
include("db_connection.php");
session_start();

$user_id = $_SESSION['employee_id'] ?? 0;
date_default_timezone_set('Asia/Kolkata');

if (!$user_id) {
    echo json_encode([
        "status" => "error",
        "message" => "Not logged in"
    ]);
    exit;
}

// Get LAST journey only
$q = mysqli_query($conn, "
    SELECT status, DATE(start_time) AS start_date
    FROM journey_start
    WHERE user_id = '$user_id'
    ORDER BY id DESC
    LIMIT 1
");

$row = mysqli_fetch_assoc($q);

//  No journey at all
if (!$row) {
    echo json_encode([
        "status" => "error",
        "message" => "Please start your journey first."
    ]);
    exit;
}

//  Previous journey not ended (older date)
if ($row['status'] === 'started' && $row['start_date'] < date('Y-m-d')) {
    echo json_encode([
        "status" => "error",
        "message" => "You did not end your previous journey. Please end it first."
    ]);
    exit;
}

//  No journey today
if ($row['start_date'] !== date('Y-m-d')) {
    echo json_encode([
        "status" => "error",
        "message" => "Today's journey has not started."
    ]);
    exit;
}

//  OK
echo json_encode([
    "status" => "success"
]);
