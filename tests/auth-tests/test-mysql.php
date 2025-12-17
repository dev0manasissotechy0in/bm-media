<?php
/**
 * Direct MySQL Connection Test
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$result = ['timestamp' => date('Y-m-d H:i:s')];

// Step 1: Test direct MySQL connection
$result['step1_direct_connection'] = 'testing';
try {
    $conn = new mysqli('localhost', 'root', '', 'news_website');
    
    if ($conn->connect_error) {
        $result['step1_direct_connection'] = 'FAILED';
        $result['error'] = $conn->connect_error;
    } else {
        $result['step1_direct_connection'] = 'SUCCESS';
        $result['mysql_version'] = $conn->server_info;
        
        // Check if database exists
        $db_check = $conn->query("SELECT DATABASE()");
        $db_name = $db_check->fetch_row()[0];
        $result['current_database'] = $db_name;
        
        // Check if otp_codes table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'otp_codes'");
        $result['otp_table_exists'] = $table_check->num_rows > 0;
        
        if ($result['otp_table_exists']) {
            // Get table structure
            $structure = $conn->query("DESCRIBE otp_codes");
            $columns = [];
            while ($row = $structure->fetch_assoc()) {
                $columns[] = $row['Field'];
            }
            $result['otp_table_columns'] = $columns;
            
            // Count OTPs
            $count = $conn->query("SELECT COUNT(*) as cnt FROM otp_codes");
            $result['total_otps'] = $count->fetch_assoc()['cnt'];
        } else {
            $result['otp_table_error'] = 'Table does not exist - run fix_auth_system.sql';
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    $result['step1_direct_connection'] = 'ERROR';
    $result['error'] = $e->getMessage();
}

// Step 2: Test PDO connection (what Database class uses)
$result['step2_pdo_connection'] = 'testing';
try {
    $dsn = "mysql:host=localhost;dbname=news_website;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5 // 5 second timeout
    ]);
    
    $result['step2_pdo_connection'] = 'SUCCESS';
    $result['pdo_version'] = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    
} catch (PDOException $e) {
    $result['step2_pdo_connection'] = 'FAILED';
    $result['pdo_error'] = $e->getMessage();
}

// Step 3: Test Database class
$result['step3_database_class'] = 'testing';
try {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/includes/Database.php';
    
    $db = Database::getInstance();
    $result['step3_database_class'] = 'SUCCESS';
    
    // Try a simple query with timeout
    $test = $db->fetchOne("SELECT 1 as test");
    $result['test_query'] = $test ? 'SUCCESS' : 'FAILED';
    
} catch (Exception $e) {
    $result['step3_database_class'] = 'FAILED';
    $result['database_class_error'] = $e->getMessage();
}

$result['recommendation'] = $result['step1_direct_connection'] === 'SUCCESS' 
    ? '✅ MySQL is working fine' 
    : '❌ MySQL connection failed - check credentials';

echo json_encode($result, JSON_PRETTY_PRINT);
