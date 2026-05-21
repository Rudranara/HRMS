<?php
include 'db_connection.php';

$ace_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($ace_id > 0) {
    $stmt = $conn->prepare("DELETE FROM aces WHERE ace_id = ?");
    $stmt->bind_param("i", $ace_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: admin_home?tab=aces");
exit;
