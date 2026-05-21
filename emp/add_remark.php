<?php
include("db_connection.php");

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['task_id'], $data['remark'], $data['user_type'])) {
    $task_id = $data['task_id'];
    $remark = $data['remark'];
    $user_type = $data['user_type'];

    $stmt = $conn->prepare("INSERT INTO task_remarks (task_id, remark, user_type, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $task_id, $remark, $user_type);

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "remark" => $remark,
            "timestamp" => date("Y-m-d H:i:s")
        ]);
    } else {
        echo json_encode(["success" => false]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid data!"]);
}
?>
