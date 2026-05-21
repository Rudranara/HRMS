<?php
require 'db_connection.php';

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$employee_id = intval($_GET['id']);
$stmt = $conn->prepare("
    SELECT location_in 
    FROM attendance 
    WHERE employee_id = ? AND punch_out_time IS NULL
    ORDER BY punch_in_time DESC 
    LIMIT 1
");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Location data unavailable or employee has punched out']);
    exit;
}

$location = $result->fetch_assoc();
echo json_encode(['location' => $location['location_in']]);
?>
