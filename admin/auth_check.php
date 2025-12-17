<?php
/**
 * Admin Authentication Check
 * Include this file at the top of all admin pages
 */

// Suppress deprecation warnings before loading any libraries
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

// Load required files first
require_once '../config/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Session timeout (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit;
}

$_SESSION['last_activity'] = time();

// Get current admin info
$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];
$admin_role = $_SESSION['admin_role'];
$admin_email = $_SESSION['admin_email'];

/**
 * Check if admin has permission for an action
 */
function hasPermission($required_role = 'admin') {
    global $admin_role;
    
    $roles = ['super_admin' => 3, 'admin' => 2, 'editor' => 1];
    
    return $roles[$admin_role] >= $roles[$required_role];
}

/**
 * Require specific permission or redirect
 */
function requirePermission($required_role = 'admin') {
    if (!hasPermission($required_role)) {
        header('Location: dashboard.php?error=permission_denied');
        exit;
    }
}
