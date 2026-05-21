<?php

include("db_connection.php");
session_start();



header('Content-Type: application/json');

date_default_timezone_set('Asia/Kolkata');


// Radius check
        function distanceM($a1,$b1,$a2,$b2){
            $R=6371000;
            return $R*2*asin(
                sqrt(
                    pow(sin(deg2rad(($a2-$a1)/2)),2) +
                    cos(deg2rad($a1))*cos(deg2rad($a2))*pow(sin(deg2rad(($b2-$b1)/2)),2)
                )
            );
        }

// Google API Key
$GOOGLE_API_KEY = "AIzaSyCH2j-8_qFXr-AwOdr9sgaEa0jQQHp0YZU";

function saveCompressedVisitPhoto(array $file, string $folder, string $filenameBase, int $targetBytes = 10240): string
{
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new Exception("Photo upload failed");
    }

    if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
        $fallbackPath = $folder . $filenameBase . '.jpg';
        if (!move_uploaded_file($file['tmp_name'], $fallbackPath)) {
            throw new Exception("Photo upload failed");
        }
        return $fallbackPath;
    }

    $imageData = file_get_contents($file['tmp_name']);
    if ($imageData === false) {
        throw new Exception("Unable to read uploaded photo");
    }

    $sourceImage = @imagecreatefromstring($imageData);
    if (!$sourceImage) {
        throw new Exception("Invalid image content");
    }

    $originalWidth = imagesx($sourceImage);
    $originalHeight = imagesy($sourceImage);
    $sizeSteps = [960, 820, 720, 640, 560, 480, 420, 360, 320, 280, 240, 200];
    $qualitySteps = [55, 40, 30, 22, 16, 12, 8, 5];
    $finalPath = $folder . $filenameBase . '.jpg';
    $bestBinary = null;

    foreach ($sizeSteps as $maxDimension) {
        $ratio = min(1, $maxDimension / max($originalWidth, $originalHeight));
        $newWidth = max(1, (int) round($originalWidth * $ratio));
        $newHeight = max(1, (int) round($originalHeight * $ratio));

        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        $background = imagecolorallocate($resizedImage, 255, 255, 255);
        imagefill($resizedImage, 0, 0, $background);
        imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        foreach ($qualitySteps as $quality) {
            ob_start();
            imagejpeg($resizedImage, null, $quality);
            $binary = (string) ob_get_clean();

            if ($bestBinary === null || strlen($binary) < strlen($bestBinary)) {
                $bestBinary = $binary;
            }

            if (strlen($binary) <= $targetBytes) {
                file_put_contents($finalPath, $binary);
                imagedestroy($resizedImage);
                imagedestroy($sourceImage);
                return $finalPath;
            }
        }

        imagedestroy($resizedImage);
    }

    imagedestroy($sourceImage);

    if ($bestBinary === null || file_put_contents($finalPath, $bestBinary) === false) {
        throw new Exception("Photo upload failed");
    }

    return $finalPath;
}




try {

    if (!isset($_SESSION['employee_id'])) {
        throw new Exception("Not authenticated");
    }

    $user_id = intval($_SESSION['employee_id']);
    $type    = $_POST['visit_type'] ?? null;
    $lat     = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
    $lng     = isset($_POST['lng']) ? floatval($_POST['lng']) : null;

    if ($lat === null || $lng === null) {
        throw new Exception("GPS location missing");
    }


    //  Get today's active journey 
        $js = $conn->prepare("
            SELECT id 
            FROM journey_start
            WHERE user_id = ?
              AND status = 'started'
              AND DATE(start_time) = CURDATE()
            ORDER BY id DESC
            LIMIT 1
        ");

        $js->bind_param("i", $user_id);
        $js->execute();
        $res = $js->get_result();
        $journey = $res->fetch_assoc();
        $js->close();

        if (!$journey) {
            throw new Exception("No active journey found for today");
        }

        $journey_id = intval($journey['id']);




    /* -------------------------------
        PHOTO UPLOAD
    -------------------------------*/
    $photoPath = "";
    if (!empty($_FILES['photo']['name'])) {

        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png'];
        if (!in_array($ext, $allowed)) {
            throw new Exception("Invalid image format");
        }

        $folder = __DIR__ . "/uploads/visits/";
        if (!file_exists($folder)) mkdir($folder, 0775, true);

        $filenameBase = time() . "_" . bin2hex(random_bytes(4));
        $dest = saveCompressedVisitPhoto($_FILES['photo'], $folder, $filenameBase);
        $photoPath = "uploads/visits/" . basename($dest);
    }

    /* ----------------------------------------
        IF TYPE = VENDOR
    ----------------------------------------*/
    if ($type === "vendor") {

        $vendor_id = intval($_POST['vendor_id'] ?? 0);
        if (!$vendor_id) {
            throw new Exception("Vendor is required");
        }

        // Prevent duplicate visit today
        $chk = $conn->prepare("
            SELECT visit_id 
            FROM visits 
            WHERE user_id = ? 
              AND vendor_id = ?
              AND visit_type = 'vendor'
              AND DATE(created_at) = CURDATE()
            LIMIT 1
        ");

        $chk->bind_param("ii", $user_id, $vendor_id);
        $chk->execute();
        $today = $chk->get_result();
        if ($today->num_rows > 0) {
            echo json_encode([
                "status" => "error",
                "message" => "You have already visited this vendor today."
            ]);
            exit;
        }

        // Fetch vendor
        $q = $conn->prepare("SELECT lat,lng,area FROM vendors WHERE vendor_id=?");
        $q->bind_param("i", $vendor_id);
        $q->execute();
        $r = $q->get_result();
        if ($r->num_rows == 0) throw new Exception("Vendor not found");

        $vendor = $r->fetch_assoc();

        

        if (!empty($vendor['lat']) && !empty($vendor['lng']) && !empty($vendor['area'])) {

            $dist = distanceM($lat, $lng, $vendor['lat'], $vendor['lng']);

            if ($dist > $vendor['area']) {
                echo json_encode([
                    "status" => "error",
                    "message" => "You are outside vendor radius"
                ]);
                exit;
            }
        }


        $customer_name = null;
        $customer_mobile = null;
        $purpose       = null;
        $customer_addr = null;
    }

    /* ----------------------------------------
        IF TYPE = CUSTOMER
    ----------------------------------------*/
    else if ($type === "customer") {

        $customer_name = trim($_POST['customer_name'] ?? "");
        $customer_mobile = trim($_POST['customer_mobile'] ?? "");
        $customer_addr = trim($_POST['customer_address'] ?? "");
        $purpose       = trim($_POST['purpose'] ?? "");

        if ($customer_name=="" || $purpose=="" || $customer_addr=="") {
            throw new Exception("Customer name, address & purpose are required");
        }

        if ($customer_mobile !== "" && !preg_match('/^[0-9]{10}$/', $customer_mobile)) {
        throw new Exception("Invalid customer mobile number");
        }

        //  Prevent duplicate customer visit (same day)
        $chk = $conn->prepare("
            SELECT visit_id
            FROM visits
            WHERE user_id = ?
              AND visit_type = 'customer'
              AND customer_name = ?
              AND customer_address = ?
              AND DATE(created_at) = CURDATE()
            LIMIT 1
        ");
        $chk->bind_param("iss", $user_id, $customer_name, $customer_addr);
        $chk->execute();

        if ($chk->get_result()->num_rows > 0) {
            echo json_encode([
                "status" => "error",
                "message" => "This customer has already been visited today."
            ]);
            exit;
        }


        $vendor_id = null;
    }

    else if ($type === "other") {

        $customer_name = trim($_POST['visitor_name'] ?? "");
        $customer_addr = trim($_POST['visitor_address'] ?? "");
        $purpose       = trim($_POST['purpose'] ?? "");

        if ($customer_name === "" || $customer_addr === "" || $purpose === "") {
            throw new Exception("Visitor name, address & reason are required");
        }

        //  Prevent duplicate other visit (same day)
        $chk = $conn->prepare("
            SELECT visit_id
            FROM visits
            WHERE user_id = ?
              AND visit_type = 'other'
              AND customer_name = ?
              AND customer_address = ?
              AND purpose = ?
              AND DATE(created_at) = CURDATE()
            LIMIT 1
        ");
        $chk->bind_param("isss", $user_id, $customer_name, $customer_addr, $purpose);
        $chk->execute();

        if ($chk->get_result()->num_rows > 0) {
            echo json_encode([
                "status" => "error",
                "message" => "This visit has already been recorded today."
            ]);
            exit;
        }


        $customer_mobile = null;   // not required
        $vendor_id = null;
    }


    else if ($type === "subdealer") {

        $sub_dealer_id = intval($_POST['sub_dealer_id'] ?? 0);
        if (!$sub_dealer_id) {
            throw new Exception("Sub Dealer is required");
        }

        // Prevent duplicate sub dealer visit today
        $chk = $conn->prepare("
            SELECT visit_id
            FROM visits
            
            WHERE user_id = ?
              AND sub_dealer_id = ?
              AND visit_type = 'subdealer'

              AND DATE(created_at) = CURDATE()
            LIMIT 1
        ");
        $chk->bind_param("ii", $user_id, $sub_dealer_id);
        $chk->execute();

        if ($chk->get_result()->num_rows > 0) {
            echo json_encode([
                "status" => "error",
                "message" => "You have already visited this Sub Dealer today."
            ]);
            exit;
        }

        // Fetch sub dealer location + radius
        $q = $conn->prepare("SELECT lat,lng,area FROM sub_dealers WHERE sub_dealer_id=?");
        $q->bind_param("i", $sub_dealer_id);
        $q->execute();
        $r = $q->get_result();
        if ($r->num_rows == 0) throw new Exception("Sub Dealer not found");

        $sd = $r->fetch_assoc();

        if (!empty($sd['lat']) && !empty($sd['lng']) && !empty($sd['area'])) {

            $dist = distanceM($lat, $lng, $sd['lat'], $sd['lng']);

            if ($dist > $sd['area']) {
                echo json_encode([
                    "status" => "error",
                    "message" => "You are outside Sub Dealer radius"
                ]);
                exit;
            }
        }


        // map to common columns
        $sub_dealer_id = intval($_POST['sub_dealer_id']);
        $vendor_id = null;
        $ace_id = null;
        $customer_name   = null;
        $customer_mobile = null;
        $customer_addr   = null;
        $purpose         = null;
    }

    else if ($type === "ace") {

        $ace_id = intval($_POST['ace_id'] ?? 0);
        if (!$ace_id) {
            throw new Exception("ACE is required");
        }

        // Prevent duplicate ACE visit today
        $chk = $conn->prepare("
            SELECT visit_id
            FROM visits
            WHERE user_id = ?
              AND ace_id = ?
              AND visit_type = 'ace'

              AND DATE(created_at) = CURDATE()
            LIMIT 1
        ");
        $chk->bind_param("ii", $user_id, $ace_id);
        $chk->execute();

        if ($chk->get_result()->num_rows > 0) {
            echo json_encode([
                "status" => "error",
                "message" => "You have already visited this ACE today."
            ]);
            exit;
        }

        // Fetch ACE location + radius
        $q = $conn->prepare("SELECT lat,lng,area FROM aces WHERE ace_id=?");
        $q->bind_param("i", $ace_id);
        $q->execute();
        $r = $q->get_result();
        if ($r->num_rows == 0) throw new Exception("ACE not found");

        $ace = $r->fetch_assoc();

        if (!empty($ace['lat']) && !empty($ace['lng']) && !empty($ace['area'])) {

            $dist = distanceM($lat, $lng, $ace['lat'], $ace['lng']);

            if ($dist > $ace['area']) {
                echo json_encode([
                    "status" => "error",
                    "message" => "You are outside ACE radius"
                ]);
                exit;
            }
        }


        // map to common columns
        $ace_id = intval($_POST['ace_id']);
        $vendor_id = null;
        $sub_dealer_id = null;

        $customer_name   = null;
        $customer_mobile = null;
        $customer_addr   = null;
        $purpose         = null;
    }

    else {
        throw new Exception("Invalid visit type");
    }

/* ----------------------------------------
   KM CALCULATION (TODAY JOURNEY ONLY)
----------------------------------------*/

    $distance_km = 0.0;

    $prev_lat = null;
    $prev_lng = null;

    /* 1️⃣ LAST VISIT — TODAY ONLY */
    $prev = $conn->prepare("
        SELECT lat, lng
        FROM visits
        WHERE user_id = ?
          AND DATE(created_at) = CURDATE()
        ORDER BY visit_id DESC
        LIMIT 1
    ");
    $prev->bind_param("i", $user_id);
    $prev->execute();
    $p = $prev->get_result()->fetch_assoc();
    $prev->close();

    if ($p) {
        $prev_lat = (float)$p['lat'];
        $prev_lng = (float)$p['lng'];
    }

    /* 2️⃣ FALLBACK → TODAY JOURNEY START */
    if ($prev_lat === null || $prev_lng === null) {

        $js = $conn->prepare("
            SELECT start_lat, start_lng
            FROM journey_start
            WHERE user_id = ?
              AND DATE(start_time) = CURDATE()
            ORDER BY id DESC
            LIMIT 1
        ");
        $js->bind_param("i", $user_id);
        $js->execute();
        $j = $js->get_result()->fetch_assoc();
        $js->close();

        if ($j) {
            $prev_lat = (float)$j['start_lat'];
            $prev_lng = (float)$j['start_lng'];
        }
    }

    /* 3️⃣ GOOGLE DIRECTIONS API (TRAFFIC + MULTI-ROUTE) */
    if ($prev_lat !== null && $prev_lng !== null) {

        $url = "https://maps.googleapis.com/maps/api/directions/json?"
             . "origin={$prev_lat},{$prev_lng}"
             . "&destination={$lat},{$lng}"
             . "&mode=driving"
             . "&departure_time=now"
             . "&traffic_model=best_guess"
             . "&alternatives=true"
             . "&key={$GOOGLE_API_KEY}";

        $response = @file_get_contents($url);

        if ($response !== false) {

            $json = json_decode($response, true);
            $minMeters = null;

            if (!empty($json['routes'])) {
                foreach ($json['routes'] as $route) {
                    $meters = $route['legs'][0]['distance']['value'] ?? null;
                    if ($meters !== null) {
                        if ($minMeters === null || $meters < $minMeters) {
                            $minMeters = $meters;
                        }
                    }
                }
            }

            if ($minMeters !== null) {
                $distance_km = round($minMeters / 1000, 2);
            }
        }
    }


    /* ----------------------------------------
        INSERT INTO DATABASE
    ----------------------------------------*/

    $now = date("Y-m-d H:i:s");

    

    $stmt = $conn->prepare("
            INSERT INTO visits (
                user_id,
                journey_id,
                visit_type,
                vendor_id,
                sub_dealer_id,
                ace_id,
                customer_name,
                customer_mobile,
                customer_address,
                purpose,
                lat,
                lng,
                distance_from_prev_km,
                arrival_time,
                photo_path
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");

        $stmt->bind_param(
            "iisiiissssdddss",
            $user_id,
            $journey_id,
            $type,
            $vendor_id,
            $sub_dealer_id,
            $ace_id,
            $customer_name,
            $customer_mobile,
            $customer_addr,
            $purpose,
            $lat,
            $lng,
            $distance_km,
            $now,
            $photoPath
        );




    if (!$stmt->execute()) {
        throw new Exception("DB Insert Failed: " . $stmt->error);
    }

    $_SESSION['journey_reached'] = true;

    echo json_encode([
        "status" => "recorded",
        "message" => "Visit recorded successfully",
        "distance_km" => $distance_km
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

?>
