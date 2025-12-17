<?php
require_once 'config/config.php';
$db = Database::getInstance();

// Check reel with id 128
$reel = $db->fetchOne("SELECT * FROM reels WHERE id = 128");

echo "<h2>Reel ID 128 Data:</h2>";
echo "<pre>";
print_r($reel);
echo "</pre>";

// Also check articles with content_type reel
$article = $db->fetchOne("SELECT * FROM articles WHERE id = 128");
echo "<h2>Article ID 128 Data:</h2>";
echo "<pre>";
print_r($article);
echo "</pre>";
?>
