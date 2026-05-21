<?php
include("db_connection.php");

$title = $_POST['title'];
$event_type = $_POST['event_type'];
$event_date = $_POST['start_date'];

$stmt = $conn->prepare("INSERT INTO events (title, event_type, start_date) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $title, $event_type, $event_date);

if ($stmt->execute()) {
    echo "Event added successfully!";
} else {
    echo "Failed to add event.";
}
?>
