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
$errors = [];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    if (empty($status)) {
        $errors[] = 'Выберите статус заказа';
    }
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET status = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$status, $order_id]);
            if ($status !== $order['status']) {
                $stmt = $pdo->prepare("
                    INSERT INTO order_history (
                        order_id, old_status, new_status, comment, changed_by, created_at
                    ) VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $order_id,
                    $order['status'],
                    $status,
                    $comment,
                    $_SESSION['user_id']
                ]);
            }
            $pdo->commit();
            $success = true;
            $stmt = $pdo->prepare("
                SELECT o.*, u.username, u.phone
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE o.id = ?
            ");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Произошла ошибка при обновлении заказа';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование заказа #<?php echo $order_id; ?> - AnaLeg</title>
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
            <h1>Редактирование заказа #<?php echo $order_id; ?></h1>
            <a href="view_order.php?id=<?php echo $order_id; ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Назад к просмотру
            </a>
        </div>
        <?php if ($success): ?>
            <div class="alert alert-success">
                Заказ успешно обновлен
            </div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Изменение статуса заказа</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="status" class="form-label">Статус заказа</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="new" <?php echo $order['status'] === 'new' ? 'selected' : ''; ?>>Новый</option>
                                    <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>В обработке</option>
                                    <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Отправлен</option>
                                    <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Доставлен</option>
                                    <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Отменен</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="comment" class="form-label">Комментарий к изменению</label>
                                <textarea class="form-control" id="comment" name="comment" rows="3" 
                                          placeholder="Укажите причину изменения статуса или дополнительную информацию"></textarea>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Сохранить изменения
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Информация о заказе</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="text-muted small">Клиент:</div>
                            <div><?php echo htmlspecialchars($order['username']); ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Телефон:</div>
                            <div><?php echo htmlspecialchars($order['phone']); ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Адрес доставки:</div>
                            <div><?php echo htmlspecialchars($order['shipping_address']); ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Способ оплаты:</div>
                            <div>
                                <?php echo $order['payment_method'] === 'cash' ? 'Наличными при получении' : 'Банковской картой при получении'; ?>
                            </div>
                        </div>
                        <div>
                            <div class="text-muted small">Сумма заказа:</div>
                            <div class="fw-bold"><?php echo number_format($order['total_amount'], 2); ?> ₽</div>
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