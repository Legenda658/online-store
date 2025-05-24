<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id']) && isset($_POST['quantity'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $user_id = $_SESSION['user_id'] ?? null;

    if ($product_id <= 0 || $quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Некорректные данные товара или количество.']);
        exit();
    }

    if ($user_id === null) {
        echo json_encode(['success' => false, 'message' => 'Пожалуйста, войдите, чтобы добавить товары в корзину.']);
        exit();
    }

    try {
        $stmt_check = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt_check->execute([$user_id, $product_id]);
        $existing_item = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($existing_item) {
            $new_quantity = $existing_item['quantity'] + $quantity;
            $stmt_update = $pdo->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
            $stmt_update->execute([$new_quantity, $user_id, $product_id]);
        } else {
            $stmt_insert = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
            $stmt_insert->execute([$user_id, $product_id, $quantity]);
        }

        // Получаем общее количество товаров в корзине
        $stmt_count = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt_count->execute([$user_id]);
        $cart_count = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        echo json_encode([
            'success' => true,
            'message' => 'Товар добавлен в корзину',
            'cart_count' => $cart_count
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка при добавлении товара в корзину: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Неверный запрос'
    ]);
} 