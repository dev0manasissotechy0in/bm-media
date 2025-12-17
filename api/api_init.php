<?php
/**
 * API Initialization
 * Include this at the top of all API files to ensure clean JSON responses
 * Usage: require_once __DIR__ . '/../api_init.php';
 */

// Suppress ALL errors and warnings before output
error_reporting(0);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
@ini_set('log_errors', '1');
@ini_set('error_log', __DIR__ . '/../error.log');

// Start output buffering to catch any unwanted output
ob_start();

// Set JSON content type
header('Content-Type: application/json');

/**
 * Clean output and send JSON response
 * Call this before echo json_encode()
 */
function send_json_response($data, $status_code = 200) {
    http_response_code($status_code);
    ob_clean();
    echo json_encode($data);
    exit;
}

/**
 * Send error response
 */
function send_error_response($message, $status_code = 400, $details = null) {
    $response = [
        'success' => false,
        'message' => $message
    ];
    
    if ($details !== null) {
        $response['error'] = $details;
    }
    
    send_json_response($response, $status_code);
}

/**
 * Send success response
 */
function send_success_response($data, $message = null) {
    $response = ['success' => true];
    
    if ($message) {
        $response['message'] = $message;
    }
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    send_json_response($response, 200);
}
