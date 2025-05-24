<?php
header('X-Content-Type-Options: nosniff');
require_once 'init.php';
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../auth/login.php');
    exit();
}
$stmt = $pdo->query("SELECT fm.*, u.username FROM feedback_messages fm JOIN users u ON fm.user_id = u.id ORDER BY fm.created_at DESC");
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
$currentPage = 'feedback';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ: Обратная связь</title>
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
        <div class="admin-container">
            <h1>Сообщения обратной связи</h1>
            <?php if (empty($messages)): ?>
                <p>Нет сообщений обратной связи.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Пользователь</th>
                            <th>Сообщение</th>
                            <th>Дата создания</th>
                            <th>Ответ администратора</th>
                            <th>Дата ответа</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $message): ?>
                            <tr>
                                <td><?php echo $message['id']; ?></td>
                                <td><?php echo htmlspecialchars($message['username']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($message['message'])); ?></td>
                                <td><?php echo $message['created_at']; ?></td>
                                <td><?php echo nl2br(htmlspecialchars($message['admin_response'] ?? '')); ?></td>
                                <td><?php echo $message['responded_at']; ?></td>
                                <td class="actions">
                                    <a href="view_feedback.php?id=<?php echo $message['id']; ?>" class="btn-secondary">Просмотреть/Ответить</a>
                                    <!-- Возможность удалить сообщение -->
                                    <!-- <a href="delete_feedback.php?id=<?php echo $message['id']; ?>" class="btn-danger" onclick="return confirm('Вы уверены?')">Удалить</a> -->
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
    <footer>
        <!-- Футер админки -->
    </footer>
</body>
</html> 