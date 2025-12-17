<?php
/**
 * CASE THREADS - LIST API ENDPOINT
 * Get all cases with filters and pagination
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
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;
    $offset = ($page - 1) * $limit;
    
    $category = isset($_GET['category']) ? trim($_GET['category']) : null;
    $status = isset($_GET['status']) ? trim($_GET['status']) : null;
    $search = isset($_GET['search']) ? trim($_GET['search']) : null;
    $sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'latest';
    
    // Build WHERE clause
    $where = ['1=1'];
    $params = [];
    
    if ($category) {
        $where[] = 'category = ?';
        $params[] = $category;
    }
    
    if ($status) {
        $where[] = 'status = ?';
        $params[] = $status;
    }
    
    if ($search) {
        $where[] = '(title LIKE ? OR short_description LIKE ? OR full_description LIKE ?)';
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Determine ORDER BY clause
    $orderBy = match($sort) {
        'popular' => 'total_followers DESC, total_views DESC',
        'articles' => 'total_articles DESC',
        'oldest' => 'start_date ASC',
        default => 'last_activity_at DESC, created_at DESC'
    };
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM case_threads WHERE $whereClause";
    $totalResult = $db->fetchOne($countQuery, $params);
    $totalRows = $totalResult['total'] ?? 0;
    
    // Get cases
    $query = "
        SELECT 
            id, title, slug, short_description,
            status, category, primary_location,
            start_date, end_date,
            thumbnail, cover_image,
            total_articles, total_followers, total_views,
            created_at, updated_at, last_activity_at
        FROM case_threads
        WHERE $whereClause
        ORDER BY $orderBy
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $cases = $db->fetchAll($query, $params);
    
    // Format cases
    foreach ($cases as &$case) {
        $case['id'] = (int)$case['id'];
        $case['total_articles'] = (int)$case['total_articles'];
        $case['total_followers'] = (int)$case['total_followers'];
        $case['total_views'] = (int)$case['total_views'];
        $case['is_following'] = false; // TODO: Check if user is following
    }
    
    // Build pagination
    $totalPages = ceil($totalRows / $limit);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'cases' => $cases,
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
    
} catch (PDOException $e) {
    error_log("Database error in cases list: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => 'Failed to load cases'
    ]);
} catch (Exception $e) {
    error_log("Error in cases list: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'message' => 'An error occurred'
    ]);
}
