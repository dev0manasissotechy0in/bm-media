<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();

$query = "SELECT id, title, image_url FROM articles WHERE image_url LIKE '%1764843982%' LIMIT 1";
$result = $db->query($query);
$row = $result->fetch_assoc();

echo json_encode([
    'db_value' => $row['image_url'],
    'expected_url' => BASE_URL . '/uploads/articles/' . basename($row['image_url']),
    'current_api_returns' => BASE_URL . '/' . ltrim($row['image_url'], '/'),
    'base_url' => BASE_URL
], JSON_PRETTY_PRINT);
?>
