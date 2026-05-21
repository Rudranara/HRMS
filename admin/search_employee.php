<?php
include 'db_connection.php'; // Include your database connection file

if (isset($_GET['search'])) {
    $search_query = $conn->real_escape_string($_GET['search']);
    $result = $conn->query("
        SELECT id, employee_id, name 
        FROM employees WHERE status = 'Active'
        AND name LIKE '%$search_query%' OR employee_id LIKE '%$search_query%' OR id LIKE '%$search_query%'
        ORDER BY name
    ");

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<li style="background-color:#d5b49d;color:black" class="list-group-item employee-item" data-id="' . $row['id'] . '">' . $row['name'] . ' -(' . $row['employee_id'] . ')</li>';
        }
    } else {
        echo '<li class="list-group-item">No employees found</li>';
    }
}
?>
