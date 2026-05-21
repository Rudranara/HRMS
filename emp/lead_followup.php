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
$lead_id       = isset($_POST['lead_id']) ? intval($_POST['lead_id']) : 0;
$followup_type = trim($_POST['followup_type'] ?? '');
$followup_date = $_POST['followup_date'] ?? '';
$remarks       = trim($_POST['remarks'] ?? '');

if (
    $lead_id <= 0 ||
    $followup_type === '' ||
    $followup_date === '' ||
    $remarks === ''
) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'All fields are required'
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
   INSERT FOLLOW-UP
========================= */
$stmt = $conn->prepare("
    INSERT INTO lead_followups
    (lead_id, followup_type, followup_date, remarks, created_by)
    VALUES (?,?,?,?,?)
");
$stmt->bind_param(
    "isssi",
    $lead_id,
    $followup_type,
    $followup_date,
    $remarks,
    $employee_id
);
$stmt->execute();
$stmt->close();

/* =========================
   ACTIVITY LOG
========================= */
$activity_type = "Follow-up Added";
$activity_text = "Follow-up added ({$followup_type})";

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
    'message' => 'Follow-up added successfully'
]);
exit;
