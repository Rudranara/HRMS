<?php
require 'db_connection.php';
date_default_timezone_set('Asia/Kolkata');

$id = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if (!$id || !in_array($action, ['present', 'absent'])) {
    die('Invalid request');
}

if ($action === 'present') {

    /*
      Admin approves → calculate working hours
      punch_out_time = employee punchout_time
      is_auto_punchout = 0 (admin corrected)
    */
    $stmt = $conn->prepare("
        UPDATE attendance a
        JOIN employees e ON e.id = a.employee_id
        SET 
            a.punch_out_time = CONCAT(DATE(a.punch_in_time), ' ', e.punchout_time),
            a.working_hours = 
                TIMESTAMPDIFF(MINUTE, a.punch_in_time,
                    CONCAT(DATE(a.punch_in_time), ' ', e.punchout_time)
                ) / 60,
            a.status = 'Present',
            a.is_auto_punchout = 0
        WHERE a.id = ?
    ");

} else {

    /*
      Admin confirms Absent
    */
    $stmt = $conn->prepare("
        UPDATE attendance
        SET 
            status = 'Absent',
            working_hours = 0,
            is_auto_punchout = 0
        WHERE id = ?
    ");
}

$stmt->bind_param("i", $id);
$stmt->execute();

$redirect = "forgot_punchout_requests?success=1";
$month = $_GET['month'] ?? '';
$year  = $_GET['year']  ?? '';
$name  = $_GET['name']  ?? '';
if ($month !== '') $redirect .= "&month=" . urlencode($month);
if ($year  !== '') $redirect .= "&year="  . urlencode($year);
if ($name  !== '') $redirect .= "&name="  . urlencode($name);

header("Location: $redirect");
exit;
