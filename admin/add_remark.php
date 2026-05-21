<?php
include("db_connection.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $task_id = $data['task_id'];
    $remark = $data['remark'];
    $user_type = $data['user_type'];

    $stmt = $conn->prepare("INSERT INTO task_remarks (task_id, remark, user_type) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $task_id, $remark, $user_type);
    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "remark" => htmlspecialchars($remark),
            "timestamp" => date("Y-m-d H:i:s"),
        ]);
    } else {
        echo json_encode(["success" => false]);
    }
}
?>
