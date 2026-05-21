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
require_once __DIR__ . '/../csrf.php';

/* ================= CSRF CHECK ================= */
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!$token || !verify_csrf($token)) {
    http_response_code(403);
    echo json_encode(['error' => 'csrf']);
    exit;
}

/* ================= INPUT ================= */
$payload = json_decode(file_get_contents('php://input'), true);

if (
    !$payload ||
    empty($payload['date']) ||
    empty($payload['data']) ||
    empty($payload['employee_id'])
) {
    http_response_code(400);
    echo json_encode(['error' => 'bad']);
    exit;
}

$employee_id = (int)$payload['employee_id'];
$date   = $payload['date'];
$status = $payload['status'] ?? 'saved';
$data   = json_encode($payload['data']);

if (!$isAdmin && $sessionEmployeeId !== $employee_id) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'forbidden']);
    exit;
}

/* ================= CHECK EXIST ================= */
$stmt = $conn->prepare("
    SELECT id
    FROM worksheets
    WHERE employee_id = ?
      AND date = ?
    LIMIT 1
");
$stmt->bind_param("is", $employee_id, $date);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {

    /* -------- UPDATE -------- */
    $stmt->close();

    $update = $conn->prepare("
        UPDATE worksheets
        SET data = ?, status = ?, updated_at = NOW()
        WHERE employee_id = ?
          AND date = ?
    ");
    $update->bind_param("ssis", $data, $status, $employee_id, $date);
    $update->execute();
    $update->close();

} else {

    /* -------- INSERT -------- */
    $stmt->close();

    $insert = $conn->prepare("
        INSERT INTO worksheets (employee_id, date, data, status)
        VALUES (?, ?, ?, ?)
    ");
    $insert->bind_param("isss", $employee_id, $date, $data, $status);
    $insert->execute();
    $insert->close();
}

/* ================= RESPONSE ================= */
header('Content-Type: application/json');
echo json_encode(['ok' => 1]);
