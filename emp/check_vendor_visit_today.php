<?php
include("db_connection.php");
session_start();

$user_id = $_SESSION['employee_id'];
$vendor_id = intval($_GET['vendor_id']);

$q = mysqli_query($conn,"
    SELECT visit_id FROM visits 
    WHERE user_id='$user_id' 
    AND vendor_id='$vendor_id'
    AND DATE(created_at)=CURDATE()
");

echo json_encode([
    "visited" => mysqli_num_rows($q) > 0
]);
