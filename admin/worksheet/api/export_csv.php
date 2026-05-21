<?php
require_once __DIR__ . '/../../app/db.php';
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') { http_response_code(403); echo 'Forbidden'; exit; }
$m = $_GET['m'] ?? date('m');
$y = $_GET['y'] ?? date('Y');
$start = "$y-".str_pad($m,2,'0',STR_PAD_LEFT)."-01";
$end = date('Y-m-t', strtotime($start));
$pdo = db_connect();
$stmt = $pdo->prepare('SELECT w.date,u.email,u.name,w.status,w.data FROM worksheets w JOIN users u ON u.id = w.user_id WHERE w.date BETWEEN ? AND ? ORDER BY w.date,u.email');
$stmt->execute([$start,$end]);

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="worksheets_'.$y.'_'.$m.'.csv"');
$out = fopen('php://output','w');
fputcsv($out, ['date','user_email','user_name','status','data_json']);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($out, [$row['date'],$row['email'],$row['name'],$row['status'],$row['data']]);
}
fclose($out);
exit;
