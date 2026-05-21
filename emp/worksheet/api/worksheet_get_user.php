<?php
require_once __DIR__ . '/../../db_connection.php'; // mysqli connection

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') { http_response_code(403); echo json_encode(['error'=>'unauth']); exit; }
$user_id = intval($_GET['user_id'] ?? 0);
$date = $_GET['date'] ?? date('Y-m-d');
$pdo = db_connect();
$stmt = $pdo->prepare('SELECT * FROM worksheets WHERE user_id = ? AND date = ?');
$stmt->execute([$user_id,$date]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($row ?: null);
