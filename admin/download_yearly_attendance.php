<?php
require 'db_connection.php';

if (isset($_GET['year'], $_GET['employee_id'])) {
    $selected_year = (int)$_GET['year'];
    $selected_employee = (int)$_GET['employee_id'];

    // Get employee name
    $emp_stmt = $conn->prepare("SELECT name FROM employees WHERE id = ?");
    $emp_stmt->bind_param("i", $selected_employee);
    $emp_stmt->execute();
    $employee = $emp_stmt->get_result()->fetch_assoc();
    $employee_name = $employee['name'] ?? 'Unknown';
    $emp_stmt->close();

    // Set CSV headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="Yearly_Attendance_' . $employee_name . '_' . $selected_year . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Month', 'Total Present', 'Total Absent', 'Total On Leave', 'Total Late Punch-ins', 'Total Early Punch-outs', 'Total Working Hours', 'Total Break Hours']);

    // Fetch data for each month
    for ($month = 1; $month <= 12; $month++) {
        $start_date = "{$selected_year}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
        $end_date = date("Y-m-t 23:59:59", strtotime($start_date));

        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) AS total_present,
                SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) AS total_absent,
                SUM(CASE WHEN status = 'On_Leave' THEN 1 ELSE 0 END) AS total_on_leave,
                SUM(CASE WHEN punch_in_time > '09:00:00' THEN 1 ELSE 0 END) AS total_late,
                SUM(CASE WHEN punch_out_time < '18:00:00' THEN 1 ELSE 0 END) AS total_early,
                SUM(working_hours) AS total_working_hours,
                SUM(break_hours) AS total_break_hours
            FROM attendance
            WHERE employee_id = ? AND punch_out_time BETWEEN ? AND ?
        ");
        $stmt->bind_param("iss", $selected_employee, $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        fputcsv($output, [
            date("F", strtotime($start_date)),
            $result['total_present'] ?? 0,
            $result['total_absent'] ?? 0,
            $result['total_on_leave'] ?? 0,
            $result['total_late'] ?? 0,
            $result['total_early'] ?? 0,
            $result['total_working_hours'] ?? 0,
            $result['total_break_hours'] ?? 0
        ]);
    }

    fclose($output);
    exit();
} else {
    echo "Invalid request.";
}
?>