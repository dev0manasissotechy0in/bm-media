<?php
/**
 * Admin Logout - Clear session and remember me token
 */

require_once '../config/config.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear remember me cookie and token from database
if (isset($_COOKIE['admin_remember']) && isset($_SESSION['admin_id'])) {
    $db = Database::getInstance();
    
    // Clear remember token from database
    $db->update('admin_users', [
        'remember_token' => null
    ], 'id = ?', [$_SESSION['admin_id']]);
}

// Clear remember me cookie (even if session doesn't exist)
if (isset($_COOKIE['admin_remember'])) {
    setcookie('admin_remember', '', time() - 3600, '/', '', false, true);
    unset($_COOKIE['admin_remember']);
}

// Destroy session
session_unset();
session_destroy();

// Start a new session for the logout message
session_start();

header('Location: login.php?logout=1');
exit;
