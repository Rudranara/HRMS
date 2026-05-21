<?php
include("db_connection.php");

$employee_id = $_POST['employee_id'];
$price_km    = $_POST['price_km'];

$stmt = $conn->prepare("UPDATE employees SET price_km = ? WHERE employee_id = ?");
$stmt->bind_param("ds", $price_km, $employee_id);
$stmt->execute();

header("Location: manage_employee");
exit;
