<?php
// Simple test endpoint to verify connectivity
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'success' => true,
    'message' => 'Connection successful!',
    'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'unknown',
    'client_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'server_time' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION
]);
?>
