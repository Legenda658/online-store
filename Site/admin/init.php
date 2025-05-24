<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
function isAdmin() {
    return isset($_SESSION['user_id']) && $_SESSION['is_admin'];
}
if (!isAdmin()) {
    header('Location: ../auth/login.php');
    exit();
} 