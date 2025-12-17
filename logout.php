<?php
require_once 'config/config.php';

// Determine logout type
$type = $_GET['type'] ?? 'user';

switch ($type) {
    case 'admin':
        Security::logout('admin', '/admin/login.php');
        break;
    case 'reporter':
        Security::logout('reporter', '/reporter/login.php');
        break;
    default:
        Security::logout('user', '/');
        break;
}
?>
