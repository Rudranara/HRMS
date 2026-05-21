<?php
require 'db_connection.php';

header('Content-Type: application/json');

$selected_office = isset($_GET['office']) ? trim(urldecode($_GET['office'])) : '';

if (!empty($selected_office)) {
    $employees_stmt = $conn->prepare("SELECT id, name FROM employees WHERE office = ? AND status = 'Active' ORDER BY name");
    $employees_stmt->bind_param("s", $selected_office);
} else {
    $employees_stmt = $conn->prepare("SELECT id, name FROM employees WHERE status = 'Active' ORDER BY name");
}

$employees_stmt->execute();
$employees = $employees_stmt->get_result();

$employee_list = [];
while ($row = $employees->fetch_assoc()) {
    $employee_list[] = $row;
}

echo json_encode($employee_list);
?>
