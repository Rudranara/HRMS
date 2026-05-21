
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
$m = $_GET['m'] ?? date('m');
$y = $_GET['y'] ?? date('Y');

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

$start = "$y-$m-01";
$end   = date('Y-m-t', strtotime($start));

/* ================= QUERY ================= */
$stmt = $conn->prepare("
    SELECT 
        date,
        data,
        status
    FROM worksheets
    WHERE employee_id = ?
      AND date BETWEEN ? AND ?
    ORDER BY date ASC
");

$stmt->bind_param("iss", $employee_id, $start, $end);
$stmt->execute();

$result = $stmt->get_result();
$rows   = [];

while ($row = $result->fetch_assoc()) {
    if (isset($row['data']) && is_string($row['data']) && strpos($row['data'], 'b64:') === 0) {
        $decoded = base64_decode(substr($row['data'], 4), true);
        if ($decoded !== false) {
            $row['data'] = $decoded;
        }
    }
    $rows[] = $row;
}

$stmt->close();

/* ================= RESPONSE ================= */
header('Content-Type: application/json');
echo json_encode($rows);
