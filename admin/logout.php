<?php
/**
 * admin/logout.php
 * Destroys the admin session and redirects to the login page.
 */

session_start();
session_unset();
session_destroy();

// Clear the session cookie (optional but cleaner)
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

require_once dirname(__DIR__) . '/includes/functions.php';
header('Location: ' . url('admin/login.php?logged_out=1'));
exit;
