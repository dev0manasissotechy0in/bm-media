<?php
// Quick test to check user_sessions table
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    // Check if table exists
    $tableCheck = $db->fetchOne("SHOW TABLES LIKE 'user_sessions'");
    
    if (!$tableCheck) {
        echo json_encode([
            'table_exists' => false,
            'message' => 'user_sessions table does not exist'
        ]);
        exit;
    }
    
    // Get table structure
    $columns = $db->fetchAll("DESCRIBE user_sessions");
    
    echo json_encode([
        'table_exists' => true,
        'columns' => $columns,
        'message' => 'Table structure retrieved successfully'
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
