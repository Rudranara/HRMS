<?php
include("db_connection.php");
session_start();
header('Content-Type: application/json; charset=utf-8');

$user_id = $_SESSION['employee_id'] ?? 0;
if (!$user_id) {
    echo json_encode(["status"=>"ended"]); exit;
}

$q = $conn->prepare("SELECT status FROM journey_start WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$q->bind_param("i", $user_id);
$q->execute();
$res = $q->get_result();
if ($res->num_rows == 0) {
    echo json_encode(["status"=>"ended"]);
} else {
    $r = $res->fetch_assoc();
    echo json_encode(["status"=>$r['status']]);
}
$q->close();
?>
