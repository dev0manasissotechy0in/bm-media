<?php
/**
 * Contact Form Submission API - For Mobile App
 * Handles contact form submissions from the mobile app
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/config.php';
require_once '../../includes/Database.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON data'
        ]);
        exit;
    }
    
    // Extract and validate data
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $subject = trim($data['subject'] ?? '');
    $message = trim($data['message'] ?? '');
    
    $errors = [];
    
    // Validation
    if (empty($name)) {
        $errors[] = 'Name is required';
    } elseif (strlen($name) < 2) {
        $errors[] = 'Name must be at least 2 characters';
    } elseif (strlen($name) > 100) {
        $errors[] = 'Name must not exceed 100 characters';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address';
    }
    
    if (empty($subject)) {
        $errors[] = 'Subject is required';
    } elseif (strlen($subject) < 3) {
        $errors[] = 'Subject must be at least 3 characters';
    } elseif (strlen($subject) > 200) {
        $errors[] = 'Subject must not exceed 200 characters';
    }
    
    if (empty($message)) {
        $errors[] = 'Message is required';
    } elseif (strlen($message) < 10) {
        $errors[] = 'Message must be at least 10 characters';
    } elseif (strlen($message) > 5000) {
        $errors[] = 'Message must not exceed 5000 characters';
    }
    
    // Check for spam patterns
    $spam_keywords = ['viagra', 'cialis', 'casino', 'lottery', 'prize', 'winner'];
    $content_lower = strtolower($message . ' ' . $subject);
    foreach ($spam_keywords as $keyword) {
        if (strpos($content_lower, $keyword) !== false) {
            $errors[] = 'Your message contains prohibited content';
            break;
        }
    }
    
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors
        ]);
        exit;
    }
    
    // Rate limiting - check for recent submissions from same IP
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $recent_submission = $db->fetchOne(
        "SELECT id FROM contact_queries 
         WHERE ip_address = ? 
         AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)",
        [$ip_address]
    );
    
    if ($recent_submission) {
        echo json_encode([
            'success' => false,
            'message' => 'Please wait 5 minutes before submitting another message'
        ]);
        exit;
    }
    
    // Check for duplicate submissions
    $duplicate = $db->fetchOne(
        "SELECT id FROM contact_queries 
         WHERE email = ? 
         AND message = ? 
         AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
        [$email, $message]
    );
    
    if ($duplicate) {
        echo json_encode([
            'success' => false,
            'message' => 'This message has already been submitted'
        ]);
        exit;
    }
    
    // Save to database
    $query_data = [
        'name' => $name,
        'email' => $email,
        'subject' => $subject,
        'message' => $message,
        'ip_address' => $ip_address,
        'source' => 'app', // Mark as coming from app
        'is_read' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $inserted_id = $db->insert('contact_queries', $query_data);
    
    if ($inserted_id) {
        // Try to send email notification (optional, won't fail if email sending fails)
        try {
            require_once '../../includes/EmailService.php';
            $emailService = new EmailService();
            
            $contactData = array_merge($query_data, [
                'id' => $inserted_id,
                'source_label' => 'Mobile App'
            ]);
            
            $emailService->sendContactNotification($contactData);
        } catch (Exception $e) {
            // Log error but don't fail the API call
            error_log("Failed to send contact email notification: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Thank you for contacting us! We will get back to you soon.',
            'query_id' => $inserted_id
        ]);
    } else {
        throw new Exception('Failed to save contact query');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again later.',
        'error' => DEV_MODE ? $e->getMessage() : null
    ]);
}
