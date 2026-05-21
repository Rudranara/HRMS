<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
//     http_response_code(403);
//     exit;
// }

require_once __DIR__ . '/../../db_connection.php';

$employee_id = intval($_GET['employee_id'] ?? 0);
if (!$employee_id) {
    echo json_encode([]);
    exit;
}

$m = $_GET['m'] ?? date('m');
$y = $_GET['y'] ?? date('Y');

$start = "$y-$m-01";
$end   = date('Y-m-t', strtotime($start));

$results = [];

/* GLOBAL HOLIDAYS */
$stmt = $conn->prepare("
    SELECT date, title
    FROM holidays
    WHERE date BETWEEN ? AND ?
");
$stmt->bind_param("ss", $start, $end);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $results[$row['date']] = $row;
}

/* PERSONAL MARKS — ONLY FOR SELECTED EMPLOYEE */
$stmt = $conn->prepare("
    SELECT date, title
    FROM personal_marks
    WHERE employee_id = ?
      AND date BETWEEN ? AND ?
");
$stmt->bind_param("iss", $employee_id, $start, $end);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    if (!isset($results[$row['date']])) {
        $results[$row['date']] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode(array_values($results));
