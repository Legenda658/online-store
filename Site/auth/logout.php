<?php
header('X-Content-Type-Options: nosniff');
session_start();
require_once '../config/database.php';
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}
session_unset();
session_destroy();
setcookie('remember_me_token', '', time() - 3600, "/");
header("Location: ../index.php");
exit();
?>