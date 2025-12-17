<?php
/**
 * Complete Auth & Database Debug Tool
 * Access: http://your-domain/api/debug-auth.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'tests' => []
];

// Test 1: Config file
try {
    require_once __DIR__ . '/../config/config.php';
    $results['tests']['config'] = [
        'status' => '✅ SUCCESS',
        'message' => 'Config file loaded',
        'details' => [
            'DB_HOST' => DB_HOST,
            'DB_NAME' => DB_NAME,
            'DB_USER' => DB_USER,
            'BASE_URL' => BASE_URL,
            'API_URL' => API_URL
        ]
    ];
} catch (Exception $e) {
    $results['tests']['config'] = [
        'status' => '❌ ERROR',
        'message' => $e->getMessage()
    ];
    echo json_encode($results, JSON_PRETTY_PRINT);
    exit;
}

// Test 2: Database class
try {
    require_once __DIR__ . '/../includes/Database.php';
    $results['tests']['database_class'] = [
        'status' => '✅ SUCCESS',
        'message' => 'Database class loaded'
    ];
} catch (Exception $e) {
    $results['tests']['database_class'] = [
        'status' => '❌ ERROR',
        'message' => $e->getMessage()
    ];
    echo json_encode($results, JSON_PRETTY_PRINT);
    exit;
}

// Test 3: Database connection
try {
    $db = Database::getInstance();
    $results['tests']['db_connection'] = [
        'status' => '✅ SUCCESS',
        'message' => 'Database connected successfully'
    ];
} catch (Exception $e) {
    $results['tests']['db_connection'] = [
        'status' => '❌ ERROR',
        'message' => $e->getMessage(),
        'hint' => 'Check DB credentials in config.php. Make sure MySQL is running.'
    ];
    echo json_encode($results, JSON_PRETTY_PRINT);
    exit;
}

// Test 4: Check users table
try {
    $userCount = $db->fetchOne("SELECT COUNT(*) as count FROM users");
    $results['tests']['users_table'] = [
        'status' => '✅ SUCCESS',
        'message' => 'Users table exists and accessible',
        'total_users' => (int)$userCount['count']
    ];
} catch (Exception $e) {
    $results['tests']['users_table'] = [
        'status' => '❌ ERROR',
        'message' => $e->getMessage(),
        'hint' => 'Users table might not exist. Run the database migration.'
    ];
}

// Test 5: Check otp_codes table
try {
    $otpCount = $db->fetchOne("SELECT COUNT(*) as count FROM otp_codes");
    
    // Check for required columns
    $columns = $db->fetchAll("DESCRIBE otp_codes");
    $columnNames = array_column($columns, 'Field');
    
    $requiredColumns = ['user_type', 'verified'];
    $missingColumns = array_diff($requiredColumns, $columnNames);
    
    if (empty($missingColumns)) {
        $results['tests']['otp_codes_table'] = [
            'status' => '✅ SUCCESS',
            'message' => 'OTP table exists with all required columns',
            'total_otps' => (int)$otpCount['count'],
            'columns' => $columnNames
        ];
    } else {
        $results['tests']['otp_codes_table'] = [
            'status' => '⚠️ WARNING',
            'message' => 'OTP table exists but missing columns',
            'missing_columns' => $missingColumns,
            'hint' => 'Run: ALTER TABLE otp_codes ADD COLUMN user_type ENUM(\'user\',\'author\',\'admin\') DEFAULT \'user\', ADD COLUMN verified BOOLEAN DEFAULT 0;'
        ];
    }
} catch (Exception $e) {
    $results['tests']['otp_codes_table'] = [
        'status' => '❌ ERROR',
        'message' => $e->getMessage(),
        'hint' => 'Run database/migration_advanced_features.sql to create OTP table'
    ];
}

// Test 6: Check user_sessions table
try {
    $sessionCount = $db->fetchOne("SELECT COUNT(*) as count FROM user_sessions");
    $results['tests']['user_sessions_table'] = [
        'status' => '✅ SUCCESS',
        'message' => 'User sessions table exists',
        'total_sessions' => (int)$sessionCount['count']
    ];
} catch (Exception $e) {
    $results['tests']['user_sessions_table'] = [
        'status' => '❌ ERROR',
        'message' => $e->getMessage(),
        'hint' => 'Run database/user_sessions_table.sql to create sessions table'
    ];
}

// Test 7: Check recent OTPs
try {
    $recentOtps = $db->fetchAll(
        "SELECT id, email, purpose, user_type, expires_at, is_used, verified, 
                CASE WHEN expires_at > NOW() THEN 'Valid' ELSE 'Expired' END as status,
                created_at 
         FROM otp_codes 
         ORDER BY created_at DESC 
         LIMIT 5"
    );
    
    // Mask email for privacy
    foreach ($recentOtps as &$otp) {
        $email = $otp['email'];
        $parts = explode('@', $email);
        $otp['email'] = substr($parts[0], 0, 3) . '***@' . ($parts[1] ?? '');
    }
    
    $results['tests']['recent_otps'] = [
        'status' => '✅ SUCCESS',
        'message' => 'Recent OTPs retrieved',
        'count' => count($recentOtps),
        'data' => $recentOtps
    ];
} catch (Exception $e) {
    $results['tests']['recent_otps'] = [
        'status' => '❌ ERROR',
        'message' => $e->getMessage()
    ];
}

// Test 8: Sample user data
try {
    $sampleUsers = $db->fetchAll(
        "SELECT id, email, full_name, status, email_verified, phone_verified, 
                auth_provider, created_at, last_login
         FROM users 
         ORDER BY id DESC
         LIMIT 5"
    );
    
    // Mask sensitive data
    foreach ($sampleUsers as &$user) {
        $email = $user['email'];
        $parts = explode('@', $email);
        $user['email'] = substr($parts[0], 0, 3) . '***@' . ($parts[1] ?? '');
        $user['email_verified'] = (bool)$user['email_verified'];
        $user['phone_verified'] = (bool)$user['phone_verified'];
    }
    
    $results['tests']['sample_users'] = [
        'status' => '✅ SUCCESS',
        'message' => 'Sample users retrieved',
        'count' => count($sampleUsers),
        'data' => $sampleUsers
    ];
} catch (Exception $e) {
    $results['tests']['sample_users'] = [
        'status' => '❌ ERROR',
        'message' => $e->getMessage()
    ];
}

// Test 9: Auth API endpoints
$authEndpoints = [
    'send-otp' => '/api/auth/send-otp.php',
    'verify-otp' => '/api/auth/verify-otp.php',
    'login' => '/api/auth/login.php',
    'forgot-password' => '/api/auth/forgot-password.php',
    'reset-password' => '/api/auth/reset-password.php',
];

$endpointStatuses = [];
foreach ($authEndpoints as $name => $path) {
    $fullPath = dirname(__DIR__) . $path;
    $exists = file_exists($fullPath);
    $endpointStatuses[$name] = [
        'exists' => $exists,
        'path' => $path,
        'status' => $exists ? '✅' : '❌'
    ];
}

$results['tests']['auth_endpoints'] = [
    'status' => '✅ INFO',
    'message' => 'Auth API endpoints check',
    'endpoints' => $endpointStatuses
];

// Test 10: EmailHelper check
try {
    require_once __DIR__ . '/../includes/EmailHelper.php';
    $results['tests']['email_helper'] = [
        'status' => '✅ SUCCESS',
        'message' => 'EmailHelper class loaded',
        'note' => 'SMTP configuration required for sending emails'
    ];
} catch (Exception $e) {
    $results['tests']['email_helper'] = [
        'status' => '❌ ERROR',
        'message' => $e->getMessage()
    ];
}

// Overall status
$errorCount = 0;
$warningCount = 0;
$successCount = 0;

foreach ($results['tests'] as $test) {
    if (strpos($test['status'], '❌') !== false) {
        $errorCount++;
    } elseif (strpos($test['status'], '⚠️') !== false) {
        $warningCount++;
    } elseif (strpos($test['status'], '✅') !== false) {
        $successCount++;
    }
}

$results['overall_status'] = $errorCount === 0 ? '✅ ALL SYSTEMS OPERATIONAL' : '❌ ISSUES DETECTED';
$results['summary'] = [
    'total_tests' => count($results['tests']),
    'passed' => $successCount,
    'warnings' => $warningCount,
    'failed' => $errorCount
];

// Recommendations
$recommendations = [];
if ($errorCount > 0) {
    $recommendations[] = '1. Fix all ERROR items above before testing the app';
    $recommendations[] = '2. Make sure MySQL server is running';
    $recommendations[] = '3. Run all database migration scripts';
    $recommendations[] = '4. Check config.php for correct database credentials';
}
if ($warningCount > 0) {
    $recommendations[] = '5. Address WARNING items to ensure full functionality';
}

$results['recommendations'] = $recommendations;

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
