<?php
$timeout = 60 * 60 * 48; // 48 hours

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', $timeout);
    ini_set('session.gc_probability', 1);
    ini_set('session.gc_divisor', 100);

    session_set_cookie_params([
        'lifetime' => $timeout,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

if (empty($_SESSION['employee_logged_in'])) {
    header("Location: index");
    exit;
}

// Absolute expiry after 48 hours
if (isset($_SESSION['LOGIN_TIME']) && (time() - $_SESSION['LOGIN_TIME']) > $timeout) {
    // Unset only employee session variables
    unset(
        $_SESSION['employee_logged_in'],
        $_SESSION['employee_id'],
        $_SESSION['employee_unique_id'],
        $_SESSION['employee_name'],
        $_SESSION['employee_email'],
        $_SESSION['employee_role'],
        $_SESSION['employee_designation'],
        $_SESSION['employee_photo'],
        $_SESSION['remember_me'],
        $_SESSION['LOGIN_TIME'],
        $_SESSION['LAST_ACTIVITY']
    );
    header("Location: index");
    exit;
}

// Optional activity update
$_SESSION['LAST_ACTIVITY'] = time();
