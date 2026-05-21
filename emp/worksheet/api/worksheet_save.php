<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

session_start();

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['employee_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'unauth']);
    exit;
}

$employee_id = $_SESSION['employee_id'];

/* ================= INCLUDES ================= */
require_once __DIR__ . '/../../db_connection.php'; // mysqli connection
require_once __DIR__ . '/../csrf.php';           // csrf.php inside worksheet

if (function_exists('mysqli_set_charset')) {
    mysqli_set_charset($conn, 'utf8mb4');
}

/* ================= CSRF CHECK ================= */
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verify_csrf($token)) {
    http_response_code(403);
    echo json_encode(['error' => 'csrf']);
    exit;
}

/* ================= INPUT ================= */
$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);

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
$dataArray = is_array($payload['data']) ? $payload['data'] : [];

array_walk_recursive($dataArray, function (&$value) {
    if (!is_string($value)) {
        return;
    }

    $value = str_replace("\0", '', $value);
    if (function_exists('mb_check_encoding') && !mb_check_encoding($value, 'UTF-8')) {
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }
});

$jsonPayload = json_encode($dataArray, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
$status = $payload['status'] ?? 'saved';

if ($jsonPayload === false) {
    http_response_code(400);
    echo json_encode(['error' => 'json_encode_failed', 'message' => 'Worksheet content could not be saved.']);
    exit;
}

$data = 'b64:' . base64_encode($jsonPayload);

/* ================= CHECK EXISTENCE ================= */
$check = $conn->prepare("
    SELECT id
    FROM worksheets
    WHERE employee_id = ?
      AND date = ?
    LIMIT 1
");
$check->bind_param("is", $employee_id, $date);
$checkOk = $check->execute();

if (!$checkOk) {
    http_response_code(500);
    echo json_encode(['error' => 'check_failed', 'message' => 'Unable to verify worksheet record.']);
    exit;
}

$check->store_result();

/* ================= UPDATE / INSERT ================= */
if ($check->num_rows > 0) {

    $update = $conn->prepare("
        UPDATE worksheets
        SET data = ?, status = ?, updated_at = NOW()
        WHERE employee_id = ? AND date = ?
    ");

    if (!$update) {
        http_response_code(500);
        echo json_encode(['error' => 'prepare_update_failed', 'message' => 'Unable to prepare worksheet update.']);
        exit;
    }

    $update->bind_param("ssis", $data, $status, $employee_id, $date);
    if (!$update->execute()) {
        http_response_code(500);
        echo json_encode(['error' => 'update_failed', 'message' => 'Unable to update worksheet.']);
        exit;
    }

} else {

    $insert = $conn->prepare("
        INSERT INTO worksheets (employee_id, date, data, status)
        VALUES (?, ?, ?, ?)
    ");

    if (!$insert) {
        http_response_code(500);
        echo json_encode(['error' => 'prepare_insert_failed', 'message' => 'Unable to prepare worksheet save.']);
        exit;
    }

    $insert->bind_param("isss", $employee_id, $date, $data, $status);
    if (!$insert->execute()) {
        http_response_code(500);
        echo json_encode(['error' => 'insert_failed', 'message' => 'Unable to save worksheet.']);
        exit;
    }
}

/* ================= RESPONSE ================= */
echo json_encode(['ok' => 1, 'message' => 'Worksheet saved successfully.']);
