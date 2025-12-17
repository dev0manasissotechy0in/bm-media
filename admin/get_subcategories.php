<?php
/**
 * Get Subcategories by Parent ID
 * AJAX endpoint
 */

require_once 'auth_check.php';

header('Content-Type: application/json');

$parent_id = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : 0;

if (!$parent_id) {
    echo json_encode(['subcategories' => []]);
    exit;
}

$db = Database::getInstance();

$subcategories = $db->fetchAll(
    "SELECT id, name, slug FROM categories WHERE parent_id = ? AND status = 'active' ORDER BY order_id ASC, name ASC",
    [$parent_id]
);

echo json_encode(['subcategories' => $subcategories]);
