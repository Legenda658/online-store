<?php
session_start();
require_once '../config/database.php';
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../auth/login.php");
    exit();
}
if (isset($_GET['id'])) {
    $brand_id = $_GET['id'];
    try {
        $stmt_check_products = $pdo->prepare("SELECT COUNT(*) FROM products WHERE brand_id = ?");
        $stmt_check_products->execute([$brand_id]);
        $products_count = $stmt_check_products->fetchColumn();
        if ($products_count > 0) {
             $_SESSION['errors'][] = "Невозможно удалить бренд, связанный с товарами. Сначала удалите или измените бренд для этих товаров.";
        } else {
            $stmt_get_logo = $pdo->prepare("SELECT logo FROM brands WHERE id = ?");
            $stmt_get_logo->execute([$brand_id]);
            $logo_path = $stmt_get_logo->fetchColumn();
            if ($logo_path && file_exists('../' . $logo_path)) {
                unlink('../' . $logo_path);
            }
            $stmt_delete = $pdo->prepare("DELETE FROM brands WHERE id = ?");
            if ($stmt_delete->execute([$brand_id])) {
                $_SESSION['success'] = "Бренд успешно удален.";
            } else {
                $_SESSION['errors'][] = "Ошибка при удалении бренда.";
            }
        }
    } catch (PDOException $e) {
        $_SESSION['errors'][] = "Ошибка базы данных: " . $e->getMessage();
    }
} else {
    $_SESSION['errors'][] = "Не указан ID бренда для удаления.";
}
header("Location: brands.php");
exit();
?>