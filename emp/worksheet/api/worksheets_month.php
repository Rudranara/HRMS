<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['employee_id'])) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../../db_connection.php';

$employee_id = $_SESSION['employee_id'];

$m = $_GET['m'] ?? date('m');
$y = $_GET['y'] ?? date('Y');

$start = "$y-$m-01";
$end   = date('Y-m-t', strtotime($start));

$stmt = $conn->prepare("
    SELECT date, status, data
    FROM worksheets
    WHERE employee_id = ?
      AND date BETWEEN ? AND ?
");

$stmt->bind_param("iss", $employee_id, $start, $end);
$stmt->execute();

$result = $stmt->get_result();
$rows = [];

while ($row = $result->fetch_assoc()) {
    if (isset($row['data']) && is_string($row['data']) && strpos($row['data'], 'b64:') === 0) {
        $decoded = base64_decode(substr($row['data'], 4), true);
        if ($decoded !== false) {
            $row['data'] = $decoded;
        }
    }
    $rows[] = $row;
}

header('Content-Type: application/json');
echo json_encode($rows);
