# CASE THREADS - PHP BACKEND ARCHITECTURE

## Folder Structure

```
case_threads/
├── public/              # Web-accessible folder (document root)
│   ├── index.php        # Front controller
│   ├── .htaccess        # URL rewriting
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   └── uploads/
│       ├── cases/
│       ├── documents/
│       └── media/
│
├── api/                 # API endpoints
│   ├── index.php        # API router
│   ├── cases/
│   │   ├── list.php
│   │   ├── single.php
│   │   ├── articles.php
│   │   ├── timeline.php
│   │   ├── media.php
│   │   ├── follow.php
│   │   └── unfollow.php
│   ├── user/
│   │   ├── followed-cases.php
│   │   └── profile.php
│   └── notifications/
│       ├── list.php
│       ├── mark-read.php
│       └── mark-all-read.php
│
├── src/                 # Core application code
│   ├── Config/
│   │   ├── Database.php
│   │   └── App.php
│   ├── Models/
│   │   ├── CaseThread.php
│   │   ├── TimelineEvent.php
│   │   ├── CaseDocument.php
│   │   ├── CaseMedia.php
│   │   ├── CaseReview.php
│   │   ├── CaseFollow.php
│   │   └── Notification.php
│   ├── Controllers/
│   │   ├── CaseController.php
│   │   ├── UserController.php
│   │   └── NotificationController.php
│   ├── Services/
│   │   ├── CaseService.php
│   │   └── NotificationService.php
│   └── Helpers/
│       ├── Response.php
│       ├── Auth.php
│       ├── Validator.php
│       └── Pagination.php
│
├── views/               # Server-rendered HTML templates
│   ├── layouts/
│   │   ├── header.php
│   │   ├── footer.php
│   │   └── navbar.php
│   ├── cases/
│   │   ├── index.php    # Case listing page
│   │   └── detail.php   # Single case page
│   └── partials/
│       ├── case-card.php
│       ├── timeline-item.php
│       └── article-item.php
│
├── config/
│   ├── database.php
│   └── app.php
│
├── vendor/              # Composer dependencies (optional)
│
└── .env                 # Environment variables
```

## Implementation Philosophy

1. **Simple Router**: Use a front controller pattern without heavy dependencies
2. **PDO for Database**: Prepared statements for security
3. **Repository Pattern**: Models handle data access logic
4. **Service Layer**: Business logic separated from controllers
5. **JSON Responses**: Consistent API response format
6. **Token Auth**: Simple JWT or session-based auth

---

## Key PHP Files (Basic Structure)

### 1. Database Connection

**File: src/Config/Database.php**

```php
<?php

namespace CaseThreads\Config;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $database = $_ENV['DB_NAME'] ?? 'news_website';
        $username = $_ENV['DB_USER'] ?? 'root';
        $password = $_ENV['DB_PASS'] ?? '';
        
        try {
            $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new \Exception("Database connection failed");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->connection->lastInsertId();
    }
    
    public function update($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function delete($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
}
```

### 2. Response Helper

**File: src/Helpers/Response.php**

```php
<?php

namespace CaseThreads\Helpers;

class Response {
    public static function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    public static function success($data, $message = null) {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }
    
    public static function error($message, $code = 'ERROR', $statusCode = 400, $details = null) {
        self::json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details
            ]
        ], $statusCode);
    }
    
    public static function notFound($message = 'Resource not found') {
        self::error($message, 'NOT_FOUND', 404);
    }
    
    public static function unauthorized($message = 'Unauthorized access') {
        self::error($message, 'UNAUTHORIZED', 401);
    }
    
    public static function forbidden($message = 'Access forbidden') {
        self::error($message, 'FORBIDDEN', 403);
    }
    
    public static function validationError($errors) {
        self::error('Validation failed', 'VALIDATION_ERROR', 422, $errors);
    }
}
```

### 3. Pagination Helper

**File: src/Helpers/Pagination.php**

```php
<?php

namespace CaseThreads\Helpers;

class Pagination {
    public static function build($totalResults, $currentPage = 1, $perPage = 20) {
        $totalPages = ceil($totalResults / $perPage);
        $currentPage = max(1, min($currentPage, $totalPages));
        $offset = ($currentPage - 1) * $perPage;
        
        return [
            'pagination' => [
                'current_page' => $currentPage,
                'total_pages' => $totalPages,
                'total_results' => $totalResults,
                'per_page' => $perPage
            ],
            'offset' => $offset,
            'limit' => $perPage
        ];
    }
}
```

### 4. Authentication Helper

**File: src/Helpers/Auth.php**

```php
<?php

namespace CaseThreads\Helpers;

class Auth {
    public static function check() {
        // Check if user is authenticated via session or token
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check session
        if (isset($_SESSION['user_id'])) {
            return $_SESSION['user_id'];
        }
        
        // Check Bearer token
        $token = self::getBearerToken();
        if ($token) {
            return self::validateToken($token);
        }
        
        return null;
    }
    
    public static function requireAuth() {
        $userId = self::check();
        if (!$userId) {
            Response::unauthorized();
        }
        return $userId;
    }
    
    private static function getBearerToken() {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $matches = [];
            if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
    
    private static function validateToken($token) {
        // Simple token validation (in production, use JWT)
        // For now, check against database or cache
        $db = \CaseThreads\Config\Database::getInstance();
        $user = $db->fetchOne(
            "SELECT id FROM users WHERE api_token = ? AND token_expires_at > NOW()",
            [$token]
        );
        
        return $user ? $user['id'] : null;
    }
    
    public static function user() {
        $userId = self::check();
        if (!$userId) {
            return null;
        }
        
        $db = \CaseThreads\Config\Database::getInstance();
        return $db->fetchOne(
            "SELECT id, full_name, email, profile_photo FROM users WHERE id = ?",
            [$userId]
        );
    }
}
```

---

## API Router Example

**File: api/index.php**

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php'; // If using Composer
require_once __DIR__ . '/../src/Config/Database.php';
require_once __DIR__ . '/../src/Helpers/Response.php';
require_once __DIR__ . '/../src/Helpers/Auth.php';

use CaseThreads\Helpers\Response;

// Enable CORS for mobile app
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Simple router
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove /api/ prefix and query string
$path = preg_replace('/^\/api/', '', parse_url($requestUri, PHP_URL_PATH));

// Route matching
switch (true) {
    // Cases
    case $requestMethod === 'GET' && $path === '/cases':
        require __DIR__ . '/cases/list.php';
        break;
        
    case $requestMethod === 'GET' && preg_match('/^\/cases\/(\d+)$/', $path, $matches):
        $_GET['id'] = $matches[1];
        require __DIR__ . '/cases/single.php';
        break;
        
    case $requestMethod === 'GET' && preg_match('/^\/cases\/(\d+)\/articles$/', $path, $matches):
        $_GET['case_id'] = $matches[1];
        require __DIR__ . '/cases/articles.php';
        break;
        
    case $requestMethod === 'GET' && preg_match('/^\/cases\/(\d+)\/timeline$/', $path, $matches):
        $_GET['case_id'] = $matches[1];
        require __DIR__ . '/cases/timeline.php';
        break;
        
    case $requestMethod === 'POST' && preg_match('/^\/cases\/(\d+)\/follow$/', $path, $matches):
        $_POST['case_id'] = $matches[1];
        require __DIR__ . '/cases/follow.php';
        break;
        
    case $requestMethod === 'DELETE' && preg_match('/^\/cases\/(\d+)\/follow$/', $path, $matches):
        $_POST['case_id'] = $matches[1];
        require __DIR__ . '/cases/unfollow.php';
        break;
        
    // User
    case $requestMethod === 'GET' && $path === '/user/followed-cases':
        require __DIR__ . '/user/followed-cases.php';
        break;
        
    // Notifications
    case $requestMethod === 'GET' && $path === '/notifications':
        require __DIR__ . '/notifications/list.php';
        break;
        
    case $requestMethod === 'POST' && preg_match('/^\/notifications\/(\d+)\/read$/', $path, $matches):
        $_POST['notification_id'] = $matches[1];
        require __DIR__ . '/notifications/mark-read.php';
        break;
        
    default:
        Response::notFound('Endpoint not found');
}
```

---

This provides the foundation. Next, I'll create the actual endpoint implementations and models.
