<?php
// admin/index.php
// Main entry point for the admin section.

require_once __DIR__ . '/../config/init.php';

// Check if the admin is already logged in.
if (isset($_SESSION['admin_id'])) {
    // If logged in, redirect to the dashboard.
    header('Location: ' . BASE_PATH . 'admin/dashboard.php');
    exit();
} else {
    // If not logged in, redirect to the login page.
    header('Location: ' . BASE_PATH . 'admin/login.php');
    exit();
}
?>
