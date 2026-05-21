<?php
include("db_connection.php");

$user_id = $_POST['id'];
$name = $_POST['name'];
$phone = $_POST['phone'];
$price_km = $_POST['price_km'];

if (!$user_id || !$name || !$phone || !$price_km) {
    die("Missing required fields.");
}

$stmt = $conn->prepare("UPDATE employees SET name=?, phone=?, price_km=? WHERE id=?");
$stmt->bind_param("ssdi", $name, $phone, $price_km, $user_id);
$stmt->execute();
$stmt->close();

header("Location: admin_home");
exit;
?>
