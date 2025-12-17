<?php
/**
 * Switch from Admin to Author Panel
 * Only for Super Admin with Author ID 1
 */

require_once 'auth_check.php';

// Get author account for this admin
$db = Database::getInstance();
$author = $db->fetchOne(
    "SELECT * FROM authors WHERE email = ? AND id = 1",
    [$_SESSION['admin_email'] ?? '']
);

if (!$author) {
    Session::setFlash('error', 'Author account not found or access denied.', 'danger');
    redirect('dashboard.php');
}

// Clear admin session
Session::destroy();

// Start new session for author
Session::start();
Session::set('author_id', $author['id']);
Session::set('author_email', $author['email']);
Session::set('author_name', $author['full_name']);
Session::set('author_photo', $author['profile_photo']);
Session::set('author_permissions', json_decode($author['permissions'] ?? '[]', true));

// Redirect to author dashboard
redirect(BASE_URL . '/author/dashboard.php');
