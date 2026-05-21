<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

/* ================= AUTH CHECK ================= */
// if (!isset($_SESSION['employee_id'])) {
//     http_response_code(403);
//     exit;
// }

$employee_id = $_SESSION['employee_id'];

/* ================= INCLUDES ================= */
require_once __DIR__ . '/../../db_connection.php'; // worksheet/db_connection.php
require_once __DIR__ . '/../csrf.php';           // worksheet/csrf.php

/* ================= CSRF CHECK ================= */
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verify_csrf($token)) {
    http_response_code(403);
    echo json_encode(['error' => 'csrf']);
    exit;
}

/* ================= INPUT ================= */
$date  = $_POST['date']  ?? null;
$title = $_POST['title'] ?? null;

if (!$date || !$title) {
    http_response_code(400);
    echo json_encode(['error' => 'missing_params']);
    exit;
}

/* ================= DB LOGIC ================= */
/*
 personal_marks table structure:
 - employee_id (FK)
 - date (UNIQUE with employee_id)
 - title
*/

$stmt = $conn->prepare("
    INSERT INTO personal_marks (employee_id, date, title)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE title = VALUES(title)
");

$stmt->bind_param("iss", $employee_id, $date, $title);
$stmt->execute();

/* ================= RESPONSE ================= */
echo json_encode(['ok' => 1]);
