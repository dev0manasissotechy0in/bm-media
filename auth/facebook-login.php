<?php
/**
 * Facebook OAuth Login Handler
 */

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Session.php';

// This is a placeholder for Facebook OAuth integration
// Full implementation requires:
// 1. Facebook Developer App
// 2. App ID and App Secret
// 3. Facebook PHP SDK (composer require facebook/graph-sdk)

$_SESSION['error'] = 'Facebook login is not yet configured. Please contact the administrator.';
header('Location: ' . BASE_URL . '/login.php');
exit;

// Example implementation structure:
/*
require_once 'vendor/autoload.php';

$fb = new Facebook\Facebook([
    'app_id' => 'YOUR_APP_ID',
    'app_secret' => 'YOUR_APP_SECRET',
    'default_graph_version' => 'v12.0',
]);

$helper = $fb->getRedirectLoginHelper();

if (isset($_GET['code'])) {
    try {
        $accessToken = $helper->getAccessToken();
        
        $response = $fb->get('/me?fields=id,name,email', $accessToken);
        $user = $response->getGraphUser();
        
        $email = $user['email'];
        $name = $user['name'];
        $fb_id = $user['id'];
        
        // Check if user exists
        $db = Database::getInstance();
        $existing_user = $db->fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
        
        if ($existing_user) {
            // Login existing user
            $_SESSION['user_id'] = $existing_user['id'];
            $_SESSION['username'] = $existing_user['username'];
            header('Location: ' . BASE_URL);
        } else {
            // Register new user
            $username = explode('@', $email)[0];
            $db->insert('users', [
                'username' => $username,
                'email' => $email,
                'full_name' => $name,
                'facebook_id' => $fb_id,
                'password' => '', // No password for OAuth users
                'status' => 'active'
            ]);
            
            $user_id = $db->lastInsertId();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            header('Location: ' . BASE_URL);
        }
    } catch(Facebook\Exceptions\FacebookResponseException $e) {
        $_SESSION['error'] = 'Facebook login error: ' . $e->getMessage();
        header('Location: ' . BASE_URL . '/login.php');
    }
} else {
    $permissions = ['email'];
    $loginUrl = $helper->getLoginUrl(BASE_URL . '/auth/facebook-login.php', $permissions);
    header('Location: ' . $loginUrl);
}
*/
?>