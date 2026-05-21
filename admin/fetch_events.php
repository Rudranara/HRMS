<?php
include("db_connection.php");

header('Content-Type: application/json');

$query = "SELECT id, title, start_date AS start, event_type FROM events";
$result = $conn->query($query);

$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'start' => $row['start'],
        'eventType' => $row['event_type'],
        'color' => $row['event_type'] === 'weekly_off' ? '#ff9f89' : '#89cff0'
    ];
}

echo json_encode($events);
?>
