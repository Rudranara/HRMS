<?php
include("db_connection.php");

if (isset($_GET['task_id'])) {
    $task_id = $_GET['task_id'];

    $stmt = $conn->prepare("SELECT * FROM task_remarks WHERE task_id = ? ORDER BY created_at ASC");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $class = $row['user_type'] === 'Manager' ? 'admin-message' : 'employee-message';
        echo "<div class='$class'>";
        echo "<strong>" . ucfirst($row['user_type']) . ":</strong>";
        echo "<p>" . htmlspecialchars($row['remark']) . "</p>";
        echo "<small>" . htmlspecialchars($row['created_at']) . "</small>";
        echo "</div>";
    }
}
?>
