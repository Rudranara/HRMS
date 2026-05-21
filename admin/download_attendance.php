<?php
require 'db_connection.php';

$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$selected_month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$selected_office = isset($_GET['office']) ? $_GET['office'] : '';
$decoded_office = $selected_office !== '' ? urldecode($selected_office) : '';

// Prepare headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="attendance_summary.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Column headers
fputcsv($output, ['Employee ID', 'Name', 'Office', 'Total Present', 'Total Absent', 'Total On Leave', 'Total Late Punch-ins', 'Total Early Punch-outs', 'Total Working Hours', 'Total Break Hours']);

// Prepare the query for employee attendance summary
$employee_query = "SELECT id, name, office FROM employees";
if ($decoded_office !== '') {
    $employee_query .= " WHERE office = ?";
}

$employees_stmt = $conn->prepare($employee_query);
if ($decoded_office !== '') {
    $employees_stmt->bind_param("s", $decoded_office);
}
$employees_stmt->execute();
$employees_result = $employees_stmt->get_result();
$employees = $employees_result->fetch_all(MYSQLI_ASSOC);
$employees_stmt->close();

$filter_start = "{$selected_year}-{$selected_month}-01";
$filter_end = date("Y-m-t 23:59:59", strtotime($filter_start));

foreach ($employees as $employee) {
    $emp_id = $employee['id'];

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total_present,
               SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) AS total_absent,
               SUM(CASE WHEN status = 'On_Leave' THEN 1 ELSE 0 END) AS total_on_leave,
               SUM(CASE WHEN punch_in_time > '09:00:00' THEN 1 ELSE 0 END) AS total_late,
               SUM(CASE WHEN punch_out_time < '18:00:00' THEN 1 ELSE 0 END) AS total_early,
               SUM(working_hours) AS total_working_hours,
               SUM(break_hours) AS total_break_hours
        FROM attendance
        WHERE employee_id = ? AND punch_out_time BETWEEN ? AND ?
    ");
    $stmt->bind_param("iss", $emp_id, $filter_start, $filter_end);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    fputcsv($output, [
        $employee['id'], $employee['name'], $employee['office'], 
        $result['total_present'] ?? 0, $result['total_absent'] ?? 0, $result['total_on_leave'] ?? 0, 
        $result['total_late'] ?? 0, $result['total_early'] ?? 0, 
        $result['total_working_hours'] ?? 0, $result['total_break_hours'] ?? 0
    ]);
}

// Close output stream
fclose($output);
exit;
