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
$lat     = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
$lng     = isset($_POST['lng']) ? floatval($_POST['lng']) : null;
$remarks = trim($_POST['remarks'] ?? '');

if ($lead_id <= 0 || $remarks === '') {
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
   INSERT VISIT
========================= */
$stmt = $conn->prepare("
    INSERT INTO lead_visits
    (lead_id, employee_id, lat, lng, remarks)
    VALUES (?,?,?,?,?)
");
$stmt->bind_param(
    "iidss",
    $lead_id,
    $employee_id,
    $lat,
    $lng,
    $remarks
);
$stmt->execute();
$stmt->close();

/* =========================
   ACTIVITY LOG
========================= */
$type = "Visit Added";
$text = "Visit added by employee";

$log = $conn->prepare("
    INSERT INTO lead_activities
    (lead_id, activity_type, activity_text, created_by)
    VALUES (?,?,?,?)
");
$log->bind_param(
    "issi",
    $lead_id,
    $type,
    $text,
    $employee_id
);
$log->execute();
$log->close();

/* =========================
   JSON SUCCESS RESPONSE
========================= */
echo json_encode([
    'status'  => 'success',
    'message' => 'Visit added successfully'
]);
exit;
