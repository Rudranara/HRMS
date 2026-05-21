<?php
include("db_connection.php");

$name = $_POST['name'];
$phone = $_POST['phone'];
$price_km = $_POST['price_km'];

// Optional basic validation
if (!$name || !$phone || !$price_km) {
    die("Missing required fields.");
}

$stmt = $conn->prepare("INSERT INTO employees (name, phone, price_km) VALUES (?, ?, ?)");
$stmt->bind_param("ssd", $name, $phone, $price_km);
$stmt->execute();
$stmt->close();

header("Location: admin_home");
exit;
?>
