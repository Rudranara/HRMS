<?php
require_once __DIR__ . '/../../db_connection.php'; // mysqli connection

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') { http_response_code(403); exit; }
$user_id = intval($_GET['user_id'] ?? 0);
$m = $_GET['m'] ?? date('m');
$y = $_GET['y'] ?? date('Y');
$start = "$y-$m-01";
$pdo = db_connect();
$end = date('Y-m-t', strtotime($start));
$stmt = $pdo->prepare('SELECT date, data, status FROM worksheets WHERE user_id = ? AND date BETWEEN ? AND ?');
$stmt->execute([$user_id, $start, $end]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($rows);
