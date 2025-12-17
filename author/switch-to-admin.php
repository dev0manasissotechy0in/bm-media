<?php
/**
 * Switch from Author to Admin Panel
 * Only for Super Admin (Author ID 1)
 */

require_once 'auth_check.php';

// Only allow author ID 1 to switch to admin
if ($author_id != 1) {
    Session::setFlash('error', 'Access denied. Only Super Admin can switch to admin panel.', 'danger');
    redirect('dashboard.php');
}

// Get admin credentials for this author
$db = Database::getInstance();
$admin = $db->fetchOne(
    "SELECT * FROM admin_users WHERE email = ?",
    [$author['email']]
);

if (!$admin) {
    Session::setFlash('error', 'Admin account not found for this email.', 'danger');
    redirect('dashboard.php');
}

// Clear author session
Session::destroy();

// Start new session for admin
Session::start();
Session::set('admin_id', $admin['id']);
Session::set('admin_email', $admin['email']);
Session::set('admin_name', $admin['username']);
Session::set('admin_role', $admin['role']);

// Redirect to admin dashboard
redirect(BASE_URL . '/admin/dashboard.php');
