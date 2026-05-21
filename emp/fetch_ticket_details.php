<?php
require 'db_connection.php';
session_start();

if (!isset($_SESSION['employee_id']) || !isset($_GET['id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$ticket_id = $_GET['id'];
$employee_id = $_SESSION['employee_id'];

$stmt = $conn->prepare("SELECT * FROM tickets WHERE id = ? AND employee_id = ?");
$stmt->bind_param("ii", $ticket_id, $employee_id);
$stmt->execute();
$result = $stmt->get_result();
if ($ticket = $result->fetch_assoc()) {
    echo json_encode($ticket);
} else {
    echo json_encode(['error' => 'Ticket not found']);
}
?>