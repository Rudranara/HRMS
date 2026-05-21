<?php
require 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $attendance_id = $_POST['attendance_id'];
    $location = $_POST['location']; // Format: "latitude,longitude"

    if (!empty($attendance_id) && !empty($location)) {
        $stmt = $conn->prepare("INSERT INTO realtime_location_updates (attendance_id, location) VALUES (?, ?)");
        $stmt->bind_param("is", $attendance_id, $location);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Location updated successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to update location."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Invalid data provided."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>
