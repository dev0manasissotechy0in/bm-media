<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

echo "=== ARTICLES TABLE MEDIA COLUMNS ===\n";
$cols = $db->fetchAll("DESCRIBE articles");
foreach ($cols as $col) {
    if (strpos($col['Field'], 'media') !== false || strpos($col['Field'], 'video') !== false || strpos($col['Field'], 'reel') !== false || strpos($col['Field'], 'audio') !== false) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
}
?>
