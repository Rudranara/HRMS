<?php
require_once __DIR__ . '/../../db_connection.php'; // mysqli connection

session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') { http_response_code(403); exit; }
$pdo = db_connect();
$rows = $pdo->query('SELECT id,email,name,role,created_at FROM users')->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($rows);
