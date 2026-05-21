<?php
require 'db_connection.php';
date_default_timezone_set('Asia/Kolkata');
// Get today's date
$today_date = date('Y-m-d');
// Fetch all active employees
$employees_result = $conn->query("SELECT id, office FROM employees WHERE status = 'Active'");
while ($employee = $employees_result->fetch_assoc()) {
    $employee_id = $employee['id'];
    $employee_office = $employee['office'];

    $sundayWeeklyOffStmt = $conn->prepare("
        UPDATE attendance
        SET status = 'Weekly Off', office = ?
        WHERE employee_id = ?
          AND status = 'Absent'
          AND DAYOFWEEK(punch_in_time) = 1
          AND DATE(punch_in_time) <= ?
    ");
    $sundayWeeklyOffStmt->bind_param("sis", $employee_office, $employee_id, $today_date);
    $sundayWeeklyOffStmt->execute();
    $sundayWeeklyOffStmt->close();

    $lateLeaveUpdateStmt = $conn->prepare("
        UPDATE attendance a
        INNER JOIN leave_requests lr
            ON lr.employee_id = a.employee_id
           AND lr.status = 'Approved'
           AND DATE(a.punch_in_time) BETWEEN DATE(lr.start_date) AND DATE(lr.end_date)
        SET a.status = 'On Leave', a.office = ?
        WHERE a.employee_id = ?
          AND a.status = 'Absent'
          AND TIME(a.punch_in_time) = '00:00:00'
          AND TIME(a.punch_out_time) = '00:00:00'
          AND DATE(a.punch_in_time) <= ?
    ");
    $lateLeaveUpdateStmt->bind_param("sis", $employee_office, $employee_id, $today_date);
    $lateLeaveUpdateStmt->execute();
    $lateLeaveUpdateStmt->close();

    // Get the last punch-out date
    $stmt = $conn->prepare("
        SELECT DATE(punch_out_time) AS last_punch_out_date 
        FROM attendance 
        WHERE employee_id = ? AND punch_out_time IS NOT NULL 
        ORDER BY punch_out_time DESC 
        LIMIT 1
    ");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $stmt->bind_result($last_punch_out_date);
    $stmt->fetch();
    $stmt->close();
    if ($last_punch_out_date) {
        $missing_date = date('Y-m-d', strtotime($last_punch_out_date . ' +1 day'));
        // Loop through each missing day
       while (strtotime($missing_date) < strtotime($today_date)) {
            $existingAttendanceStmt = $conn->prepare("
                SELECT id
                FROM attendance
                WHERE employee_id = ? AND DATE(punch_in_time) = ?
                LIMIT 1
            ");
            $existingAttendanceStmt->bind_param("is", $employee_id, $missing_date);
            $existingAttendanceStmt->execute();
            $existingAttendanceStmt->store_result();
            $attendanceExists = $existingAttendanceStmt->num_rows > 0;
            $existingAttendanceStmt->close();

            if ($attendanceExists) {
                $missing_date = date('Y-m-d', strtotime($missing_date . ' +1 day'));
                continue;
            }

            $event_type = null;
            // Check if the date is a weekly off or holiday
            $stmt = $conn->prepare("
                SELECT event_type 
                FROM events 
                WHERE start_date = ?
            ");
            $stmt->bind_param("s", $missing_date);
            $stmt->execute();
            $stmt->bind_result($event_type);
            $stmt->fetch();
            $stmt->close();
            // Check leave status
            $leave_status = false;

            $stmt = $conn->prepare("
                SELECT 1
                FROM leave_requests
                WHERE employee_id = ?
                AND status = 'Approved'
                AND ? BETWEEN DATE(start_date) AND DATE(end_date)
                LIMIT 1
            ");
            $stmt->bind_param("is", $employee_id, $missing_date);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $leave_status = true;
            }

            $stmt->close();
                       
            // Determine final status
            $is_sunday = date('N', strtotime($missing_date)) == 7;

            if ($leave_status) {
                $status = 'On Leave';
            } elseif ($is_sunday) {
                $status = 'Weekly Off';
            } elseif ($event_type === 'weekly_off') {
                $status = 'Weekly Off';
            } elseif ($event_type === 'holiday') {
                $status = 'Holiday';
            } else {
                $status = 'Absent';
            }

            // Insert attendance record
            $stmt = $conn->prepare("
                INSERT INTO attendance (employee_id, punch_in_time, punch_out_time, working_hours, office, status) 
                VALUES (?, ?, ?, 0, ?, ?)
            ");
            $punch_in = $missing_date . " 00:00:00";
            $punch_out = $missing_date . " 00:00:00";
            $stmt->bind_param("issss", $employee_id, $punch_in, $punch_out, $employee_office, $status);
            $stmt->execute();
            $stmt->close();
            // Go to next day
            $missing_date = date('Y-m-d', strtotime($missing_date . ' +1 day'));
        }
    }
}
echo "Missing attendance records updated successfully.\n";
?>
