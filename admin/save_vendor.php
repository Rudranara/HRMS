<?php
include("db_connection.php");

// Collect form data safely
$id           = $_POST['vendor_id'] ?? '';
$name         = trim($_POST['name']);
$dealer_code  = trim($_POST['dealer_code'] ?? '');
$aso_name     = trim($_POST['aso_name'] ?? '');
$area         = trim($_POST['area']);
$address      = trim($_POST['address']);
$lat          = floatval($_POST['lat']);
$lng          = floatval($_POST['lng']);
$visit_price  = isset($_POST['visit_price']) ? floatval($_POST['visit_price']) : 0;

// Validate required fields
if (empty($name) || empty($address) || empty($area)) {
    die("Missing required fields");
}

if ($id) {

    // UPDATE
    $stmt = $conn->prepare("
        UPDATE vendors
        SET 
            name = ?, 
            dealer_code = ?, 
            aso_name = ?,
            lat = ?, 
            lng = ?, 
            area = ?, 
            address = ?, 
            visit_price = ?
        WHERE vendor_id = ?
    ");

    
    $stmt->bind_param(
        "sssddssdi",
        $name,
        $dealer_code,
        $aso_name,
        $lat,
        $lng,
        $area,
        $address,
        $visit_price,
        $id
    );

} else {

    // INSERT
    $stmt = $conn->prepare("
        INSERT INTO vendors 
        (name, dealer_code, aso_name, lat, lng, area, address, visit_price)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    
    $stmt->bind_param(
        "sssddssd",
        $name,
        $dealer_code,
        $aso_name,
        $lat,
        $lng,
        $area,
        $address,
        $visit_price
    );
}

if ($stmt->execute()) {
    header("Location: admin_home?tab=vendors");
    exit;
} else {
    echo "Database Error: " . $stmt->error;
}

$stmt->close();
?>
