<?php
/**
 * admin/auth.php
 * Session guard — include at the top of every protected admin page.
 * Redirects to the login page if the user is not authenticated.
 */

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require config and functions (safe to require multiple times via require_once)
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Check for active admin session
if (empty($_SESSION['admin_id'])) {
    // Preserve the intended destination so we can redirect after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . url('admin/login.php'));
    exit;
}
