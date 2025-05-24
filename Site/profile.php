<?php
session_start();
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$stmt = $pdo->prepare("
    SELECT o.*, COUNT(oi.id) as items_count 
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.user_id = ? 
    GROUP BY o.id 
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT * FROM feedback_messages WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$feedback_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="AnaLeg.ico">
</head>
<body>
    <header>
        <nav class="main-nav">
            <div class="logo">
                <a href="index.php">AnaLeg</a>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Главная</a></li>
                <li><a href="catalog.php">Каталог</a></li>
            </ul>
            <div class="nav-right">
                <a href="cart.php" class="cart-link">Корзина</a>
                <a href="auth/logout.php" class="logout-link">Выйти</a>
            </div>
        </nav>
    </header>
    <main>
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php if ($user['avatar']): ?>
                        <div class="avatar-image" style="background-image: url('<?php echo htmlspecialchars($user['avatar']); ?>');"></div>
                    <?php else: ?>
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                </div>
            </div>
            <div class="profile-sections">
                <div class="profile-section">
                    <h3>Загрузить фото профиля</h3>
                    <form action="upload_avatar.php" method="post" enctype="multipart/form-data" id="avatar-form">
                        <div class="file-upload-group">
                            <input type="file" name="avatar" id="avatar-upload" accept="image/*" required>
                            <label for="avatar-upload" class="btn-primary">Выберите фото</label>
                        </div>
                    </form>
                    <script>
                        document.getElementById('avatar-upload').addEventListener('change', function() {
                            document.getElementById('avatar-form').submit();
                        });
                    </script>
                </div>
                <div class="profile-section">
                    <h3>Мои заказы</h3>
                    <?php if ($orders): ?>
                        <ul>
                            <?php foreach ($orders as $order): ?>
                                <li>
                                    <p>Заказ #<?php echo $order['id']; ?></p>
                                    <p>Дата: <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></p>
                                    <p>Статус: <?php 
                                        echo match($order['status']) {
                                            'new' => 'Новый',
                                            'processing' => 'В обработке',
                                            'shipped' => 'Отправлен',
                                            'delivered' => 'Доставлен',
                                            'cancelled' => 'Отменен',
                                            default => $order['status']
                                        }; 
                                    ?></p>
                                    <p>Товаров: <?php echo $order['items_count']; ?></p>
                                    <p>Сумма: <?php echo number_format($order['total_amount'], 2); ?> ₽</p>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>У вас пока нет заказов</p>
                    <?php endif; ?>
                </div>
                <div class="profile-section">
                    <h3>Мои сообщения</h3>
                    <?php if ($feedback_messages): ?>
                        <div class="feedback-list">
                            <?php foreach ($feedback_messages as $message): ?>
                                <div class="feedback-item">
                                    <p><strong>Отправлено:</strong> <?php echo $message['created_at']; ?></p>
                                    <p><strong>Сообщение:</strong> <?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                    <?php if ($message['admin_response']): ?>
                                        <p><strong>Ответ администратора:</strong> <?php echo nl2br(htmlspecialchars($message['admin_response'])); ?></p>
                                        <p><strong>Отвечено:</strong> <?php echo $message['responded_at']; ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>У вас пока нет сообщений.</p>
                    <?php endif; ?>
                </div>
                <div class="profile-section">
                    <h3>Настройки</h3>
                    <ul>
                        <li><a href="edit_profile.php">Редактировать профиль</a></li>
                        <li><a href="change_password.php">Изменить пароль</a></li>
                        <li><a href="addresses.php">Мои адреса</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </main>
    <footer>
        <div class="footer-content">
            <a href="https://t.me/FitoDomik" class="support-button" target="_blank">Поддержка</a>
        </div>
    </footer>
</body>
</html> 