<?php
require 'db_connection.php';

header('Content-Type: application/json');

$employee_id = strtoupper(trim($_GET['employee_id'] ?? ''));

$response = ['exists' => false];

if (!empty($employee_id)) {
    $stmt = $conn->prepare("SELECT id FROM employees WHERE employee_id = ?");
    $stmt->bind_param("s", $employee_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $response['exists'] = true;
    }

    $stmt->close();
}

echo json_encode($response);
?>