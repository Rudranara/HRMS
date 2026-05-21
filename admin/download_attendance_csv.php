<?php
require 'db_connection.php';

// Get filters from the request
$selected_employee = isset($_GET['employee_id']) ? $_GET['employee_id'] : '';
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('m');
$selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$selected_office = isset($_GET['office']) ? $_GET['office'] : '';

// Decode office value if set (e.g., "Delhi_Delhi")
$decoded_office = !empty($selected_office) ? urldecode($selected_office) : null;

// Format the date range
$filter_start = "{$selected_year}-{$selected_month}-01";
$filter_end = date("Y-m-t 23:59:59", strtotime($filter_start));

// Base query
$attendance_query = "
    SELECT a.*, e.name AS employee_name, e.employee_id AS emp_id, e.office
    FROM attendance a
    INNER JOIN employees e ON a.employee_id = e.id
    WHERE a.punch_out_time BETWEEN ? AND ?
";

// Build conditions and params
$params = [$filter_start, $filter_end];
$types = "ss";

if (!empty($selected_employee)) {
    $attendance_query .= " AND a.employee_id = ?";
    $types .= "i";
    $params[] = $selected_employee;
}

if (!empty($decoded_office)) {
    $attendance_query .= " AND e.office = ?";
    $types .= "s";
    $params[] = $decoded_office;
}

$attendance_query .= " ORDER BY a.punch_in_time DESC";

// Prepare and execute query
$stmt = $conn->prepare($attendance_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Build filename based on filters
$month_name = date("F", mktime(0, 0, 0, $selected_month, 10));
$filename_parts = ["attendance", $month_name, $selected_year];

if (!empty($selected_employee)) {
    // Get employee name for filename
    $emp_stmt = $conn->prepare("SELECT name FROM employees WHERE id = ?");
    $emp_stmt->bind_param("i", $selected_employee);
    $emp_stmt->execute();
    $emp_result = $emp_stmt->get_result();
    if ($emp_row = $emp_result->fetch_assoc()) {
        $filename_parts[] = str_replace(' ', '_', $emp_row['name']);
    }
    $emp_stmt->close();
}

$filename = implode("_", $filename_parts) . ".csv";

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"$filename\"");

// Output to browser
$output = fopen('php://output', 'w');

// Column headings
fputcsv($output, ['Employee Name', 'Employee ID', 'Office', 'Punch In Time', 'location_in', 'Punch Out Time', 'location_out', 'Working Hours', 'Status']);

// Data rows
// Data rows
while ($row = $result->fetch_assoc()) {
    // Map status to short codes
    $status_map = [
        'Present' => 'P',
        'Absent' => 'A',
        'Holiday' => 'H',
        'Weekly Off' => 'W'
    ];

    $status = isset($status_map[$row['status']]) ? $status_map[$row['status']] : $row['status'];

    fputcsv($output, [
        $row['employee_name'],
        $row['emp_id'],
        $row['office'],
        $row['punch_in_time'],
        $row['location_in'],
        $row['punch_out_time'],
        $row['location_out'],
        $row['working_hours'],
        $status
    ]);
}


fclose($output);
exit;
?>
