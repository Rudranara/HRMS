<?php
include 'db_connection.php';

// Safety check
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_home?tab=subdealers");
    exit;
}

$id = (int) $_GET['id'];

// Delete record
$conn->query("DELETE FROM sub_dealers WHERE sub_dealer_id = $id");

// Redirect back
header("Location: admin_home?tab=subdealers");
exit;
