<?php
include('db_connection.php'); // Include your DB connection

if (isset($_POST['office_id'])) { // 'office_id' here is actually 'office_name_state_name'
    $office_info = $_POST['office_id'];

    // Split the "office_name_state_name" string
    list($office_name, $state_name) = explode('_', $office_info);

    $stmt = $conn->prepare("SELECT latitude, longitude FROM offices WHERE office_name = ? AND state_name = ?");
    $stmt->bind_param('ss', $office_name, $state_name); // Both are strings
    $stmt->execute();
    $result = $stmt->get_result();

    if ($office = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'latitude' => $office['latitude'],
            'longitude' => $office['longitude']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Office not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
