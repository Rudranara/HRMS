<?php
session_start();
// Unset only admin session variables (do not destroy employee session)
unset(
    $_SESSION['admin_logged_in'],
    $_SESSION['admin_id'],
    $_SESSION['admin_name']
);
// Redirect to the admin login page
header("Location: index");
exit;
?>
