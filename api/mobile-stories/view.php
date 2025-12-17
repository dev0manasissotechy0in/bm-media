<?php
/**
 * Track mobile story view
 */

require_once '../../config/config.php';
require_once '../../includes/Database.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id > 0) {
    trackView('mobile_story', $id);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
