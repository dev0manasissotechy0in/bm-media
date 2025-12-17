<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

header('Content-Type: application/json');

$settings = $db->fetchOne("SELECT post_details_layout FROM app_settings LIMIT 1");

echo json_encode([
    'current_layout' => $settings['post_details_layout'] ?? 'not_set',
    'timestamp' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT);
