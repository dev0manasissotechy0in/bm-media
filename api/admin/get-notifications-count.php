<?php
/**
 * Get Admin Notifications Count
 * Returns count of pending items requiring attention
 */

require_once '../../config/config.php';
require_once '../../includes/Database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    // Count pending article approvals
    try {
        $pendingArticles = $db->fetchOne(
            "SELECT COUNT(*) as count FROM articles WHERE approval_status = 'pending'"
        )['count'] ?? 0;
    } catch (Exception $e) {
        $pendingArticles = 0;
    }
    
    // Count unread contact queries
    try {
        $unreadContacts = $db->fetchOne(
            "SELECT COUNT(*) as count FROM contact_queries WHERE is_read = 0"
        )['count'] ?? 0;
    } catch (Exception $e) {
        $unreadContacts = 0;
    }
    
    // Count new mobile stories (last 24 hours)
    try {
        $newStories = $db->fetchOne(
            "SELECT COUNT(*) as count FROM mobile_stories 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        )['count'] ?? 0;
    } catch (Exception $e) {
        $newStories = 0;
    }
    
    // Count live articles
    try {
        $liveArticles = $db->fetchOne(
            "SELECT COUNT(*) as count FROM articles WHERE is_live = 1 AND status = 'published'"
        )['count'] ?? 0;
    } catch (Exception $e) {
        $liveArticles = 0;
    }
    
    // Count new case studies (last 24 hours)
    try {
        $newCases = $db->fetchOne(
            "SELECT COUNT(*) as count FROM cases 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        )['count'] ?? 0;
    } catch (Exception $e) {
        $newCases = 0;
    }
    
    $totalNotifications = $pendingArticles + $unreadContacts + $newStories + $liveArticles + $newCases;
    
    echo json_encode([
        'success' => true,
        'total' => (int)$totalNotifications,
        'pendingArticles' => (int)$pendingArticles,
        'unreadContacts' => (int)$unreadContacts,
        'newStories' => (int)$newStories,
        'liveArticles' => (int)$liveArticles,
        'newCases' => (int)$newCases
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'total' => 0,
        'pendingArticles' => 0,
        'unreadContacts' => 0,
        'newStories' => 0,
        'liveArticles' => 0,
        'newCases' => 0,
        'error' => $e->getMessage()
    ]);
}
