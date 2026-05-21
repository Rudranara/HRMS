<?php
include("db_connection.php");
session_start();

date_default_timezone_set('Asia/Kolkata');

$user_id = $_SESSION['employee_id'];


$now     = date("Y-m-d H:i:s");
$today   = date("Y-m-d");
$is_auto_end = empty($_POST['end_lat']) || empty($_POST['end_lng']);
$end_lat = $is_auto_end ? null : floatval($_POST['end_lat']);
$end_lng = $is_auto_end ? null : floatval($_POST['end_lng']);


// Google API Key
$GOOGLE_API_KEY = "AIzaSyCH2j-8_qFXr-AwOdr9sgaEa0jQQHp0YZU";

/* ===============================
   1️⃣ CHECK ACTIVE JOURNEY
=============================== */
$check = mysqli_query($conn, "
    SELECT id, DATE(start_time) AS start_date
    FROM journey_start
    WHERE user_id = '$user_id'
      AND status = 'started'
    ORDER BY id DESC
    LIMIT 1
");

if (mysqli_num_rows($check) == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "No active journey found to end."
    ]);
    exit;
}

$row = mysqli_fetch_assoc($check);
$journey_id   = (int)$row['id'];
$start_date   = $row['start_date'];

/* ===============================
   2️⃣ DECIDE WHETHER TO ADD END KM
=============================== */

$calculate_end_km = (!$is_auto_end && $start_date === $today);
$extra_km = 0.0;

/* ===============================
   3️⃣ DISTANCE: LAST VISIT → END
   (ONLY IF STARTED TODAY)
=============================== */
if ($calculate_end_km) {

    $last = $conn->prepare("
        SELECT lat, lng
        FROM visits
        WHERE journey_id = ?
        ORDER BY visit_id DESC
        LIMIT 1
    ");
    $last->bind_param("i", $journey_id);
    $last->execute();
    $l = $last->get_result()->fetch_assoc();
    $last->close();

    if ($l) {
        $from_lat = $l['lat'];
        $from_lng = $l['lng'];

        $url = "https://maps.googleapis.com/maps/api/directions/json?"
             . "origin={$from_lat},{$from_lng}"
             . "&destination={$end_lat},{$end_lng}"
             . "&mode=driving"
             . "&key={$GOOGLE_API_KEY}";

        $response = @file_get_contents($url);
        $json = json_decode($response, true);

        if (!empty($json['routes'][0]['legs'][0]['distance']['value'])) {
            $extra_km = round(
                $json['routes'][0]['legs'][0]['distance']['value'] / 1000,
                2
            );
        }
    }
}

/* ===============================
   4️⃣ CALCULATE JOURNEY TOTAL
=============================== */
$sum_sql = "
    SELECT
        COALESCE(SUM(v.distance_from_prev_km),0) AS km,
        COALESCE(SUM(vendors.visit_price),0) AS vendor_amount,
        SUM(
            CASE
                WHEN v.visit_type = 'customer' THEN 100
                WHEN v.visit_type = 'other' THEN 50
                ELSE 0
            END
        ) AS customer_amount,
        (SELECT price_km FROM employees WHERE id='$user_id') AS km_rate
    FROM visits v
    LEFT JOIN vendors ON v.vendor_id = vendors.vendor_id
    WHERE v.journey_id = '$journey_id'
";

$sum_res = mysqli_query($conn, $sum_sql);
$sum = mysqli_fetch_assoc($sum_res);

/* Final KM */
$km = (float)$sum['km'] + $extra_km;

/* Price calculation */
$km_rate        = (float)$sum['km_rate'];
$vendor_amount  = (float)$sum['vendor_amount'];
$customer_amount= (float)$sum['customer_amount'];

$km_pay = $km * $km_rate;
$perday_price = $vendor_amount + $customer_amount + $km_pay;

/* ===============================
   5️⃣ UPDATE JOURNEY
=============================== */
$update = mysqli_query($conn, "
    UPDATE journey_start
    SET
        end_lat = " . ($end_lat === null ? "NULL" : "'$end_lat'") . ",
        end_lng = " . ($end_lng === null ? "NULL" : "'$end_lng'") . ",
        end_time = '$now',
        status = 'ended',
        end_km = '$extra_km',
        total_km = '$km',
        perday_price = '$perday_price'
    WHERE id = '$journey_id'
");



if (!$update) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to update journey end."
    ]);
    exit;
}

/* ===============================
   6️⃣ RESPONSE
=============================== */
echo json_encode([
    "status" => "ended",
    "perday_price" => round($perday_price, 2),
    "total_km" => round($km, 2),
    "end_km" => $extra_km,
    "message" => "Journey Ended Successfully"
]);
