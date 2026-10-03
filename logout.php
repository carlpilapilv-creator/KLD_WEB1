<?php
/**
 * Logout Handler
 * Fully destroys session, clears cookie, logs audit event, then redirects.
 */
require_once 'includes/auth_helper.php';

// Audit log before destroying session
$userId = $_SESSION['user']['id'] ?? null;
if ($userId) {
    auditLog((int) $userId, 'logout');
}

// Destroy session completely
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

// Redirect with feedback
header('Location: login.php?logged_out=1');
exit;
