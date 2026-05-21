<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$employee_id = $_GET['employee_id'] ?? null;
if (!$employee_id) {
    echo json_encode(['error' => 'Employee ID is required.']);
    exit;
}

$stmt = $conn->prepare("
    SELECT id 
    FROM attendance 
    WHERE employee_id = ? AND punch_out_time IS NULL
    LIMIT 1
");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(['punched_in' => true]);
} else {
    echo json_encode(['punched_in' => false]);
}

$stmt->close();
$conn->close();
?>
