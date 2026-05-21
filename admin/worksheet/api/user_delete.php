<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/csrf.php';
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') { http_response_code(403); exit; }
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf'] ?? null);
if (!verify_csrf($token)) { http_response_code(403); echo json_encode(['error'=>'csrf']); exit; }
$id = $_POST['id'] ?? null;
if (!$id) { http_response_code(400); exit; }
$pdo = db_connect();
$stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
$stmt->execute([$id]);
echo json_encode(['ok'=>1]);
