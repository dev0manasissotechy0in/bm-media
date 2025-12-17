<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

echo "=== PODCASTS TABLE STRUCTURE ===\n";
$cols = $db->fetchAll('DESCRIBE podcasts');
foreach($cols as $c) {
    echo $c['Field'] . ' (' . $c['Type'] . ")\n";
}

echo "\n=== PODCAST DATA ===\n";
$podcast = $db->fetchOne("SELECT * FROM podcasts WHERE id = 1");
print_r($podcast);
?>
