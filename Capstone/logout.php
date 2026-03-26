<?php
require_once 'includes/config.php';
if (isLoggedIn()) {
    $db = getDB();
    $uid = $_SESSION['user_id'];
    $ip  = $_SERVER['REMOTE_ADDR'];
    $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'LOGOUT', 'User logged out', ?)");
    $stmt->bind_param('is', $uid, $ip);
    $stmt->execute();
    $db->close();
}
session_destroy();
header('Location: index.php');
exit;
