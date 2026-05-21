<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// if (!isset($_SESSION['employee_id'])) {
//     http_response_code(403);
//     exit;
// }

require_once __DIR__ . '/../../db_connection.php';

/* ================= FETCH EMPLOYEES ================= */
$result = $conn->query("
    SELECT 
        id,
        name,
        email,
        role,
        created_at
    FROM employees
    ORDER BY name ASC
");

$rows = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
}

/* ================= RESPONSE ================= */
header('Content-Type: application/json');
echo json_encode($rows);
