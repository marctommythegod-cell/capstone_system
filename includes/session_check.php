<?php
// includes/session_check.php - Session Validation

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /SYSTEM/index.php');
    exit;
}

// Check if user exists and is active
require_once __DIR__ . '/../config/db.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT id, role FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: /SYSTEM/index.php');
    exit;
}

$_SESSION['user_role'] = $user['role'];
?>
