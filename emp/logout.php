<?php
session_start();
// Unset only employee session variables (do not destroy admin session)
unset(
    $_SESSION['employee_logged_in'],
    $_SESSION['employee_id'],
    $_SESSION['employee_name'],
    $_SESSION['employee_unique_id'],
    $_SESSION['employee_email'],
    $_SESSION['employee_role'],
    $_SESSION['employee_designation'],
    $_SESSION['employee_photo'],
    $_SESSION['remember_me'],
    $_SESSION['LOGIN_TIME'],
    $_SESSION['LAST_ACTIVITY']
);
// Redirect to the login page
header("Location: ../index");
exit;
?>
