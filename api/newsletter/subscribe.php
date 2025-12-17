<?php
/**
 * Newsletter Subscription API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/config.php';
require_once '../../includes/Newsletter.php';

try {
    $email = $_POST['email'] ?? null;
    $name = $_POST['name'] ?? null;
    $preferences = json_decode($_POST['preferences'] ?? '[]', true);
    
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Valid email address is required');
    }
    
    $result = subscribeToNewsletter($email, $name, $preferences);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
