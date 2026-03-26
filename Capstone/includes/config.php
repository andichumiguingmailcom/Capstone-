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

// ── SESSION START ──
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── AUTH HELPERS ──
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin($role = null) {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
    if ($role && $_SESSION['role'] !== $role && $_SESSION['role'] !== 'admin') {
        header('Location: unauthorized.php');
        exit;
    }
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isMember() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'member';
}

// ── SANITIZE INPUT ──
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
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
