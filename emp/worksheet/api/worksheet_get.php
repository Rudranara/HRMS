<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['employee_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'unauth']);
    exit;
}

$employee_id = $_SESSION['employee_id'];

/* ================= INCLUDES ================= */
require_once __DIR__ . '/../../db_connection.php';

/* ================= INPUT ================= */
$date = $_GET['date'] ?? date('Y-m-d');

/* ================= DB QUERY ================= */
$stmt = $conn->prepare("
    SELECT *
    FROM worksheets
    WHERE employee_id = ?
      AND date = ?
    LIMIT 1
");

$stmt->bind_param("is", $employee_id, $date);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row && isset($row['data']) && is_string($row['data']) && strpos($row['data'], 'b64:') === 0) {
  $decoded = base64_decode(substr($row['data'], 4), true);
  if ($decoded !== false) {
    $row['data'] = $decoded;
  }
}

/* ================= RESPONSE ================= */
header('Content-Type: application/json');
echo json_encode($row ?: null);
