<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

// Fix article 133 - reel video
$db->query("UPDATE articles SET thumbnail = 'reel_1764843982_693161ce87994.mp4' WHERE id = 133");
echo "Fixed article 133 thumbnail\n";

// Check if there's a file for article 128
$files = glob(__DIR__ . '/uploads/articles/*1764850032*');
if (!empty($files)) {
    $filename = basename($files[0]);
    $db->query("UPDATE articles SET thumbnail = ? WHERE id = 128", [$filename]);
    echo "Fixed article 128 thumbnail: {$filename}\n";
} else {
    echo "No file found for article 128\n";
}

echo "Done!\n";
?>
