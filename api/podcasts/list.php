<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$featured = isset($_GET['featured']) ? (bool)$_GET['featured'] : false;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $where = ['p.is_active = 1'];
    $params = [];

    if ($category_id > 0) {
        $where[] = 'p.category_id = ?';
        $params[] = $category_id;
    }

    if ($featured) {
        $where[] = 'p.is_featured = 1';
    }

    if ($search !== '') {
        $where[] = '(p.title LIKE ? OR p.description LIKE ? OR p.host LIKE ? OR p.tags LIKE ?)';
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $whereClause = implode(' AND ', $where);

    // Get total count
    $countQuery = "SELECT COUNT(*) FROM podcasts p WHERE {$whereClause}";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();

    // Get podcasts
    $query = "
        SELECT p.*, pc.name as category_name, pc.color as category_color
        FROM podcasts p
        LEFT JOIN podcast_categories pc ON p.category_id = pc.id
        WHERE {$whereClause}
        ORDER BY p.published_at DESC, p.created_at DESC
        LIMIT ? OFFSET ?
    ";
    $params[] = $limit;
    $params[] = $offset;
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $podcasts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'podcasts' => $podcasts
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
