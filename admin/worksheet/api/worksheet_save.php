<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

/* ================= AUTH CHECK ================= */
// if (!isset($_SESSION['employee_id'])) {
//     http_response_code(403);
//     echo json_encode(['error' => 'unauth']);
//     exit;
// }

$employee_id = $_SESSION['employee_id'];

/* ================= INCLUDES ================= */
require_once __DIR__ . '/../../db_connection.php'; // mysqli connection
require_once __DIR__ . '/../csrf.php';           // csrf.php inside worksheet

/* ================= CSRF CHECK ================= */
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verify_csrf($token)) {
    http_response_code(403);
    echo json_encode(['error' => 'csrf']);
    exit;
}

/* ================= INPUT ================= */
$payload = json_decode(file_get_contents('php://input'), true);

if (
    !$payload ||
    empty($payload['date']) ||
    !isset($payload['data'])
) {
    http_response_code(400);
    echo json_encode(['error' => 'bad']);
    exit;
}

$date   = $payload['date'];
$data   = json_encode($payload['data']);
$status = $payload['status'] ?? 'saved';

/* ================= CHECK EXISTENCE ================= */
$check = $conn->prepare("
    SELECT id
    FROM worksheets
    WHERE employee_id = ?
      AND date = ?
    LIMIT 1
");
$check->bind_param("is", $employee_id, $date);
$check->execute();
$check->store_result();

/* ================= UPDATE / INSERT ================= */
if ($check->num_rows > 0) {

    $update = $conn->prepare("
        UPDATE worksheets
        SET data = ?, status = ?, updated_at = NOW()
        WHERE employee_id = ? AND date = ?
    ");
    $update->bind_param("ssis", $data, $status, $employee_id, $date);
    $update->execute();

} else {

    $insert = $conn->prepare("
        INSERT INTO worksheets (employee_id, date, data, status)
        VALUES (?, ?, ?, ?)
    ");
    $insert->bind_param("isss", $employee_id, $date, $data, $status);
    $insert->execute();
}

/* ================= RESPONSE ================= */
echo json_encode(['ok' => 1]);
