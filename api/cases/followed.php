<?php
/**
 * CASE THREADS - FOLLOWED CASES API ENDPOINT
 * Get user's followed cases
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // TODO: Get user ID from authentication token
    $userId = 1; // Replace with actual user authentication
    
    // Get followed cases
    $query = "
        SELECT 
            ct.id, ct.title, ct.slug, ct.short_description,
            ct.status, ct.category, ct.primary_location,
            ct.thumbnail, ct.total_articles, ct.total_followers,
            cf.followed_at, cf.notify_new_articles, cf.notify_timeline_updates
        FROM case_follows cf
        JOIN case_threads ct ON cf.case_id = ct.id
        WHERE cf.user_id = ?
        ORDER BY cf.followed_at DESC
    ";
    
    $cases = $db->fetchAll($query, [$userId]);
    
    // Format cases
    foreach ($cases as &$case) {
        $case['id'] = (int)$case['id'];
        $case['total_articles'] = (int)$case['total_articles'];
        $case['total_followers'] = (int)$case['total_followers'];
        $case['is_following'] = true;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'cases' => $cases,
            'total' => count($cases)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Database error in followed cases: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'DATABASE_ERROR',
        'message' => 'Failed to load followed cases'
    ]);
} catch (Exception $e) {
    error_log("Error in followed cases: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'SERVER_ERROR',
        'message' => 'An error occurred'
    ]);
}
