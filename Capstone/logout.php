<?php
require_once 'includes/config.php';

$context = clean($_GET['context'] ?? '');
if (!in_array($context, ['admin', 'member'], true)) {
    $context = getSessionContext();
}

if (session_status() === PHP_SESSION_NONE) {
    startSession($context);
}

$param = $context === 'member' ? 'member_ctx' : 'admin_ctx';
$token = clean($_GET[$param] ?? '');

$logoutUserId = null;
if ($token && isset($_SESSION[$context . '_contexts'][$token])) {
    $logoutUserId = $_SESSION[$context . '_contexts'][$token]['id'] ?? null;
    unset($_SESSION[$context . '_contexts'][$token]);
    if (!empty($_SESSION[$context . '_current_ctx']) && $_SESSION[$context . '_current_ctx'] === $token) {
        unset($_SESSION[$context . '_current_ctx']);
        if (!empty($_SESSION[$context . '_contexts'])) {
            $keys = array_keys($_SESSION[$context . '_contexts']);
            $_SESSION[$context . '_current_ctx'] = $keys[0];
        }
    }
}

if ($logoutUserId !== null) {
    $db = getDB();
    $ip  = $_SERVER['REMOTE_ADDR'];
    $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'LOGOUT', 'User logged out', ?)");
    $stmt->bind_param('is', $logoutUserId, $ip);
    $stmt->execute();
    $db->close();
}
if (empty($_SESSION[$context . '_contexts'])) {
    unset($_SESSION[$context . '_contexts'], $_SESSION[$context . '_current_ctx']);
}

if (empty($_SESSION['admin_contexts']) && empty($_SESSION['member_contexts'])) {
    session_unset();
    session_destroy();
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
}

if ($context === 'member') {
    header('Location: Member/member_login.php');
} else {
    header('Location: index.php');
}
exit;
