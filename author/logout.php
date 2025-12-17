<?php
/**
 * Author Logout
 */

require_once __DIR__ . '/../includes/Session.php';
require_once __DIR__ . '/../includes/Functions.php';

Session::start();
Session::destroy();

// Clear remember me cookie
if (isset($_COOKIE['author_remember'])) {
    setcookie('author_remember', '', time() - 3600, '/');
}

redirect(BASE_URL . '/author/login.php');
