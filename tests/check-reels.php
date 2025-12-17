<?php
require 'config/config.php';
require 'includes/Database.php';

$db = Database::getInstance();
$reels = $db->fetchAll("SELECT id, title, slug, content_type FROM articles WHERE content_type = 'reel' AND status = 'published' ORDER BY id");

echo "Total Reels: " . count($reels) . "\n\n";
foreach($reels as $r) {
    echo "ID: " . $r['id'] . " - Title: " . $r['title'] . " - Slug: " . $r['slug'] . "\n";
}
