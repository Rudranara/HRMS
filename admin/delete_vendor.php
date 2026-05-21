<?php
include("db_connection.php");

$id = intval($_GET['id']);
mysqli_query($conn, "DELETE FROM vendors WHERE vendor_id = '$id'");
header("Location: admin_home");
exit;
?>
