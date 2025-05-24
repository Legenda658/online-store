<?php
header('X-Content-Type-Options: nosniff');
require_once 'init.php';
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../auth/login.php");
    exit();
}
$stmt = $pdo->query("
    SELECT o.*, u.username 
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
");
$orders = $stmt->fetchAll();
$currentPage = 'orders';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ: Заказы</title>
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
        <h1>Управление заказами</h1>
        <div class="admin-section">
            <h2>Список заказов</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID Заказа</th>
                        <th>Пользователь</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                        <th>Дата создания</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo $order['id']; ?></td>
                            <td><?php echo htmlspecialchars($order['username']); ?></td>
                            <td><?php echo number_format($order['total_amount'], 2); ?> ₽</td>
                            <td><?php echo htmlspecialchars($order['status']); ?></td>
                            <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                            <td class="action-links">
                                <a href="view_order.php?id=<?php echo $order['id']; ?>">Просмотреть</a>
                                <a href="edit_order.php?id=<?php echo $order['id']; ?>">Редактировать</a>
                                <a href="delete_order.php?id=<?php echo $order['id']; ?>" onclick="return confirm('Вы уверены, что хотите удалить этот заказ?');">Удалить</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html> 