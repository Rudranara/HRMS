<?php
require 'db_connection.php';

$stmt = $conn->prepare("
    SELECT rl.attendance_id, e.name AS employee_name, rl.location
    FROM realtime_location_updates rl
    JOIN attendance a ON rl.attendance_id = a.id
    JOIN employees e ON a.employee_id = e.id
    WHERE a.punch_out_time IS NULL
    GROUP BY rl.attendance_id
    ORDER BY rl.timestamp DESC
");
$stmt->execute();
$result = $stmt->get_result();

$locations = [];
while ($row = $result->fetch_assoc()) {
    $locations[] = $row;
}

echo json_encode($locations);
?>
