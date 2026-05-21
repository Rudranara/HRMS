<?php
include("db_connection.php"); // Include database connection

if (isset($_GET['employee_id'])) {
    $employee_id = $_GET['employee_id'];
    $stmt = $conn->prepare("SELECT sick_leave, casual_leave, paid_leave, other_leave, total_leave FROM employees WHERE id = ?");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    echo json_encode($result);
}
?>
