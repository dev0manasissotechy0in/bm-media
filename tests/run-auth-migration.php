<?php
/**
 * Database Migration: Create user_sessions table
 * Run this file once to set up the unified authentication system
 */

require_once 'config/config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>Running Database Migration...</h2>";
    
    // Create user_sessions table
    $sql = "
    CREATE TABLE IF NOT EXISTS `user_sessions` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT NOT NULL,
      `token` VARCHAR(64) NOT NULL UNIQUE,
      `device_info` VARCHAR(255),
      `ip_address` VARCHAR(45),
      `expires_at` DATETIME NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      INDEX `idx_token` (`token`),
      INDEX `idx_user_id` (`user_id`),
      INDEX `idx_expires` (`expires_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $db->exec($sql);
    echo "<p style='color: green;'>✅ Successfully created user_sessions table!</p>";
    
    // Check if table was created
    $result = $db->query("SHOW TABLES LIKE 'user_sessions'");
    if ($result->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Table verified in database!</p>";
        
        // Show table structure
        $columns = $db->query("DESCRIBE user_sessions")->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "<td>{$col['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<h3>✅ Migration Complete!</h3>";
    echo "<p>The unified authentication system is now ready to use.</p>";
    echo "<p><a href='test-auth-api.html'>Test the Authentication APIs</a></p>";
    echo "<p><a href='UNIFIED_AUTH_GUIDE.md'>Read the Documentation</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "<p style='color: orange;'>⚠️ Table already exists. No changes made.</p>";
        echo "<p><a href='test-auth-api.html'>Proceed to test the APIs</a></p>";
    }
}
?>
