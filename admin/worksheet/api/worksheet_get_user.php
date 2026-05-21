<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$sessionEmployeeId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

if (!$isAdmin && $sessionEmployeeId <= 0) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'forbidden']);
    exit;
}

require_once __DIR__ . '/../../db_connection.php';

/* ================= INPUT ================= */
$employee_id = intval($_GET['employee_id'] ?? 0);
$date = $_GET['date'] ?? date('Y-m-d');

if ($employee_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid employee']);
    exit;
}

if (!$isAdmin && $sessionEmployeeId !== $employee_id) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'forbidden']);
    exit;
}

/* ================= QUERY ================= */
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

$stmt->close();

/* ================= RESPONSE ================= */
header('Content-Type: application/json');
echo json_encode($row ?: null);
