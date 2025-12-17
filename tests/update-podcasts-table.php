<?php
require_once 'config/config.php';

echo "Adding audio_url and duration columns to podcasts table...\n\n";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Add audio_url column
    $pdo->exec("ALTER TABLE podcasts ADD COLUMN audio_url VARCHAR(500) NULL AFTER cover_image");
    echo "✓ Added audio_url column\n";
    
    // Add duration column
    $pdo->exec("ALTER TABLE podcasts ADD COLUMN duration INT NULL COMMENT 'Duration in seconds for single podcasts' AFTER audio_url");
    echo "✓ Added duration column\n";
    
    echo "\n✓ Podcasts table updated successfully!\n";
    echo "Single podcasts can now have direct audio files.\n";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "✓ Columns already exist - no changes needed\n";
    } else {
        echo "✗ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
