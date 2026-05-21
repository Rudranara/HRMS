<?php
session_start();
require 'db_connection.php';

/* =========================
   AUTH CHECK
========================= */
if (!isset($_SESSION['employee_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$employee_id = $_SESSION['employee_id'];

/* =========================
   INPUT VALIDATION
========================= */
$lead_id = isset($_POST['lead_id']) ? intval($_POST['lead_id']) : 0;
$status  = trim($_POST['status'] ?? '');

$allowedStatus = [
    'New',
    'Contacted',
    'Follow-up',
    'Interested',
    'Not Interested',
    'Converted',
    'Lost'
];

if ($lead_id <= 0 || !in_array($status, $allowedStatus)) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Invalid input'
    ]);
    exit;
}

/* =========================
   VERIFY LEAD OWNERSHIP
========================= */
$check = $conn->prepare("
    SELECT id 
    FROM leads 
    WHERE id = ? AND assigned_to = ?
");
$check->bind_param("ii", $lead_id, $employee_id);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
    $check->close();
    echo json_encode([
        'status'  => 'error',
        'message' => 'Access denied'
    ]);
    exit;
}
$check->close();

/* =========================
   UPDATE STATUS
========================= */
$update = $conn->prepare("
    UPDATE leads 
    SET lead_status = ? 
    WHERE id = ?
");
$update->bind_param("si", $status, $lead_id);
$update->execute();
$update->close();

/* =========================
   ACTIVITY LOG
========================= */
$activity_type = "Status Updated";
$activity_text = "Lead status changed to <b>{$status}</b>";

$log = $conn->prepare("
    INSERT INTO lead_activities
    (lead_id, activity_type, activity_text, created_by)
    VALUES (?,?,?,?)
");
$log->bind_param(
    "issi",
    $lead_id,
    $activity_type,
    $activity_text,
    $employee_id
);
$log->execute();
$log->close();

/* =========================
   JSON SUCCESS RESPONSE
========================= */
echo json_encode([
    'status'  => 'success',
    'message' => 'Status updated successfully',
    'new_status' => $status
]);
exit;
