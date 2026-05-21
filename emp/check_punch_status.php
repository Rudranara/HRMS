<?php
session_start();
require 'db_connection.php';
date_default_timezone_set('Asia/Kolkata');

$employee_id = $_SESSION['employee_id'];
$today_date = date('Y-m-d');
$yesterday_date = date('Y-m-d', strtotime('-1 day'));

$employeeStmt = $conn->prepare("SELECT disable_auto_punchout FROM employees WHERE id = ?");
$employeeStmt->bind_param("i", $employee_id);
$employeeStmt->execute();
$employeeResult = $employeeStmt->get_result();
$employee = $employeeResult ? $employeeResult->fetch_assoc() : null;
$employeeStmt->close();

$disable_auto_punchout = !empty($employee['disable_auto_punchout']);

$stmt = $conn->prepare("SELECT punch_in_time, punch_out_time FROM attendance WHERE employee_id = ? AND DATE(punch_in_time) = CURDATE() ORDER BY id DESC LIMIT 1");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();

$response = ['punched_in' => false, 'enable_punch_out' => false, 'disable_punch_in' => false];

if ($row = $result->fetch_assoc()) {
    $current_timestamp = time();

    // If punched in but not punched out
    if ($row['punch_in_time'] && !$row['punch_out_time']) {
        $response['punched_in'] = true;
        
        // Convert punch_in_time to a timestamp
        $punch_in_timestamp = strtotime($row['punch_in_time']);

        // Check if 60 minutes have passed for enabling Punch Out
        if (($current_timestamp - $punch_in_timestamp) >= 3600) { // 3600 seconds = 60 minutes
            $response['enable_punch_out'] = true;
        }
    }

    // If punched out, disable punch-in for 60 minutes
    if ($row['punch_out_time']) {
        $punch_out_timestamp = strtotime($row['punch_out_time']);

        if (($current_timestamp - $punch_out_timestamp) < 3600) { // 60 minutes cooldown
            $response['disable_punch_in'] = true;
        }
    }
}

if (!$response['punched_in'] && !$response['disable_punch_in'] && $disable_auto_punchout) {
    $previousDayStmt = $conn->prepare("
        SELECT punch_in_time, punch_out_time
        FROM attendance
        WHERE employee_id = ?
          AND DATE(punch_in_time) = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $previousDayStmt->bind_param("is", $employee_id, $yesterday_date);
    $previousDayStmt->execute();
    $previousDayResult = $previousDayStmt->get_result();

    if ($previousDayRow = $previousDayResult->fetch_assoc()) {
        if (!empty($previousDayRow['punch_in_time']) && empty($previousDayRow['punch_out_time'])) {
            $response['punched_in'] = true;
            $response['enable_punch_out'] = true;
            $response['disable_punch_in'] = false;
        }
    }

    $previousDayStmt->close();
}

header('Content-Type: application/json');
echo json_encode($response);
?>
