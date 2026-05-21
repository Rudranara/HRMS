<?php
require 'db_connection.php'; // Database connection file
date_default_timezone_set('Asia/Kolkata'); // Set timezone to IST

function ensureAutoPunchoutColumn(mysqli $conn): void
{
    $check = $conn->query("SHOW COLUMNS FROM employees LIKE 'disable_auto_punchout'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE employees ADD COLUMN disable_auto_punchout TINYINT(1) NOT NULL DEFAULT 0");
    }
}

ensureAutoPunchoutColumn($conn);

// Get today's date
$today_date = date('Y-m-d');

// Query to find rows with missing punch-outs up to today
$query = "
    SELECT a.id, a.employee_id, a.punch_in_time, a.location_in, a.office
    FROM attendance a
    INNER JOIN employees e ON a.employee_id = e.id
    WHERE a.punch_out_time IS NULL
      AND DATE(a.punch_in_time) <= ?
      AND COALESCE(e.disable_auto_punchout, 0) = 0
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

    // Set punch_out_time to 00:00:00 of the same date
    $punch_out_time = date('Y-m-d', strtotime($punch_in_time)) . " 00:00:00";

    // Set working hours = 0
    $working_hours = 0;

    // Update attendance as Absent with auto punch-out
    $update_query = "
        UPDATE attendance 
        SET punch_out_time = ?, location_out = ?, working_hours = ?, 
            is_auto_punchout = 1, status = 'Absent'
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
