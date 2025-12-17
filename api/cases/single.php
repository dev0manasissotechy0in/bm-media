<?php
/**
 * CASE THREADS - GET SINGLE CASE API ENDPOINT
 * Complete implementation example
 * File: api/cases/single.php
 */

require_once __DIR__ . '/../../src/Config/Database.php';
require_once __DIR__ . '/../../src/Helpers/Response.php';
require_once __DIR__ . '/../../src/Helpers/Auth.php';

use CaseThreads\Config\Database;
use CaseThreads\Helpers\Response;
use CaseThreads\Helpers\Auth;

try {
    // Get case ID or slug from URL
    $caseId = $_GET['id'] ?? null;
    $slug = $_GET['slug'] ?? null;
    
    if (!$caseId && !$slug) {
        Response::error('Case ID or slug is required', 'MISSING_IDENTIFIER', 400);
    }
    
    // Get current user (optional for public access)
    $userId = Auth::check();
    
    $db = Database::getInstance();
    
    // ============================================
    // 1. FETCH MAIN CASE DATA
    // ============================================
    // Support both ID and slug lookup
    if ($slug) {
        $case = $db->fetchOne("
            SELECT 
                id, title, slug, short_description, full_description,
                status, category, primary_location,
                start_date, end_date,
                thumbnail, cover_image,
                total_articles, total_followers, total_views,
                meta_title, meta_description,
                created_at, updated_at, last_activity_at
            FROM case_threads
            WHERE slug = ?
        ", [$slug]);
    } else {
        $case = $db->fetchOne("
            SELECT 
                id, title, slug, short_description, full_description,
                status, category, primary_location,
                start_date, end_date,
                thumbnail, cover_image,
                total_articles, total_followers, total_views,
                meta_title, meta_description,
                created_at, updated_at, last_activity_at
            FROM case_threads
            WHERE id = ?
        ", [$caseId]);
    }
    
    if (!$case) {
        Response::notFound('Case thread not found');
    }
    
    // Use the fetched case ID for subsequent queries
    $caseId = $case['id'];
    
    // Check if user is following this case
    $isFollowing = false;
    if ($userId) {
        $follow = $db->fetchOne("
            SELECT id FROM case_follows 
            WHERE user_id = ? AND case_id = ?
        ", [$userId, $caseId]);
        $isFollowing = (bool)$follow;
    }
    
    $case['is_following'] = $isFollowing;
    
    // ============================================
    // 2. FETCH TIMELINE EVENTS
    // ============================================
    $timelineEvents = $db->fetchAll("
        SELECT 
            id, event_title, event_description,
            event_date, event_time, event_type,
            is_major_event, linked_article_ids, linked_document_ids,
            created_at
        FROM case_timeline_events
        WHERE case_id = ?
        ORDER BY event_date DESC, event_time DESC
        LIMIT 20
    ", [$caseId]);
    
    // Parse JSON arrays and count linked items
    foreach ($timelineEvents as &$event) {
        $linkedArticles = json_decode($event['linked_article_ids'] ?: '[]', true);
        $event['linked_articles_count'] = count($linkedArticles);
        unset($event['linked_article_ids'], $event['linked_document_ids']);
    }
    
    $timeline = [
        'total' => count($timelineEvents),
        'events' => $timelineEvents
    ];
    
    // ============================================
    // 3. FETCH RECENT ARTICLES
    // ============================================
    $articles = $db->fetchAll("
        SELECT 
            a.id, a.title, a.slug, a.description, a.thumbnail,
            a.published_at, a.source,
            cam.relevance_score, cam.is_key_article,
            au.id as author_id, au.full_name as author_name, 
            au.profile_photo as author_avatar
        FROM case_article_map cam
        JOIN articles a ON cam.article_id = a.id
        LEFT JOIN authors au ON a.author_id = au.id
        WHERE cam.case_id = ?
        ORDER BY cam.is_key_article DESC, a.published_at DESC
        LIMIT 10
    ", [$caseId]);
    
    // Format articles with author object
    foreach ($articles as &$article) {
        $article['author'] = [
            'name' => $article['author_name'] ?: 'Unknown',
            'avatar' => $article['author_avatar']
        ];
        unset($article['author_id'], $article['author_name'], $article['author_avatar']);
    }
    
    // Get total article count
    $totalArticlesRow = $db->fetchOne("
        SELECT COUNT(*) as total 
        FROM case_article_map 
        WHERE case_id = ?
    ", [$caseId]);
    
    $recentArticles = [
        'total' => (int)$totalArticlesRow['total'],
        'showing' => count($articles),
        'articles' => $articles
    ];
    
    // ============================================
    // 4. FETCH DOCUMENTS
    // ============================================
    $documents = $db->fetchAll("
        SELECT 
            id, title, document_type, description,
            plain_language_summary, file_url, file_type, file_size,
            document_date, source, official_reference_number
        FROM case_documents
        WHERE case_id = ?
        ORDER BY document_date DESC
        LIMIT 10
    ", [$caseId]);
    
    $totalDocsRow = $db->fetchOne("
        SELECT COUNT(*) as total 
        FROM case_documents 
        WHERE case_id = ?
    ", [$caseId]);
    
    $documentsData = [
        'total' => (int)$totalDocsRow['total'],
        'documents' => $documents
    ];
    
    // ============================================
    // 5. FETCH MEDIA
    // ============================================
    $media = $db->fetchAll("
        SELECT 
            id, media_type, title, caption,
            file_url, thumbnail_url, duration,
            media_date, source, is_featured
        FROM case_media
        WHERE case_id = ?
        ORDER BY is_featured DESC, media_date DESC
        LIMIT 20
    ", [$caseId]);
    
    // Count media types
    $mediaStats = $db->fetchOne("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN media_type = 'photo' THEN 1 ELSE 0 END) as photos,
            SUM(CASE WHEN media_type = 'video' THEN 1 ELSE 0 END) as videos,
            SUM(CASE WHEN media_type = 'audio' THEN 1 ELSE 0 END) as audio
        FROM case_media
        WHERE case_id = ?
    ", [$caseId]);
    
    $mediaData = [
        'total' => (int)$mediaStats['total'],
        'photos' => (int)$mediaStats['photos'],
        'videos' => (int)$mediaStats['videos'],
        'audio' => (int)$mediaStats['audio'],
        'items' => $media
    ];
    
    // ============================================
    // 6. FETCH REVIEWS
    // ============================================
    $reviews = $db->fetchAll("
        SELECT 
            id, title, review_type, summary,
            author_name, author_designation, organization,
            review_date, external_url, is_verified
        FROM case_reviews
        WHERE case_id = ?
        ORDER BY is_verified DESC, review_date DESC
        LIMIT 10
    ", [$caseId]);
    
    $totalReviewsRow = $db->fetchOne("
        SELECT COUNT(*) as total 
        FROM case_reviews 
        WHERE case_id = ?
    ", [$caseId]);
    
    $reviewsData = [
        'total' => (int)$totalReviewsRow['total'],
        'reviews' => $reviews
    ];
    
    // ============================================
    // 7. INCREMENT VIEW COUNT
    // ============================================
    $db->update("
        UPDATE case_threads 
        SET total_views = total_views + 1 
        WHERE id = ?
    ", [$caseId]);
    
    // ============================================
    // 8. BUILD FINAL RESPONSE
    // ============================================
    Response::success([
        'case' => $case,
        'timeline' => $timeline,
        'recent_articles' => $recentArticles,
        'documents' => $documentsData,
        'media' => $mediaData,
        'reviews' => $reviewsData
    ]);
    
} catch (Exception $e) {
    error_log("Error in single case API: " . $e->getMessage());
    Response::error('An error occurred', 'SERVER_ERROR', 500);
}
