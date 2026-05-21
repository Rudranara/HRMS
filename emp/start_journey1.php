<?php
include("db_connection.php");
session_start();

date_default_timezone_set('Asia/Kolkata');

$user_id   = $_SESSION['employee_id'];
$start_lat = floatval($_POST['start_lat']);
$start_lng = floatval($_POST['start_lng']);
$now       = date("Y-m-d H:i:s");

/* ===============================
   1️⃣ BLOCK IF JOURNEY ALREADY ENDED TODAY
=============================== */
$ended_today = mysqli_query($conn, "
    SELECT id
    FROM journey_start
    WHERE user_id = '$user_id'
      AND DATE(start_time) = CURDATE()
      AND status = 'ended'
    LIMIT 1
");

if (mysqli_num_rows($ended_today) > 0) {
    echo json_encode([
        "status" => "blocked",
        "message" => "You have already ended your journey today. You cannot start again."
    ]);
    exit;
}

/* ===============================
   2️⃣ BLOCK IF JOURNEY ALREADY STARTED TODAY
=============================== */
$started_today = mysqli_query($conn, "
    SELECT id
    FROM journey_start
    WHERE user_id = '$user_id'
      AND DATE(start_time) = CURDATE()
      AND status = 'started'
    LIMIT 1
");

if (mysqli_num_rows($started_today) > 0) {
    echo json_encode([
        "status" => "blocked",
        "message" => "Your journey is already running today."
    ]);
    exit;
}

/* ===============================
   3️⃣ START NEW JOURNEY
=============================== */
mysqli_query($conn, "
    INSERT IGNORE INTO journey_start
        (user_id, start_lat, start_lng, start_time, status)
    VALUES
        ('$user_id', '$start_lat', '$start_lng', '$now', 'started')
");

echo json_encode([
    "status" => "success",
    "message" => "Journey Started"
]);
