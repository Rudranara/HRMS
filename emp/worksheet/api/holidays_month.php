<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// if (!isset($_SESSION['employee_id'])) {
//     http_response_code(403);
//     exit;
// }

require_once __DIR__ . '/../../db_connection.php';

$employee_id = $_SESSION['employee_id'];

$m = $_GET['m'] ?? date('m');
$y = $_GET['y'] ?? date('Y');

$start = "$y-$m-01";
$end   = date('Y-m-t', strtotime($start));

$results = [];

/* -----------------------------
   GLOBAL HOLIDAYS
------------------------------*/
$stmt = $conn->prepare("
    SELECT date, title, 'global' AS scope
    FROM holidays
    WHERE date BETWEEN ? AND ?
");
$stmt->bind_param("ss", $start, $end);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $results[$row['date']] = $row; // key by date to avoid duplicates
}

/* -----------------------------
   PERSONAL MARKS (EMPLOYEE)
------------------------------*/
$stmt = $conn->prepare("
    SELECT date, title, 'personal' AS scope
    FROM personal_marks
    WHERE employee_id = ?
      AND date BETWEEN ? AND ?
");
$stmt->bind_param("iss", $employee_id, $start, $end);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    // only add if global holiday not already present
    if (!isset($results[$row['date']])) {
        $results[$row['date']] = $row;
    }
}

/* Return indexed array */
header('Content-Type: application/json');
echo json_encode(array_values($results));
