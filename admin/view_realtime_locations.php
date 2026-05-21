<?php
session_start();
require 'db_connection.php';
date_default_timezone_set('Asia/Kolkata');

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: index");
    exit;
}

// Get employee ID from the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Invalid request.";
    exit;
}

$attendance_id = intval($_GET['id']);

// Fetch the latest location data of the employee
$stmt = $conn->prepare("
    SELECT 
        e.name AS employee_name, 
        a.location_in, 
        a.location_out 
    FROM attendance a
    JOIN employees e ON a.employee_id = e.id
    WHERE a.id = ?
");
$stmt->bind_param("i", $attendance_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "No location data found for the selected employee.";
    exit;
}

$attendance = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real-Time Location</title>
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY"></script>
    <style>
        #map {
            height: 500px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>Real-Time Location of <?= htmlspecialchars($attendance['employee_name']) ?></h2>
        <div id="map"></div>
    </div>

    <script>
    let map, marker;

    function initMap() {
        map = new google.maps.Map(document.getElementById("map"), {
            zoom: 15,
            center: { lat: 20.5937, lng: 78.9629 }, // Default center (India)
        });

        marker = new google.maps.Marker({
            map: map,
        });

        // Start fetching real-time location
        fetchLocation();
    }

    function fetchLocation() {
        const employeeId = <?= json_encode($attendance_id) ?>;

        fetch(`get_employee_location?id=${employeeId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                } else if (data.location) {
                    const [lat, lng] = data.location.split(",").map(parseFloat);
                    const position = { lat, lng };

                    // Update marker position and map center
                    marker.setPosition(position);
                    map.setCenter(position);
                }
            })
            .catch(error => console.error("Error fetching location:", error));

        // Fetch location every 5 seconds
        setTimeout(fetchLocation, 5000);
    }

    // Initialize the map
    window.onload = initMap;
    </script>
</body>
</html>
