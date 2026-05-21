<?php
include("db_connection.php");

if (isset($_GET['task_id'])) {
    $task_id = $_GET['task_id'];

    $remarks_query = $conn->prepare("SELECT * FROM task_remarks WHERE task_id = ? ORDER BY created_at ASC");
    $remarks_query->bind_param("i", $task_id);
    $remarks_query->execute();
    $remarks_result = $remarks_query->get_result();
    $remarks = $remarks_result->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['remarks' => $remarks]);
} else {
    echo json_encode(['remarks' => []]);
}
?>
