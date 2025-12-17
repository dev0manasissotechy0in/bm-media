<?php
/**
 * Author Authentication Check
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Session.php';
require_once __DIR__ . '/../includes/Functions.php';

Session::start();

// Check if author is logged in
if (!Session::has('author_id')) {
    Session::setFlash('error', 'Please login to access author dashboard.', 'warning');
    redirect(BASE_URL . '/author/login.php');
}

// Get author data
$db = Database::getInstance();
$author_id = Session::get('author_id');
$author = $db->fetchOne("SELECT * FROM authors WHERE id = ? AND status = 'active'", [$author_id]);

// If author not found or inactive, logout
if (!$author) {
    Session::destroy();
    Session::setFlash('error', 'Your account is no longer active. Please contact admin.', 'danger');
    redirect(BASE_URL . '/author/login.php');
}

// Update session data
Session::set('author_name', $author['full_name']);
Session::set('author_email', $author['email']);
Session::set('author_photo', $author['profile_photo']);
Session::set('author_permissions', json_decode($author['permissions'] ?? '[]', true));

// Helper function to check permissions
function hasPermission($permission) {
    $permissions = Session::get('author_permissions', []);
    return in_array($permission, $permissions);
}
