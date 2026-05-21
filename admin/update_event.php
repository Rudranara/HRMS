<?php
include("db_connection.php");

$id = $_POST['id'];
$title = $_POST['title'];
$event_type = $_POST['event_type'];
$event_date = $_POST['start_date'];

$stmt = $conn->prepare("UPDATE events SET title = ?, event_type = ?, start_date = ? WHERE id = ?");
$stmt->bind_param("sssi", $title, $event_type, $event_date, $id);

if ($stmt->execute()) {
    echo "Event updated successfully!";
} else {
    echo "Failed to update event.";
}
?>
