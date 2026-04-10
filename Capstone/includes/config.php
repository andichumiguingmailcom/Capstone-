<?php
// ── DATABASE CONFIGURATION ──
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'coop_ims');

// ── ESTABLISH CONNECTION ──
function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

// ── SANITIZE INPUT ──
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// ── SESSION CONTEXT HELPERS ──
function getSessionContext() {
    $context = clean($_REQUEST['context'] ?? '');
    if (in_array($context, ['admin', 'member'], true)) {
        return $context;
    }

    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '');
    if (strpos(strtolower($script), '/member/') !== false || strpos(strtolower($script), '/member_') !== false) {
        return 'member';
    }

    return 'admin';
}

function getSessionName($context = null) {
    $context = $context ?? getSessionContext();
    return $context === 'member' ? 'COOPIMS_MEMBER_SESSID' : 'COOPIMS_ADMIN_SESSID';
}

function getContextParamName($context = null) {
    $context = $context ?? getSessionContext();
    return $context === 'member' ? 'member_ctx' : 'admin_ctx';
}

function getAppRoot() {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '');
    $path = dirname($script);
    if (preg_match('#^(.*/(?:Admin|Member))$#i', $path, $matches)) {
        return dirname($matches[1]);
    }
    return $path === '/' ? '' : $path;
}

function startSession($context = null) {
    if (session_status() === PHP_SESSION_NONE) {
        $context = $context ?? getSessionContext();
        session_name(getSessionName($context));
        session_set_cookie_params(0, '/', '', false, true);
        session_start();
    }
}

function getContextToken($context = null) {
    $context = $context ?? getSessionContext();
    $param = getContextParamName($context);
    $token = clean($_REQUEST[$param] ?? '');

    if ($token && isset($_SESSION[$context . '_contexts'][$token])) {
        $_SESSION[$context . '_current_ctx'] = $token;
        return $token;
    }

    if (!empty($_SESSION[$context . '_current_ctx']) && isset($_SESSION[$context . '_contexts'][$_SESSION[$context . '_current_ctx']])) {
        return $_SESSION[$context . '_current_ctx'];
    }

    if (!empty($_SESSION[$context . '_contexts']) && is_array($_SESSION[$context . '_contexts'])) {
        $keys = array_keys($_SESSION[$context . '_contexts']);
        if ($keys) {
            $_SESSION[$context . '_current_ctx'] = $keys[0];
            return $keys[0];
        }
    }

    return '';
}

function getContextData($context = null) {
    $context = $context ?? getSessionContext();
    $token = getContextToken($context);
    if (!$token) {
        return null;
    }
    return $_SESSION[$context . '_contexts'][$token] ?? null;
}

function syncLegacySession() {
    $context = getSessionContext();
    $data = getContextData($context);

    if ($data) {
        $_SESSION['user_id']   = $data['id'];
        $_SESSION['user_name'] = $data['name'];
        $_SESSION['role']      = $data['role'];
        if ($context === 'member') {
            $_SESSION['member_id'] = $data['member_id'] ?? $data['id'];
        } else {
            unset($_SESSION['member_id']);
        }
    } else {
        unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['role'], $_SESSION['member_id']);
    }
}

function appendContextToUrl($url, $context = null) {
    $context = $context ?? getSessionContext();
    $param = getContextParamName($context);
    $token = getContextToken($context);

    if (!$token || stripos($url, $param . '=') !== false) {
        return $url;
    }

    $parts = explode('#', $url, 2);
    $hash = isset($parts[1]) ? '#' . $parts[1] : '';
    $url = $parts[0];

    if (strpos($url, '?') === false) {
        $url .= '?' . $param . '=' . urlencode($token);
    } else {
        $url .= '&' . $param . '=' . urlencode($token);
    }

    return $url . $hash;
}

startSession();
syncLegacySession();

// ── AUTH HELPERS ─
function isLoggedIn() {
    return getContextData() !== null;
}

function requireLogin($role = null) {
    if (!isLoggedIn()) {
        $context = getSessionContext();
        $root = getAppRoot();
        if ($context === 'member') {
            header('Location: ' . ($root ?: '') . '/Member/member_login.php');
        } else {
            header('Location: ' . ($root ?: '') . '/index.php');
        }
        exit;
    }

    if (!$role) {
        return;
    }

    $allowedRoles = is_array($role) ? $role : [$role];
    $allowedRoles[] = 'general_manager'; // GM has override rights

    if (!in_array($_SESSION['role'], $allowedRoles, true)) {
        header('Location: unauthorized.php');
        exit;
    }
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'general_manager';
}

function isMember() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'member';
}

// ── RESPONSE HELPER ──
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ── CURRENT USER ──
function getCurrentUser() {
    return [
        'id'   => $_SESSION['user_id']   ?? null,
        'name' => $_SESSION['user_name'] ?? 'Guest',
        'role' => $_SESSION['role']      ?? 'guest',
    ];
}
?>
