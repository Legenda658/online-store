<?php
session_start();
require_once '../config/database.php';
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../auth/login.php");
    exit();
}
if (isset($_GET['id'])) {
    $product_id = $_GET['id'];
    try {
        $stmt_get_images = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
        $stmt_get_images->execute([$product_id]);
        $images = $stmt_get_images->fetchAll(PDO::FETCH_COLUMN);
        foreach ($images as $image_path) {
            if (file_exists('../' . $image_path)) {
                unlink('../' . $image_path);
            }
        }
        $stmt_delete_images = $pdo->prepare("DELETE FROM product_images WHERE product_id = ?");
        $stmt_delete_images->execute([$product_id]);
        $stmt_delete_product = $pdo->prepare("DELETE FROM products WHERE id = ?");
        if ($stmt_delete_product->execute([$product_id])) {
            $_SESSION['success'] = "Товар успешно удален.";
        } else {
            $_SESSION['errors'][] = "Ошибка при удалении товара.";
        }
    } catch (PDOException $e) {
        $_SESSION['errors'][] = "Ошибка базы данных: " . $e->getMessage();
    }
} else {
    $_SESSION['errors'][] = "Не указан ID товара для удаления.";
}
header("Location: products.php");
exit();
?>