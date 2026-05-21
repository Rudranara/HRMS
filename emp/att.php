<?php
require 'header.php';
date_default_timezone_set('Asia/Kolkata'); // Set PHP timezone to IST
$employee_id = $_SESSION['employee_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require 'db_connection.php';
    // Ensure MySQL uses the correct time zone
    $conn->query("SET time_zone = '+05:30'");

    $action = $_POST['action'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $selfie_data = $_POST['selfie_data'];

    // Decode and save the selfie image
    $target_dir = "../uploads/attendance_selfie/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true); // Ensure the directory exists
    }
    $selfie_image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $selfie_data));
    $target_file = $target_dir . uniqid() . ".jpg";
    file_put_contents($target_file, $selfie_image);

    // Check if employee already punched in/out today
    $today_date = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT punch_in_time, punch_out_time 
        FROM attendance 
        WHERE employee_id = ? AND DATE(punch_in_time) = ? 
        LIMIT 1
    ");
    $stmt->bind_param("is", $employee_id, $today_date);
    $stmt->execute();
    $stmt->bind_result($punch_in_time, $punch_out_time);
    $stmt->fetch();
    $stmt->close();

    if ($action === 'punch_in') {
        if ($punch_in_time) {
            // Already punched in today
            echo "<script>alert('You have already punched in for today!'); window.location.href = 'add_attendance';</script>";
            exit;
        }

        // Get employee's punch-in time
        $stmt = $conn->prepare("SELECT punchin_time FROM employees WHERE id = ?");
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $stmt->bind_result($expected_punchin_time);
        $stmt->fetch();
        $stmt->close();

        if (strtotime(date('H:i:s')) > strtotime($expected_punchin_time)) {
            // Late punch-in, show confirmation modal
            echo "<script>showConfirmationModal('You are late! Are you sure you want to punch in?', 'punch_in', '$latitude', '$longitude', '$selfie_data');</script>";
            exit;
        }

        $location_in = $latitude . "," . $longitude;

        // Insert Punch-In record
        $stmt = $conn->prepare("
            INSERT INTO attendance (employee_id, punch_in_time, location_in, selfie_in, current_location, current_location_updated_at) 
            VALUES (?, NOW(), ?, ?, ?, NOW())
        ");
        $stmt->bind_param("isss", $employee_id, $location_in, $target_file, $location_in);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'punch_out') {
        if (!$punch_in_time || $punch_out_time) {
            // Either not punched in or already punched out today
            echo "<script>alert('You have not punched in or already punched out for today!'); window.location.href = 'add_attendance';</script>";
            exit;
        }

        // Get employee's punch-out time
        $stmt = $conn->prepare("SELECT punchout_time FROM employees WHERE id = ?");
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $stmt->bind_result($expected_punchout_time);
        $stmt->fetch();
        $stmt->close();

        if (strtotime(date('H:i:s')) < strtotime($expected_punchout_time)) {
            // Early punch-out, show confirmation modal
            echo "<script>showConfirmationModal('You are trying to punch out early! This may affect your salary. Are you sure you want to punch out?', 'punch_out', '$latitude', '$longitude', '$selfie_data');</script>";
            exit;
        }
        $location_out = $latitude . "," . $longitude;
        // Calculate working hours
        $punch_out_time = date('Y-m-d H:i:s');
        $diff_seconds = strtotime($punch_out_time) - strtotime($punch_in_time);
        $hours = floor($diff_seconds / 3600); // Extract hours
        $minutes = round(($diff_seconds % 3600) / 60); // Extract remaining minutes
        $working_hours = $hours + ($minutes / 60); // Store as decimal for storage

        // Update Punch-Out record
        $stmt = $conn->prepare("
            UPDATE attendance 
            SET punch_out_time = NOW(), location_out = ?, selfie_out = ?, 
                working_hours = ?, current_location = NULL 
            WHERE employee_id = ? AND punch_out_time IS NULL
        ");
        $stmt->bind_param("ssdi", $location_out, $target_file, $working_hours, $employee_id);
        $stmt->execute();
        $stmt->close();

        // Update the employee's current location
        $update_stmt = $conn->prepare("
            UPDATE employees 
            SET current_location = NULL, current_location_updated_at = NOW() 
            WHERE id = ?
        ");
        $update_stmt->bind_param("i", $employee_id);
        $update_stmt->execute();
        $update_stmt->close();
    }

    // Redirect after form submission
    echo "<script>window.location.href = 'add_attendance?success=1';</script>";
    exit;
}
?>