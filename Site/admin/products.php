<?php
header('X-Content-Type-Options: nosniff');
require_once 'init.php';
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../auth/login.php");
    exit();
}
$stmt = $pdo->query("
    SELECT p.*, c.name as category_name, b.name as brand_name, pi.image_path as main_image
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
    ORDER BY p.created_at DESC
");
$products = $stmt->fetchAll();
$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['success']);
$error_messages = isset($_SESSION['errors']) ? $_SESSION['errors'] : [];
unset($_SESSION['errors']);
$currentPage = 'products';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ: Товары</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" href="../AnaLeg.ico">
</head>
<body>
    <header>
        <nav class="main-nav">
            <div class="logo">
                <a href="index.php">Админ панель</a>
            </div>
            <ul class="nav-links">
                <li><a href="index.php" class="<?php echo ($currentPage == 'index') ? 'active' : ''; ?>">Главная (Админ)</a></li>
                <li><a href="products.php" class="<?php echo ($currentPage == 'products') ? 'active' : ''; ?>">Товары</a></li>
                <li><a href="categories.php" class="<?php echo ($currentPage == 'categories') ? 'active' : ''; ?>">Категории</a></li>
                <li><a href="brands.php" class="<?php echo ($currentPage == 'brands') ? 'active' : ''; ?>">Бренды</a></li>
                <li><a href="orders.php" class="<?php echo ($currentPage == 'orders') ? 'active' : ''; ?>">Заказы</a></li>
                <li><a href="users.php" class="<?php echo ($currentPage == 'users') ? 'active' : ''; ?>">Пользователи</a></li>
                <li><a href="feedback.php" class="<?php echo ($currentPage == 'feedback') ? 'active' : ''; ?>">Обратная связь</a></li>
            </ul>
            <div class="nav-right">
                <a href="../index.php">На сайт</a>
                <a href="../auth/logout.php">Выйти</a>
            </div>
        </nav>
    </header>
    <main>
        <h1>Управление товарами</h1>
        <div class="admin-section">
            <?php if (!empty($error_messages)): ?>
                <div class="errors">
                    <?php foreach ($error_messages as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="success">
                    <p><?php echo htmlspecialchars($success_message); ?></p>
                </div>
            <?php endif; ?>
            <h2>Список товаров</h2>
            <a href="add_product.php" class="btn-primary" style="width: auto; margin-bottom: 20px;">Добавить новый товар</a>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Изображение</th>
                        <th>Название</th>
                        <th>Цена</th>
                        <th>Категория</th>
                        <th>Бренд</th>
                        <th>На складе</th>
                        <th>Активен</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo $product['id']; ?></td>
                            <td>
                                <?php if ($product['main_image']): ?>
                                    <img src="../<?php echo htmlspecialchars($product['main_image']); ?>" alt="Изображение товара" width="50">
                                <?php else: ?>
                                    Нет изображения
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo number_format($product['price'], 2); ?> ₽</td>
                            <td><?php echo htmlspecialchars($product['category_name'] ? $product['category_name'] : 'Нет'); ?></td>
                            <td><?php echo htmlspecialchars($product['brand_name'] ? $product['brand_name'] : 'Нет'); ?></td>
                            <td><?php echo $product['stock']; ?></td>
                            <td><?php echo $product['is_active'] ? 'Да' : 'Нет'; ?></td>
                            <td class="action-links">
                                <a href="edit_product.php?id=<?php echo $product['id']; ?>">Редактировать</a>
                                <a href="delete_product.php?id=<?php echo $product['id']; ?>" onclick="return confirm('Вы уверены, что хотите удалить этот товар?');">Удалить</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html> 