<?php
require_once __DIR__ . '/../../db_connection.php'; // mysqli connection

session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') { http_response_code(403); exit; }
$m = $_GET['m'] ?? date('m');
$y = $_GET['y'] ?? date('Y');
$start = "$y-$m-01";
$end = date('Y-m-t', strtotime($start));
$pdo = db_connect();
$users = $pdo->query('SELECT id,name FROM users WHERE role = "employee"')->fetchAll(PDO::FETCH_ASSOC);
$out = [];
foreach ($users as $u) {
    $stmt = $pdo->prepare('SELECT COUNT(*) as total, SUM(status = "submitted") as submitted FROM worksheets WHERE user_id = ? AND date BETWEEN ? AND ?');
    $stmt->execute([$u['id'],$start,$end]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    $out[] = ['name'=>$u['name'],'total'=>intval($r['total']),'submitted'=>intval($r['submitted'])];
}
header('Content-Type: application/json');
echo json_encode($out);
