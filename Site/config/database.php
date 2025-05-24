<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$host = 'localhost';
$dbname = 'u3073667_sale';
$username = 'u3073667_sale';
$password = 'u3073667_sale';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
} 