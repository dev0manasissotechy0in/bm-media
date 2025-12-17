<?php
/**
 * Push Notifications API - Subscribe/Unsubscribe
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/config.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $action = $input['action'] ?? null;
    $subscription = $input['subscription'] ?? null;
    
    if (!$action || !$subscription) {
        throw new Exception('Invalid request');
    }
    
    $db = Database::getInstance();
    
    $endpoint = $subscription['endpoint'] ?? null;
    $keys = $subscription['keys'] ?? null;
    
    if (!$endpoint || !$keys) {
        throw new Exception('Invalid subscription data');
    }
    
    if ($action === 'subscribe') {
        // Check if subscription already exists
        $existing = $db->fetchOne(
            "SELECT * FROM push_subscriptions WHERE endpoint = ?", 
            [$endpoint]
        );
        
        if ($existing) {
            // Update existing subscription
            $db->execute("
                UPDATE push_subscriptions 
                SET p256dh_key = ?, 
                    auth_key = ?, 
                    updated_at = NOW(),
                    status = 'active'
                WHERE endpoint = ?
            ", [
                $keys['p256dh'],
                $keys['auth'],
                $endpoint
            ]);
        } else {
            // Insert new subscription
            $db->execute("
                INSERT INTO push_subscriptions 
                (endpoint, p256dh_key, auth_key, user_agent, ip_address, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ", [
                $endpoint,
                $keys['p256dh'],
                $keys['auth'],
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $_SERVER['REMOTE_ADDR']
            ]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Successfully subscribed to notifications'
        ]);
        
    } elseif ($action === 'unsubscribe') {
        // Mark subscription as inactive
        $db->execute("
            UPDATE push_subscriptions 
            SET status = 'inactive', updated_at = NOW() 
            WHERE endpoint = ?
        ", [$endpoint]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Successfully unsubscribed from notifications'
        ]);
    } else {
        throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
