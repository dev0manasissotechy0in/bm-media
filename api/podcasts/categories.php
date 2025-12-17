<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT * FROM podcast_categories 
        WHERE is_active = 1 
        ORDER BY display_order ASC
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
