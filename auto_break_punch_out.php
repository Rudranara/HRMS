<?php
require 'db_connection.php'; // Database connection file
date_default_timezone_set('Asia/Kolkata'); // Set timezone to IST

// Get today's date
$today_date = date('Y-m-d');

// Query to find rows with missing punch-outs
$query = "
    SELECT id, employee_id, punch_in_time, location_in, office 
    FROM break_attendance 
    WHERE punch_out_time IS NULL AND DATE(punch_in_time) <= ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $today_date);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $attendance_id = $row['id'];
    $employee_id = $row['employee_id'];
    $punch_in_time = $row['punch_in_time'];
    $location_in = $row['location_in'];
    $office = $row['office'];

    // Determine auto punch-out time (Punch-in Time + 60 minutes)
    $punch_out_time = date('Y-m-d H:i:s', strtotime($punch_in_time . ' +60 minutes'));

    // Calculate working hours
    $diff_seconds = strtotime($punch_out_time) - strtotime($punch_in_time);
    $hours = floor($diff_seconds / 3600);
    $minutes = round(($diff_seconds % 3600) / 60);
    $working_hours = $hours + ($minutes / 60);

    // Update attendance with auto punch-out
    $update_query = "
        UPDATE break_attendance 
        SET punch_out_time = ?, location_out = ?, working_hours = ?, 
            is_auto_punchout = 1, status = 'Present'
        WHERE id = ?
    ";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssdi", $punch_out_time, $location_in, $working_hours, $attendance_id);
    $update_stmt->execute();
    $update_stmt->close();
}

$stmt->close();
$conn->close();

echo "Auto punch-out script executed successfully!";
?>
