
<?php
// auto_end_journey.php
include("db_connection.php");

date_default_timezone_set('Asia/Kolkata');

$endTime = date('Y-m-d') . ' 23:50:00';

$stmt = $conn->prepare("
    UPDATE journey_start
    SET 
        end_time   = ?,
        end_km     = 0,
        auto_ended = 1,
        status     = 'ended'
    WHERE status = 'started'
      AND end_time IS NULL
");

$stmt->bind_param("s", $endTime);
$stmt->execute();

echo 'Auto-ended journeys: ' . $stmt->affected_rows;

$stmt->close();


