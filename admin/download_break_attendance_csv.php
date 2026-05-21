<?php
require 'db_connection.php';

// Get filters from the request
$selected_employee = isset($_GET['employee_id']) ? $_GET['employee_id'] : '';
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('m');
$selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$selected_office = isset($_GET['office']) ? trim(urldecode($_GET['office'])) : '';

// Filter dates for the selected month and year
$filter_start = "{$selected_year}-{$selected_month}-01";
$filter_end = date("Y-m-t 23:59:59", strtotime($filter_start)); // Add 23:59:59 to include the last day's data

// Fetch attendance data based on the filters
$attendance_query = "    
    SELECT a.*, e.name AS employee_name,
           e.employee_id AS emp_id
    FROM break_attendance a
    INNER JOIN employees e ON a.employee_id = e.id
    WHERE a.punch_out_time BETWEEN ? AND ? 
";
if ($selected_employee) {
    $attendance_query .= " AND a.employee_id = ?";
}
if ($selected_office) {
    $attendance_query .= " AND e.office = ?";
}
$attendance_query .= " ORDER BY a.punch_in_time DESC";

$attendance_stmt = $conn->prepare($attendance_query);
if ($selected_employee && $selected_office) {
    $attendance_stmt->bind_param("ssis", $filter_start, $filter_end, $selected_employee, $selected_office);
} elseif ($selected_employee) {
    $attendance_stmt->bind_param("ssi", $filter_start, $filter_end, $selected_employee);
} elseif ($selected_office) {
    $attendance_stmt->bind_param("sss", $filter_start, $filter_end, $selected_office);
} else {
    $attendance_stmt->bind_param("ss", $filter_start, $filter_end);
}
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();

// Set headers for the CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=attendance_report.csv');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Output the column headings
fputcsv($output, ['Employee Name', 'Employee ID', 'Break Start Time', 'Break End Time', 'Total Break Hours', 'Status']);

// Fetch and output the rows
while ($row = $attendance_result->fetch_assoc()) {
    fputcsv($output, [
        $row['employee_name'],
        $row['emp_id'],
        $row['punch_in_time'],
        $row['punch_out_time'],
        $row['working_hours'],
        $row['status']
    ]);
}
// Close the file pointer
fclose($output);
exit;
?>
