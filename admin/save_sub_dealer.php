<?php
include 'db_connection.php';

$id = $_POST['sub_dealer_id'] ?? '';

$sub_dealer_code = $_POST['sub_dealer_code'] ?? '';
$name  = $_POST['name'] ?? '';
$phone = $_POST['phone'] ?? '';

$area        = $_POST['area'] !== '' ? $_POST['area'] : NULL;
$visit_price= $_POST['visit_price'] !== '' ? $_POST['visit_price'] : NULL;
$address     = $_POST['address'] !== '' ? $_POST['address'] : NULL;
$lat         = $_POST['lat'] !== '' ? $_POST['lat'] : NULL;
$lng         = $_POST['lng'] !== '' ? $_POST['lng'] : NULL;

if ($id) {
    $stmt = $conn->prepare("
        UPDATE sub_dealers SET
        sub_dealer_code=?,
        name=?,
        phone=?,
        area=?,
        visit_price=?,
        address=?,
        lat=?,
        lng=?
        WHERE sub_dealer_id=?
    ");
    $stmt->bind_param(
        "ssssdsddi",
        $sub_dealer_code,
        $name,
        $phone,
        $area,
        $visit_price,
        $address,
        $lat,
        $lng,
        $id
    );
} else {
    $stmt = $conn->prepare("
        INSERT INTO sub_dealers
        (sub_dealer_code, name, phone, area, visit_price, address, lat, lng)
        VALUES (?,?,?,?,?,?,?,?)
    ");
    $stmt->bind_param(
        "ssssdsdd",
        $sub_dealer_code,
        $name,
        $phone,
        $area,
        $visit_price,
        $address,
        $lat,
        $lng
    );
}

$stmt->execute();
header("Location: admin_home?tab=subdealers");
