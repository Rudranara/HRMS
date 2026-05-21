<?php
require 'header.php';
date_default_timezone_set('Asia/Kolkata'); // Set PHP timezone to IST
$employee_id = $_SESSION['employee_id'];
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require 'db_connection.php';
    $conn->query("SET time_zone = '+05:30'");

    $action = $_POST['action'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $selfie_data = $_POST['selfie_data'];

    // Decode and save the selfie image
    $target_dir = "../uploads/attendance_selfie/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    $selfie_image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $selfie_data));
    $target_file = $target_dir . uniqid() . ".jpg";
    file_put_contents($target_file, $selfie_image);

    // Fetch employee data including restriction status and office location
    $stmt = $conn->prepare("SELECT office, restriction_status, latitude AS emp_lat, longitude AS emp_lng FROM employees WHERE id = ?");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $employee = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $restriction_status = $employee['restriction_status'];
    $employee_office = $employee['office'];
    $emp_lat = $employee['emp_lat'];
    $emp_lng = $employee['emp_lng'];

    // Fetch office location from the offices table
    $stmt = $conn->prepare("SELECT latitude, longitude FROM offices WHERE CONCAT(office_name, '_', state_name) = ?");
    $stmt->bind_param("s", $employee_office);
    $stmt->execute();
    $office = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $office_lat = $office['latitude'];
    $office_lng = $office['longitude'];

    // Function to calculate distance between two coordinates
    function getDistance($lat1, $lng1, $lat2, $lng2) {
        $earth_radius = 6371000; // Radius of the earth in meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earth_radius * $c; // Distance in meters
    }

    // Check location restriction if enabled
    if ($restriction_status === 'Yes') {
        $distance = getDistance($latitude, $longitude, $office_lat, $office_lng);

        if ($distance > 500) {
            $message = "You are not in the office location!";
            $message_type = 'danger';
            echo json_encode(['message' => $message, 'message_type' => $message_type]);
            exit;
        }
    }

    $current_time = date('H:i:s');
    $today_date = date('Y-m-d');

    if ($action === 'punch_in') {
        $stmt = $conn->prepare("
            SELECT punch_in_time 
            FROM attendance 
            WHERE employee_id = ? AND DATE(punch_in_time) = ?
        ");
        $stmt->bind_param("is", $employee_id, $today_date);
        $stmt->execute();
        $stmt->bind_result($punch_in_time);
        $stmt->fetch();
        $stmt->close();

        if ($punch_in_time) {
            $message = "You have already punched in for today!";
            $message_type = 'danger';
        } else {
            $location_in = $latitude . "," . $longitude;
            $stmt = $conn->prepare("
                INSERT INTO attendance (employee_id, punch_in_time, location_in, selfie_in, current_location, current_location_updated_at, office, status) 
                VALUES (?, NOW(), ?, ?, ?, NOW(), ?, 'Present')
            ");
            $stmt->bind_param("issss", $employee_id, $location_in, $target_file, $location_in, $employee_office);
            $stmt->execute();
            $stmt->close();
            $message = "Punch-in successful!";
            $message_type = 'success';
        }
    } elseif ($action === 'punch_out') {
        $stmt = $conn->prepare("
            SELECT punch_in_time 
            FROM attendance 
            WHERE employee_id = ? AND punch_out_time IS NULL
        ");
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $stmt->bind_result($punch_in_time);
        $stmt->fetch();
        $stmt->close();

        if (!$punch_in_time) {
            $message = "You have not punched in or already punched out!";
            $message_type = 'danger';
        } else {
            $location_out = $latitude . "," . $longitude;
            $punch_out_time = date('Y-m-d H:i:s');
            $diff_seconds = strtotime($punch_out_time) - strtotime($punch_in_time);
            $hours = floor($diff_seconds / 3600);
            $minutes = round(($diff_seconds % 3600) / 60);
            $working_hours = $hours + ($minutes / 60);

            $stmt = $conn->prepare("
                UPDATE attendance 
                SET punch_out_time = NOW(), location_out = ?, selfie_out = ?, 
                    working_hours = ?, current_location = NULL, status = 'Present'
                WHERE employee_id = ? AND punch_out_time IS NULL
            ");
            $stmt->bind_param("ssdi", $location_out, $target_file, $working_hours, $employee_id);
            $stmt->execute();
            $stmt->close();
            $message = "Punch-out successful!";
            $message_type = 'success';
        }
    }

    echo json_encode(['message' => $message, 'message_type' => $message_type]);
}
?>
