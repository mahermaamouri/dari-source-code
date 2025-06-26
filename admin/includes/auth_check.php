<?php
// admin/includes/auth_check.php
// This script checks if an admin user is logged in.

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the admin_id session variable is set.
if (!isset($_SESSION['admin_id'])) {
    // Store the intended destination to redirect after login.
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];

    // Redirect to the login page.
    header('Location: ' . BASE_PATH . 'admin/login.php');
    exit();
}

// Check for session timeout.
$timeout_duration = 1800; // 30 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();     // Unset $_SESSION variable
    session_destroy();   // Destroy session data
    
    header('Location: ' . BASE_PATH . 'admin/login.php?status=session_expired');
    exit();
}
$_SESSION['last_activity'] = time(); // Update last activity time stamp

?>
