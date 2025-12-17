<?php
/**
 * NOTIFICATIONS - LIST API ENDPOINT
 * Get user notifications with filtering
 */

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Check if user is logged in
    if (!Security::isLoggedIn('user')) {
        echo json_encode([
            'success' => true,
            'data' => [
                'notifications' => [],
                'unread_count' => 0,
                'total' => 0,
                'page' => 1,
                'per_page' => 20
            ]
        ]);
        exit;
    }
    
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'];
    
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = isset($_GET['per_page']) ? min(50, max(1, (int)$_GET['per_page'])) : 20;
    $offset = ($page - 1) * $perPage;
    
    // Filters
    $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
    $notificationType = isset($_GET['type']) ? $_GET['type'] : null;
    
    // Check if notifications table exists
    $tableExists = $db->fetchOne("SHOW TABLES LIKE 'notifications'");
    if (!$tableExists) {
        echo json_encode([
            'success' => true,
            'data' => [
                'notifications' => [],
                'unread_count' => 0,
                'total' => 0,
                'page' => 1,
                'per_page' => $perPage
            ]
        ]);
        exit;
    }
    
    // Build WHERE clause
    $whereConditions = ['n.user_id = ?'];
    $params = [$userId];
    
    if ($unreadOnly) {
        $whereConditions[] = 'n.is_read = 0';
    }
    
    if ($notificationType && in_array($notificationType, ['new_article', 'timeline_event', 'document_added', 'verdict', 'case_update', 'case_closed'])) {
        $whereConditions[] = 'n.notification_type = ?';
        $params[] = $notificationType;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Get total count
    $countResult = $db->fetchOne("SELECT COUNT(*) as total FROM notifications n WHERE $whereClause", $params);
    $totalCount = $countResult['total'];
    
    // Get notifications with case details
    $query = "
        SELECT 
            n.*,
            ct.title as case_title,
            ct.slug as case_slug,
            ct.thumbnail as case_thumbnail,
            ct.status as case_status
        FROM notifications n
        LEFT JOIN case_threads ct ON n.case_id = ct.id
        WHERE $whereClause
        ORDER BY n.created_at DESC
        LIMIT $perPage OFFSET $offset
    ";
    
    $notifications = $db->fetchAll($query, $params);
    
    // Get unread count
    $unreadResult = $db->fetchOne("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0", [$userId]);
    $unreadCount = $unreadResult['count'];
    
    // Format timestamps
    foreach ($notifications as &$notification) {
        $notification['id'] = (int)$notification['id'];
        $notification['user_id'] = (int)$notification['user_id'];
        $notification['case_id'] = $notification['case_id'] ? (int)$notification['case_id'] : null;
        $notification['entity_id'] = $notification['entity_id'] ? (int)$notification['entity_id'] : null;
        $notification['is_read'] = (bool)$notification['is_read'];
        $notification['is_sent'] = (bool)$notification['is_sent'];
        $notification['created_at_formatted'] = date('M j, Y g:i A', strtotime($notification['created_at']));
        $notification['time_ago'] = getTimeAgo($notification['created_at']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'notifications' => $notifications,
            'unread_count' => (int)$unreadCount,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => (int)$totalCount,
                'total_pages' => ceil($totalCount / $perPage)
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error in notifications list: " . $e->getMessage());
    echo json_encode([
        'success' => true,
        'data' => [
            'notifications' => [],
            'unread_count' => 0,
            'pagination' => [
                'current_page' => 1,
                'per_page' => 20,
                'total_items' => 0,
                'total_pages' => 0
            ]
        ]
    ]);
}

function getTimeAgo($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}
