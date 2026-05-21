<?php
session_start();
require 'db_connection.php';

/* =========================
   ADMIN AUTH CHECK
========================= */
if (!isset($_SESSION['admin_id'])) {
    exit("Admin login required");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: manage_leads");
    exit;
}

/* =========================
   INPUT VALIDATION
========================= */
$lead_id = isset($_POST['lead_id']) ? intval($_POST['lead_id']) : 0;
$assigned_to = isset($_POST['assigned_to']) && $_POST['assigned_to'] !== ''
    ? intval($_POST['assigned_to'])
    : null;

if ($lead_id <= 0) {
    header("Location: manage_leads");
    exit;
}

/* =========================
   VERIFY LEAD EXISTS
========================= */
$checkLead = $conn->prepare("SELECT id FROM leads WHERE id = ?");
$checkLead->bind_param("i", $lead_id);
$checkLead->execute();
$checkLead->store_result();

if ($checkLead->num_rows === 0) {
    $checkLead->close();
    header("Location: manage_leads");
    exit;
}
$checkLead->close();

/* =========================
   UPDATE LEAD ASSIGNMENT
========================= */
if ($assigned_to === null) {
    $update = $conn->prepare("
        UPDATE leads 
        SET assigned_to = NULL 
        WHERE id = ?
    ");
    $update->bind_param("i", $lead_id);
} else {
    $update = $conn->prepare("
        UPDATE leads 
        SET assigned_to = ? 
        WHERE id = ?
    ");
    $update->bind_param("ii", $assigned_to, $lead_id);
}

$update->execute();
$update->close();

/* =========================
   GET EMPLOYEE NAME (OPTIONAL)
========================= */
$employeeName = null;

if ($assigned_to !== null) {
    $emp = $conn->prepare("SELECT name FROM employees WHERE id = ?");
    $emp->bind_param("i", $assigned_to);
    $emp->execute();
    $empRes = $emp->get_result()->fetch_assoc();
    $employeeName = $empRes['name'] ?? null;
    $emp->close();
}

/* =========================
   ACTIVITY LOG (ADMIN)
========================= */
$activity_type = "Lead Assigned";

if ($assigned_to === null) {
    $activity_text = "Lead unassigned by admin";
} else {
    $activity_text = "Lead assigned to <b>" . htmlspecialchars($employeeName) . "</b> by admin";
}

/* Admin is NOT an employee */
//$created_by = null;
$created_by_role = $_SESSION['admin_id'];

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
    $created_by_role
);

$log->execute();
$log->close();

/* =========================
   REDIRECT WITH SUCCESS
========================= */
header("Location: manage_leads?assigned=1");
exit;
