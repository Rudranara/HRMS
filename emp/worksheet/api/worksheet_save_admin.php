<?php
require_once __DIR__ . '/../../db_connection.php'; // mysqli connection
require_once __DIR__ . '/../csrf.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') { http_response_code(403); echo json_encode(['error'=>'unauth']); exit; }
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!$token || !verify_csrf($token)) { http_response_code(403); echo json_encode(['error'=>'csrf']); exit; }
$payload = json_decode(file_get_contents('php://input'), true);
if (!$payload || !isset($payload['date']) || !isset($payload['data']) || !isset($payload['user_id'])) { http_response_code(400); echo json_encode(['error'=>'bad']); exit; }
$user_id = intval($payload['user_id']);
$pdo = db_connect();
$stmt = $pdo->prepare('SELECT id FROM worksheets WHERE user_id = ? AND date = ?');
$stmt->execute([$user_id,$payload['date']]);
if ($stmt->fetch()) {
    $u = $pdo->prepare('UPDATE worksheets SET data = ?, status = ?, updated_at = NOW() WHERE user_id = ? AND date = ?');
    $u->execute([json_encode($payload['data']), $payload['status'] ?? 'saved', $user_id, $payload['date']]);
} else {
    $i = $pdo->prepare('INSERT INTO worksheets (user_id,date,data,status) VALUES (?,?,?,?)');
    $i->execute([$user_id,$payload['date'],json_encode($payload['data']), $payload['status'] ?? 'saved']);
}
echo json_encode(['ok'=>1]);
