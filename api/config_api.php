<?php
// api/config_api.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/init.php';

// This file securely provides the public Google Maps API key to the frontend.
// It checks if the key is defined before sending it.
if (defined('GOOGLE_MAPS_API_KEY')) {
    echo json_encode(['google_maps_api_key' => GOOGLE_MAPS_API_KEY]);
} else {
    // Send an empty key if not defined, so the frontend can handle it gracefully.
    echo json_encode(['google_maps_api_key' => '']);
}
?>
