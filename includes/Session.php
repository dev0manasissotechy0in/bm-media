<?php
// ============================================================
// SESSION MANAGEMENT CLASS
// ============================================================

class Session {
    
    /**
     * Start session with secure settings
     */
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            
            session_name('NEWS_SESSION');
            session_start();
            
            // Regenerate session ID periodically
            if (!isset($_SESSION['CREATED'])) {
                $_SESSION['CREATED'] = time();
            } elseif (time() - $_SESSION['CREATED'] > 1800) {
                session_regenerate_id(true);
                $_SESSION['CREATED'] = time();
            }
        }
    }
    
    /**
     * Set session variable
     */
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get session variable
     */
    public static function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Check if session variable exists
     */
    public static function has($key) {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Delete session variable
     */
    public static function delete($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    /**
     * Set flash message
     */
    public static function setFlash($key, $message, $type = 'info') {
        $_SESSION['flash'][$key] = [
            'message' => $message,
            'type' => $type
        ];
    }
    
    /**
     * Get flash message
     */
    public static function getFlash($key) {
        if (isset($_SESSION['flash'][$key])) {
            $flash = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $flash;
        }
        return null;
    }
    
    /**
     * Check if flash message exists
     */
    public static function hasFlash($key) {
        return isset($_SESSION['flash'][$key]);
    }
    
    /**
     * Destroy session
     */
    public static function destroy() {
        session_unset();
        session_destroy();
    }
    
    /**
     * Regenerate session ID
     */
    public static function regenerate() {
        session_regenerate_id(true);
        $_SESSION['CREATED'] = time();
    }
}
?>
