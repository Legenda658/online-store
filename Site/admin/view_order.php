<?php
header('X-Content-Type-Options: nosniff');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../auth/login.php');
    exit();
}
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id === 0) {
    header('Location: index.php');
    exit();
}
$stmt = $pdo->prepare("
    SELECT o.*, u.username, u.phone
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();
if (!$order) {
    header('Location: index.php');
    exit();
}
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.slug, pi.image_path as main_image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();
$stmt = $pdo->prepare("
    SELECT oh.*, u.username as changed_by_username
    FROM order_history oh
    JOIN users u ON oh.changed_by = u.id
    WHERE oh.order_id = ?
    ORDER BY oh.created_at DESC
");
$stmt->execute([$order_id]);
$order_history = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотр заказа #<?php echo $order_id; ?> - AnaLeg</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" href="../AnaLeg.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <header>
        <nav class="main-nav">
            <div class="logo">
                <a href="../index.php">AnaLeg</a>
            </div>
            <ul class="nav-links">
                <li><a href="../catalog.php">Каталог</a></li>
                <li><a href="../about.php">О нас</a></li>
                <li><a href="../contact.php">Контакты</a></li>
            </ul>
            <div class="nav-right">
                <a href="index.php">Админ панель</a>
                <a href="../auth/logout.php">Выйти</a>
            </div>
        </nav>
    </header>
    <main class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Заказ #<?php echo $order_id; ?></h1>
            <a href="edit_order.php?id=<?php echo $order_id; ?>" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Редактировать
            </a>
        </div>
        <div class="row">
            <div class="col-md-8">
                <!-- Информация о заказе -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Информация о заказе</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Статус:</div>
                            <div class="col-sm-8">
                                <span class="badge bg-<?php 
                                    echo match($order['status']) {
                                        'new' => 'primary',
                                        'processing' => 'info',
                                        'shipped' => 'warning',
                                        'delivered' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?php echo match($order['status']) {
                                        'new' => 'Новый',
                                        'processing' => 'В обработке',
                                        'shipped' => 'Отправлен',
                                        'delivered' => 'Доставлен',
                                        'cancelled' => 'Отменен',
                                        default => $order['status']
                                    }; ?>
                                </span>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Дата заказа:</div>
                            <div class="col-sm-8"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Способ оплаты:</div>
                            <div class="col-sm-8">
                                <?php echo $order['payment_method'] === 'cash' ? 'Наличными при получении' : 'Банковской картой при получении'; ?>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Сумма заказа:</div>
                            <div class="col-sm-8">
                                <strong><?php echo number_format($order['total_amount'], 2); ?> ₽</strong>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Информация о клиенте -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Информация о клиенте</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Имя пользователя:</div>
                            <div class="col-sm-8"><?php echo htmlspecialchars($order['username']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Телефон:</div>
                            <div class="col-sm-8"><?php echo htmlspecialchars($order['phone']); ?></div>
                        </div>
                        <div class="row">
                            <div class="col-sm-4 text-muted">Адрес доставки:</div>
                            <div class="col-sm-8"><?php echo htmlspecialchars($order['shipping_address']); ?></div>
                        </div>
                    </div>
                </div>
                <!-- Товары в заказе -->
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
                                                <?php if ($item['main_image']): ?>
                                                    <img src="<?php echo htmlspecialchars($item['main_image']); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                         style="max-width: 50px; height: auto; margin-right: 10px;">
                                                <?php endif; ?>
                                                <a href="../product.php?slug=<?php echo htmlspecialchars($item['slug']); ?>">
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
            </div>
            <div class="col-md-4">
                <!-- История заказа -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">История заказа</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <?php foreach ($order_history as $history): ?>
                                <div class="timeline-item mb-3">
                                    <div class="timeline-date text-muted small">
                                        <?php echo date('d.m.Y H:i', strtotime($history['created_at'])); ?>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="mb-1">
                                            <span class="badge bg-<?php 
                                                echo match($history['new_status']) {
                                                    'new' => 'primary',
                                                    'processing' => 'info',
                                                    'shipped' => 'warning',
                                                    'delivered' => 'success',
                                                    'cancelled' => 'danger',
                                                    default => 'secondary'
                                                };
                                            ?>">
                                                <?php echo match($history['new_status']) {
                                                    'new' => 'Новый',
                                                    'processing' => 'В обработке',
                                                    'shipped' => 'Отправлен',
                                                    'delivered' => 'Доставлен',
                                                    'cancelled' => 'Отменен',
                                                    default => $history['new_status']
                                                }; ?>
                                            </span>
                                        </div>
                                        <?php if ($history['comment']): ?>
                                            <div class="text-muted small">
                                                <?php echo htmlspecialchars($history['comment']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="text-muted small">
                                            Изменено: <?php echo htmlspecialchars($history['changed_by_username']); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
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