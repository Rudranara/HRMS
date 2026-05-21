<?php
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/csrf.php';
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') { http_response_code(403); exit; }
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf'] ?? null);
if (!verify_csrf($token)) { http_response_code(403); echo json_encode(['error'=>'csrf']); exit; }
$id = $_POST['id'] ?? null;
$email = $_POST['email'] ?? '';
$name = $_POST['name'] ?? '';
$role = $_POST['role'] ?? 'employee';
$password = $_POST['password'] ?? null;
$pdo = db_connect();
if ($id) {
    $stmt = $pdo->prepare('UPDATE users SET email=?,name=?,role=?'.($password? ',password=?':''). ' WHERE id=?');
    $params = [$email,$name,$role];
    if ($password) $params[] = password_hash($password,PASSWORD_DEFAULT);
    $params[] = $id;
    $stmt->execute($params);
} else {
    $stmt = $pdo->prepare('INSERT INTO users (email,password,name,role) VALUES (?,?,?,?)');
    $stmt->execute([$email,password_hash($password?:'changeme',PASSWORD_DEFAULT),$name,$role]);
}
echo json_encode(['ok'=>1]);
