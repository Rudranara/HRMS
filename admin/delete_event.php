<?php
include("db_connection.php");

$id = $_POST['id'];

$stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo "Event deleted successfully!";
} else {
    echo "Failed to delete event.";
}
?>
