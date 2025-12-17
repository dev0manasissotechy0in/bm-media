<?php
// ============================================================
// SECURITY CLASS - XSS, CSRF, SQL Injection Protection
// ============================================================

class Security {
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
            $_SESSION[CSRF_TOKEN_NAME . '_time'] = time();
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCSRFToken($token) {
        if (!isset($_SESSION[CSRF_TOKEN_NAME]) || !isset($_SESSION[CSRF_TOKEN_NAME . '_time'])) {
            return false;
        }
        
        // Check if token expired
        if (time() - $_SESSION[CSRF_TOKEN_NAME . '_time'] > CSRF_TOKEN_EXPIRE) {
            self::destroyCSRFToken();
            return false;
        }
        
        return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
    
    /**
     * Destroy CSRF token
     */
    public static function destroyCSRFToken() {
        unset($_SESSION[CSRF_TOKEN_NAME]);
        unset($_SESSION[CSRF_TOKEN_NAME . '_time']);
    }
    
    /**
     * Get CSRF token input field
     */
    public static function getCSRFTokenField() {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
    
    /**
     * Sanitize input data (prevent XSS)
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
    
    /**
     * Clean HTML content (for rich text editors)
     */
    public static function cleanHTML($html) {
        // Allow specific HTML tags
        $allowed_tags = '<p><br><strong><b><em><i><u><ul><ol><li><a><img><h1><h2><h3><h4><h5><h6><blockquote><table><tr><td><th><thead><tbody><span><div>';
        return strip_tags($html, $allowed_tags);
    }
    
    /**
     * Hash password
     */
    public static function hashPassword($password) {
        return password_hash($password, HASH_ALGO, ['cost' => HASH_COST]);
    }
    
    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Generate unique ID
     */
    public static function generateUniqueId($prefix = '') {
        return $prefix . strtoupper(uniqid());
    }
    
    /**
     * Validate email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate phone number (Indian format)
     */
    public static function validatePhone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return preg_match('/^[6-9]\d{9}$/', $phone);
    }
    
    /**
     * Validate URL
     */
    public static function validateURL($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Encrypt data
     */
    public static function encrypt($data, $key = null) {
        if ($key === null) {
            $key = hash('sha256', DB_PASS);
        }
        
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }
    
    /**
     * Decrypt data
     */
    public static function decrypt($data, $key = null) {
        if ($key === null) {
            $key = hash('sha256', DB_PASS);
        }
        
        list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
        return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
    }
    
    /**
     * Prevent SQL injection (additional layer)
     */
    public static function escape($string) {
        return htmlspecialchars(strip_tags($string), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Rate limiting check
     */
    public static function checkRateLimit($key, $max_attempts = 5, $time_window = 300) {
        if (!isset($_SESSION['rate_limit'])) {
            $_SESSION['rate_limit'] = [];
        }
        
        $current_time = time();
        
        // Initialize key if not exists
        if (!isset($_SESSION['rate_limit'][$key])) {
            $_SESSION['rate_limit'][$key] = [];
        }
        
        // Clean old entries for this specific key
        $_SESSION['rate_limit'][$key] = array_filter($_SESSION['rate_limit'][$key], function($timestamp) use ($current_time, $time_window) {
            return ($current_time - $timestamp) < $time_window;
        });
        
        // Check if exceeded limit
        if (count($_SESSION['rate_limit'][$key]) >= $max_attempts) {
            return false;
        }
        
        // Add new attempt
        $_SESSION['rate_limit'][$key][] = $current_time;
        return true;
    }
    
    /**
     * Get client IP address
     */
    public static function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
    
    /**
     * Validate file upload
     */
    public static function validateFileUpload($file, $allowed_types, $max_size) {
        // Check if file was uploaded
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['success' => false, 'message' => 'Invalid file upload'];
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Upload error: ' . $file['error']];
        }
        
        // Check file size
        if ($file['size'] > $max_size) {
            return ['success' => false, 'message' => 'File size exceeds maximum limit'];
        }
        
        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime, $allowed_types)) {
            return ['success' => false, 'message' => 'Invalid file type'];
        }
        
        return ['success' => true, 'mime' => $mime];
    }
    
    /**
     * Generate secure filename
     */
    public static function generateSecureFilename($original_name) {
        $extension = pathinfo($original_name, PATHINFO_EXTENSION);
        $filename = bin2hex(random_bytes(16));
        return $filename . '.' . strtolower($extension);
    }
    
    /**
     * Create slug from string
     */
    public static function createSlug($string) {
        $string = strtolower(trim($string));
        $string = preg_replace('/[^a-z0-9-]/', '-', $string);
        $string = preg_replace('/-+/', '-', $string);
        return trim($string, '-');
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn($type = 'user') {
        return isset($_SESSION[$type . '_id']) && !empty($_SESSION[$type . '_id']);
    }
    
    /**
     * Require login
     */
    public static function requireLogin($type = 'user', $redirect = '/login.php') {
        if (!self::isLoggedIn($type)) {
            header('Location: ' . BASE_URL . $redirect);
            exit;
        }
    }
    
    /**
     * Check admin permission
     */
    public static function checkAdminPermission($required_role = 'admin') {
        self::requireLogin('admin', '/admin/login.php');
        
        $roles_hierarchy = ['super_admin' => 3, 'admin' => 2, 'editor' => 1];
        $user_role = $_SESSION['admin_role'] ?? 'editor';
        
        if ($roles_hierarchy[$user_role] < $roles_hierarchy[$required_role]) {
            header('Location: ' . ADMIN_URL . '/index.php?error=permission_denied');
            exit;
        }
    }
    
    /**
     * Logout user
     */
    public static function logout($type = 'user', $redirect = '/') {
        unset($_SESSION[$type . '_id']);
        unset($_SESSION[$type . '_email']);
        unset($_SESSION[$type . '_name']);
        if ($type === 'admin') {
            unset($_SESSION['admin_role']);
        }
        session_destroy();
        header('Location: ' . BASE_URL . $redirect);
        exit;
    }
}
?>
