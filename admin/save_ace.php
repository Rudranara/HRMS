<?php
include 'db_connection.php';

// Required
$ace_id   = $_POST['ace_id'] ?? '';
$ace_code = trim($_POST['ace_code'] ?? '');
$name     = trim($_POST['name'] ?? '');
$phone    = trim($_POST['phone'] ?? '');

// Optional (NULL allowed)
$area        = $_POST['area']        !== '' ? $_POST['area'] : NULL;
$visit_price= $_POST['visit_price'] !== '' ? $_POST['visit_price'] : NULL;
$address    = $_POST['address']     !== '' ? $_POST['address'] : NULL;
$lat        = $_POST['lat']         !== '' ? $_POST['lat'] : NULL;
$lng        = $_POST['lng']         !== '' ? $_POST['lng'] : NULL;

if ($ace_id) {

    // UPDATE
    $stmt = $conn->prepare("
        UPDATE aces SET
            ace_code    = ?,
            name        = ?,
            phone       = ?,
            area        = ?,
            visit_price = ?,
            address     = ?,
            lat         = ?,
            lng         = ?
        WHERE ace_id = ?
    ");

    $stmt->bind_param(
        "ssssdssdi",
        $ace_code,
        $name,
        $phone,
        $area,
        $visit_price,
        $address,
        $lat,
        $lng,
        $ace_id
    );

} else {

    // INSERT
    $stmt = $conn->prepare("
        INSERT INTO aces
        (ace_code, name, phone, area, visit_price, address, lat, lng)
        VALUES (?,?,?,?,?,?,?,?)
    ");

    $stmt->bind_param(
        "ssssdssd",
        $ace_code,
        $name,
        $phone,
        $area,
        $visit_price,
        $address,
        $lat,
        $lng
    );
}

// Execute & redirect
$stmt->execute();
$stmt->close();

header("Location: admin_home?tab=aces");
exit;
