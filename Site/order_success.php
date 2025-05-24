<?php
header('X-Content-Type-Options: nosniff');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id === 0) {
    header('Location: index.php');
    exit();
}
$stmt = $pdo->prepare("
    SELECT o.*, u.username 
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();
if (!$order) {
    header('Location: index.php');
    exit();
}
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.slug
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказ оформлен - AnaLeg</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="AnaLeg.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <header>
        <nav class="main-nav">
            <div class="logo">
                <a href="index.php">AnaLeg</a>
            </div>
            <ul class="nav-links">
                <li><a href="catalog.php">Каталог</a></li>
                <li><a href="about.php">О нас</a></li>
                <li><a href="contact.php">Контакты</a></li>
                <li><a href="cart.php">Корзина</a></li>
            </ul>
            <div class="nav-right">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="profile.php">Привет, <?php echo htmlspecialchars($_SESSION['username']); ?></a>
                    <?php if ($_SESSION['is_admin']): ?>
                        <a href="admin/index.php">Админ панель</a>
                    <?php endif; ?>
                    <a href="auth/logout.php">Выйти</a>
                <?php else: ?>
                    <a href="auth/login.php">Войти</a>
                    <a href="auth/register.php">Регистрация</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>
    <main class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body text-center">
                        <h1 class="card-title mb-4">Спасибо за заказ!</h1>
                        <div class="mb-4">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h5 class="mb-3">Ваш заказ #<?php echo $order_id; ?> успешно оформлен</h5>
                        <p class="text-muted mb-4">
                            Мы отправили подтверждение на вашу электронную почту.<br>
                            В ближайшее время с вами свяжется наш менеджер для уточнения деталей.
                        </p>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Детали заказа</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-sm-4 text-muted">Номер заказа:</div>
                                    <div class="col-sm-8">#<?php echo $order_id; ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4 text-muted">Дата заказа:</div>
                                    <div class="col-sm-8"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4 text-muted">Статус:</div>
                                    <div class="col-sm-8">
                                        <span class="badge bg-primary">Новый</span>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4 text-muted">Способ оплаты:</div>
                                    <div class="col-sm-8">
                                        <?php echo $order['payment_method'] === 'cash' ? 'Наличными при получении' : 'Банковской картой при получении'; ?>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4 text-muted">Адрес доставки:</div>
                                    <div class="col-sm-8"><?php echo htmlspecialchars($order['shipping_address']); ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-4 text-muted">Сумма заказа:</div>
                                    <div class="col-sm-8">
                                        <strong><?php echo number_format($order['total_amount'], 2); ?> ₽</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Товары в заказе</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Товар</th>
                                                <th>Количество</th>
                                                <th>Цена</th>
                                                <th>Сумма</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($order_items as $item): ?>
                                                <tr>
                                                    <td>
                                                        <a href="product.php?slug=<?php echo htmlspecialchars($item['slug']); ?>">
                                                            <?php echo htmlspecialchars($item['name']); ?>
                                                        </a>
                                                    </td>
                                                    <td><?php echo $item['quantity']; ?></td>
                                                    <td><?php echo number_format($item['price'], 2); ?> ₽</td>
                                                    <td><?php echo number_format($item['quantity'] * $item['price'], 2); ?> ₽</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <a href="catalog.php" class="btn btn-primary">
                                Продолжить покупки
                            </a>
                            <a href="profile.php" class="btn btn-outline-secondary">
                                Перейти в личный кабинет
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <footer>
        <div class="footer-content">
            <div class="footer-social">
                <a href="#">Avito</a>
                <a href="#">Telegram</a>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 