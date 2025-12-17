<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/config.php';
require_once '../../includes/Database.php';

$db = Database::getInstance()->getConnection();

try {
    $query = "SELECT id, name, created_at FROM tags ORDER BY name ASC";
    $result = $db->query($query);
    
    $tags = [];
    while ($tag = $result->fetch_assoc()) {
        $tags[] = $tag;
    }
    
    echo json_encode(['success' => true, 'tags' => $tags]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
