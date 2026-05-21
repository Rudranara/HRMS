<?php
require 'db_connection.php'; // Include your database connection file

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['type'])) {
    $type = $_POST['type'];
    $selected_office = isset($_POST['office']) ? trim(urldecode($_POST['office'])) : '';
    $today_date = date('Y-m-d');

    if ($type == 'present') {
        // Get present employees with today's punch_in_time
        $sql = "
            SELECT e.name, e.office, a.punch_in_time 
            FROM employees e 
            JOIN attendance a ON e.id = a.employee_id 
            WHERE e.status = 'Active' 
              AND DATE(a.punch_in_time) = ? 
              AND a.status = 'Present'
        ";
    } else {
        // Get active employees who are not present today
        $sql = "
            SELECT e.name, e.office 
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

   if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $name = htmlspecialchars($row['name']);
        $office = htmlspecialchars($row['office']);
        $punchTime = isset($row['punch_in_time']) ? date('H:i:s', strtotime($row['punch_in_time'])) : 'N/A';
            $employeeLine = $name . ' (' . $office . ') - Punch In: ' . $punchTime;
        echo "<li class='list-group-item employee-list-item'>
                <div class='employee-list-row'>
                        <span class='employee-list-line'>{$employeeLine}</span>
                </div>
              </li>";
    }
} else {
    echo "<li class='list-group-item employee-list-empty'>No employees found.</li>";
}


    $query->close();
    $conn->close();
}
?>

