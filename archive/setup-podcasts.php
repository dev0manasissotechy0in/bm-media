<?php
require_once 'config/config.php';

echo "Setting up Podcast Tables...\n\n";

try {
    // Get PDO connection directly
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/database/podcasts_schema.sql');
    
    if ($sql === false) {
        die("Error: Could not read podcasts_schema.sql file\n");
    }
    
    // Remove comments
    $sql = preg_replace('/--.*$/m', '', $sql);
    
    // Split by semicolon to execute each statement separately
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt);
        }
    );
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            $pdo->exec($statement);
            
            // Extract table name for feedback
            if (preg_match('/CREATE TABLE.*?`(\w+)`/i', $statement, $matches)) {
                echo "✓ Created table: {$matches[1]}\n";
            }
        } catch (PDOException $e) {
            echo "✗ Error executing statement: " . $e->getMessage() . "\n";
            echo "Statement: " . substr($statement, 0, 100) . "...\n\n";
        }
    }
    
    echo "\n✓ Podcast setup completed successfully!\n";
    echo "\nTables created:\n";
    echo "- podcasts\n";
    echo "- podcast_episodes\n";
    echo "- user_podcast_likes\n";
    echo "- user_saved_podcasts\n";
    echo "- user_podcast_progress\n";
    echo "- user_podcast_notifications\n";
    
} catch (Exception $e) {
    echo "✗ Fatal Error: " . $e->getMessage() . "\n";
    exit(1);
}
