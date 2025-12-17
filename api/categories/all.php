<?php
// Disable HTML error display for API responses
ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../helpers.php';

$db = Database::getInstance()->getConnection();

try {
    $query = "SELECT id, name, parent_id, icon, created_at FROM categories WHERE status = 'active' ORDER BY order_id ASC, name ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $categories = [];
    while ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $categories[] = [
            'id' => $category['id'],
            'name' => $category['name'],
            'parent_id' => $category['parent_id'],
            'image_url' => getImageUrl($category['icon'], 'categories'),
            'created_at' => $category['created_at']
        ];
    }
    
    echo json_encode(['success' => true, 'categories' => $categories]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
