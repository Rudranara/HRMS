<?php
require 'db_connection.php'; // Include your database connection file

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['type']) && isset($_POST['office'])) {
    $type = $_POST['type'];
    $office_name = $_POST['office'];
    $today_date = date('Y-m-d');

    if ($type == 'present') {
        // Fetch employees who are marked as 'Present' for the selected office
        $query = $conn->prepare("
            SELECT e.name FROM employees e 
            JOIN attendance a ON e.id = a.employee_id 
            WHERE DATE(a.punch_in_time) = ? 
            AND a.status = 'Present' 
            AND e.office = ?
        ");
    } else {
        // Fetch employees who are in the selected office but NOT marked 'Present' (Absent employees)
        $query = $conn->prepare("
            SELECT e.name FROM employees e 
            WHERE e.office = ? 
            AND e.id NOT IN (
                SELECT a.employee_id FROM attendance a 
                WHERE DATE(a.punch_in_time) = ? 
                AND a.status = 'Present'
            )
        ");
    }

    if ($type == 'present') {
        $query->bind_param("ss", $today_date, $office_name);
    } else {
        $query->bind_param("ss", $office_name, $today_date);
    }

    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<li class='list-group-item'>" . htmlspecialchars($row['name']) . "</li>";
        }
    } else {
        echo "<li class='list-group-item text-danger'>No employees found.</li>";
    }
}
?>
