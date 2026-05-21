<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// if (!isset($_SESSION['employee_id'])) {
//     http_response_code(403);
//     exit;
// }

require_once __DIR__ . '/../../db_connection.php';
require_once __DIR__ . '/../csrf.php';
/* CSRF check */
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf'] ?? '');
if (!verify_csrf($token)) {
    http_response_code(403);
    echo json_encode(['error' => 'csrf']);
    exit;
}

/* Validate input */
$date = $_POST['date'] ?? null;
if (!$date) {
    http_response_code(400);
    echo json_encode(['error' => 'missing_date']);
    exit;
}

$employee_id = $_SESSION['employee_id'];

/* Delete ONLY employee’s personal mark */
$stmt = $conn->prepare("
    DELETE FROM personal_marks
    WHERE employee_id = ?
      AND date = ?
");
$stmt->bind_param("is", $employee_id, $date);
$stmt->execute();

echo json_encode(['ok' => 1]);
