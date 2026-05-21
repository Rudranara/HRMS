<?php
require 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['type'])) {
    $type = $_POST['type'];
    $selected_office = isset($_POST['office']) ? trim(urldecode($_POST['office'])) : '';
    $today_date = date('Y-m-d');

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="employees_' . $type . '_list.csv"');

    $output = fopen("php://output", "w");

    // Output the CSV column headers
    fputcsv($output, ['Employee ID', 'Name', 'Office', 'Punch In Time']);

    if ($type == 'present') {
        $sql = "
            SELECT e.employee_id as employee_id, e.name, e.office, a.punch_in_time 
            FROM employees e 
            JOIN attendance a ON e.id = a.employee_id 
            WHERE e.status = 'Active' 
              AND DATE(a.punch_in_time) = ? 
              AND a.status = 'Present'
        ";
    } else {
        $sql = "
            SELECT e.employee_id as employee_id, e.name, e.office, NULL as punch_in_time 
            FROM employees e 
            WHERE e.status = 'Active' 
              AND e.id NOT IN (
                  SELECT employee_id 
                  FROM attendance 
                  WHERE DATE(punch_in_time) = ? 
                    AND status = 'Present'
              )
        ";
    }

    if (!empty($selected_office)) {
        $sql .= " AND e.office = ?";
    }

    $query = $conn->prepare($sql);
    if (!empty($selected_office)) {
        $query->bind_param("ss", $today_date, $selected_office);
    } else {
        $query->bind_param("s", $today_date);
    }
    $query->execute();
    $result = $query->get_result();

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['employee_id'],
            $row['name'],
            $row['office'],
            isset($row['punch_in_time']) ? date('H:i:s', strtotime($row['punch_in_time'])) : 'N/A'
        ]);
    }

    fclose($output);
    $query->close();
    $conn->close();
    exit;
}
?>
