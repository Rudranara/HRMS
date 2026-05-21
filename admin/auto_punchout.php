<?php
require 'db_connection.php';
date_default_timezone_set('Asia/Kolkata');

// Ensure MySQL uses the correct time zone
$conn->query("SET time_zone = '+05:30'");

// Get the current date
$today_date = date('Y-m-d');

// Find employees who have not punched out
$query = "
    SELECT 
        a.id AS attendance_id, a.employee_id, a.punch_in_time, e.punchout_time
    FROM 
        attendance a
    JOIN 
        employees e ON a.employee_id = e.id
    WHERE 
        DATE(a.punch_in_time) = ? AND a.punch_out_time IS NULL
";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $today_date);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $attendance_id = $row['attendance_id'];
    $employee_id = $row['employee_id'];
    $punch_in_time = $row['punch_in_time'];
    $expected_punchout_time = $row['punchout_time'];

    // Check if the current time is past the expected punch-out time
    if (strtotime(date('H:i:s')) >= strtotime($expected_punchout_time)) {
        $punch_out_time = $today_date . ' ' . $expected_punchout_time;
        $diff_seconds = strtotime($punch_out_time) - strtotime($punch_in_time);
        $hours = floor($diff_seconds / 3600);
        $minutes = round(($diff_seconds % 3600) / 60);
        $working_hours = $hours + ($minutes / 60);

        // Update the attendance record
        $update_stmt = $conn->prepare("
            UPDATE attendance 
            SET punch_out_time = ?, working_hours = ?, is_auto_punchout = 1 
            WHERE id = ?
        ");
        $update_stmt->bind_param("sdi", $punch_out_time, $working_hours, $attendance_id);
        $update_stmt->execute();
        $update_stmt->close();

        // Update the employee's current location
        $clear_location_stmt = $conn->prepare("
            UPDATE employees 
            SET current_location = NULL, current_location_updated_at = NOW()
            WHERE id = ?
        ");
        $clear_location_stmt->bind_param("i", $employee_id);
        $clear_location_stmt->execute();
        $clear_location_stmt->close();
    }
}

$stmt->close();
$conn->close();
?>
