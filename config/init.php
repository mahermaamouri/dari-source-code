<?php
// config/init.php

// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Error Reporting (Development vs Production)
define('DEVELOPMENT_MODE', true); // Set to false in production

if (DEVELOPMENT_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    // Consider setting up a proper error logger for production
    // ini_set('log_errors', 1);
    // ini_set('error_log', '/path/to/your/php-error.log');
}

// --- Define Base Path ---
// This creates a reliable, root-relative path to the project folder.
// It calculates the path from the document root to the project's parent directory.
$document_root = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$project_dir = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
$base_path = str_replace($document_root, '', $project_dir);
define('BASE_PATH', rtrim($base_path, '/') . '/');


// --- Define Other Paths ---
define('ROOT_PATH', dirname(__DIR__)); // The physical 'project/' directory path on the server
define('CONFIG_PATH', ROOT_PATH . '/config');
define('API_PATH', ROOT_PATH . '/api');
define('TEMPLATES_PATH', ROOT_PATH . '/templates');
define('PAGES_PATH', ROOT_PATH . '/pages');
define('ADMIN_PATH', ROOT_PATH . '/admin');
define('ASSETS_PATH', BASE_PATH . 'assets'); // The web-accessible path for assets (CSS, JS, images)

// Timezone
date_default_timezone_set('Africa/Tunis'); // Set to your desired timezone

// Include Database Configuration
require_once CONFIG_PATH . '/database.php';

// Global PDO instance (from database.php)
// You can access it as $pdo globally after this file is included.
global $pdo;
$pdo = connect_db();

?>
