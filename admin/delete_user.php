<?php
include("db_connection.php");

if (!isset($_GET['id'])) {
    die("Invalid request");
}

$user_id = intval($_GET['id']); // prevents SQL injection

// Delete user
$sql = "DELETE FROM employees WHERE id = $user_id";

if (mysqli_query($conn, $sql)) {
    header("Location: admin_home?msg=deleted");
    exit();
} else {
    echo "Error deleting user: " . mysqli_error($conn);
}
?>
