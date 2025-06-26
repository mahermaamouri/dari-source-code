<?php
// config/database.php

// Database credentials
define('DB_HOST', 'localhost'); // Hostname without the port
define('DB_PORT', '');      // Port is now separate
define('DB_NAME', 'dari');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Data Source Name (DSN) for PDO - CORRECTED FORMAT
$dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

// PDO options (this part remains the same)
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

/**
 * Establishes a database connection using PDO.
 *
 * @return PDO|null Returns a PDO instance on success, or null on failure.
 */
function connect_db() {
    global $dsn, $options; // Access global DSN and options
// New debugging code for connect_db()
try {
    $pdo_instance = new PDO($dsn, DB_USER, DB_PASS, $options);
    return $pdo_instance;
} catch (PDOException $e) {
    // --- START DEBUGGING CODE ---
    // This will stop the script and output a clear JSON error message
    // that you can see in your browser's developer tools (Network tab).
    http_response_code(500); // Set response code to 500
    header('Content-Type: application/json'); // Ensure the browser interprets it as JSON
    echo json_encode([
        'success' => false,
        'message' => 'Database Connection Failed. See error_details.',
        // The actual error message from the server:
        'error_details' => $e->getMessage(),
        // This shows the exact connection string that was attempted:
        'dsn_used' => $dsn
    ]);
    exit(); // Stop the script immediately
    // --- END DEBUGGING CODE ---
}}

// The global $pdo variable will be set in init.php after calling connect_db()
// Example usage in other files after including init.php:
// global $pdo;
// $stmt = $pdo->query('SELECT * FROM Admins');
// $admins = $stmt->fetchAll();
?>
