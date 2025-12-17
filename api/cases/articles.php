<?php
/**
 * CASE THREADS - ARTICLES API ENDPOINT
 * Get paginated articles for a specific case
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$db = Database::getInstance();

try {
    // Get query parameters
    $caseId = isset($_GET['case_id']) ? (int)$_GET['case_id'] : 0;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;
    $offset = ($page - 1) * $limit;
    
    if ($caseId <= 0) {
        echo json_encode([
            'success' => false,
            'error' => 'INVALID_CASE_ID',
            'message' => 'Valid case ID is required'
        ]);
        exit;
    }
    
    // Check if case exists
    $caseCheck = $db->fetchOne("SELECT id FROM case_threads WHERE id = ?", [$caseId]);
    if (!$caseCheck) {
        echo json_encode([
            'success' => false,
            'error' => 'CASE_NOT_FOUND',
            'message' => 'Case not found'
        ]);
        exit;
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM case_article_map WHERE case_id = ?";
    $totalResult = $db->fetchOne($countQuery, [$caseId]);
    $totalRows = $totalResult['total'] ?? 0;
    
    // Get articles
    $query = "
        SELECT 
            a.id, a.title, a.slug, a.description, a.thumbnail,
            a.published_at, a.source, a.views_count, a.comments_count,
            cam.relevance_score, cam.is_key_article,
            au.id as author_id, au.full_name as author_name, 
            au.profile_photo as author_avatar,
            c.id as category_id, c.name as category_name
        FROM case_article_map cam
        JOIN articles a ON cam.article_id = a.id
        LEFT JOIN authors au ON a.author_id = au.id
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE cam.case_id = ? AND a.status = 'published'
        ORDER BY cam.is_key_article DESC, a.published_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $articles = $db->fetchAll($query, [$caseId, $limit, $offset]);
    
    // Format articles
    foreach ($articles as &$article) {
        $article['id'] = (int)$article['id'];
        $article['views_count'] = (int)$article['views_count'];
        $article['comments_count'] = (int)$article['comments_count'];
        $article['relevance_score'] = (int)$article['relevance_score'];
        $article['is_key_article'] = (bool)$article['is_key_article'];
        
        $article['author'] = [
            'id' => (int)$article['author_id'],
            'name' => $article['author_name'] ?: 'Unknown',
            'avatar' => $article['author_avatar']
        ];
        
        $article['category'] = [
            'id' => (int)$article['category_id'],
            'name' => $article['category_name']
        ];
        
        unset($article['author_id'], $article['author_name'], $article['author_avatar']);
        unset($article['category_id'], $article['category_name']);
    }
    
    // Build pagination
    $totalPages = ceil($totalRows / $limit);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'articles' => $articles,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => (int)$totalRows,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error in case articles: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'SERVER_ERROR',
        'message' => 'An error occurred'
    ]);
}
