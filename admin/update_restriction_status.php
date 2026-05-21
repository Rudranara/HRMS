<?php
include("db_connection.php");

if (isset($_POST['employee_id'], $_POST['restriction_status'])) {
    $employee_id = $_POST['employee_id'];
    $restriction_status = $_POST['restriction_status'];

    $stmt = $conn->prepare("UPDATE employees SET restriction_status = ? WHERE employee_id = ?");
    $stmt->bind_param("ss", $restriction_status, $employee_id);

    if ($stmt->execute()) {
        echo "Status updated successfully!";
    } else {
        echo "Failed to update status.";
    }
}
?>
