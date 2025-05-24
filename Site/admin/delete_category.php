<?php
session_start();
require_once '../config/database.php';
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../auth/login.php");
    exit();
}
if (isset($_GET['id'])) {
    $category_id = $_GET['id'];
    try {
        $stmt_check_children = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
        $stmt_check_children->execute([$category_id]);
        $children_count = $stmt_check_children->fetchColumn();
        $stmt_check_products = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt_check_products->execute([$category_id]);
        $products_count = $stmt_check_products->fetchColumn();
        if ($children_count > 0) {
             $_SESSION['errors'][] = "Невозможно удалить категорию с дочерними категориями. Сначала удалите или переместите дочерние категории.";
        } elseif ($products_count > 0) {
             $_SESSION['errors'][] = "Невозможно удалить категорию, содержащую товары. Сначала удалите или переместите товары из этой категории.";
        } else {
            $stmt_delete = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            if ($stmt_delete->execute([$category_id])) {
                $_SESSION['success'] = "Категория успешно удалена.";
            } else {
                $_SESSION['errors'][] = "Ошибка при удалении категории.";
            }
        }
    } catch (PDOException $e) {
        $_SESSION['errors'][] = "Ошибка базы данных: " . $e->getMessage();
    }
} else {
    $_SESSION['errors'][] = "Не указан ID категории для удаления.";
}
header("Location: categories.php");
exit();
?>