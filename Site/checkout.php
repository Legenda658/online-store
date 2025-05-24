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
$user_id = $_SESSION['user_id'];
$errors = [];
$success = false;
$stmt = $pdo->prepare("
    SELECT
        c.product_id,
        c.quantity,
        p.name,
        p.price,
        p.stock,
        pi.image_path as main_image
    FROM cart c
    JOIN products p ON c.product_id = p.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($cart_items)) {
    header('Location: cart.php');
    exit();
}
$total_amount = 0;
foreach ($cart_items as $item) {
    $total_amount += $item['quantity'] * $item['price'];
}
$stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($addresses)) {
    header('Location: addresses.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address_id = isset($_POST['address_id']) ? (int)$_POST['address_id'] : 0;
    $payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : '';
    if ($address_id === 0) {
        $errors[] = 'Выберите адрес доставки';
    }
    if (empty($payment_method)) {
        $errors[] = 'Выберите способ оплаты';
    }
    foreach ($cart_items as $item) {
        if ($item['quantity'] > $item['stock']) {
            $errors[] = "Товар '{$item['name']}' доступен только в количестве {$item['stock']} шт.";
        }
    }
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, status, total_amount, shipping_address, payment_method, created_at)
                VALUES (?, 'new', ?, ?, ?, NOW())
            ");
            $stmt_address = $pdo->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ?");
            $stmt_address->execute([$address_id, $user_id]);
            $address = $stmt_address->fetch();
            if (!$address) {
                throw new Exception('Адрес доставки не найден');
            }
            $shipping_address = "{$address['address']}, {$address['city']}, {$address['postal_code']}";
            $stmt->execute([$user_id, $total_amount, $shipping_address, $payment_method]);
            $order_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            foreach ($cart_items as $item) {
                $stmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price']
                ]);
                $stmt_update = $pdo->prepare("
                    UPDATE products 
                    SET stock = stock - ? 
                    WHERE id = ?
                ");
                $stmt_update->execute([$item['quantity'], $item['product_id']]);
            }
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $pdo->commit();
            $success = true;
            header("Location: order_success.php?id=" . $order_id);
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Произошла ошибка при оформлении заказа. Пожалуйста, попробуйте позже.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформление заказа</title>
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
        <h1>Оформление заказа</h1>
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
                                    <?php foreach ($cart_items as $item): ?>
                                        <tr>
                                            <td>
                                                <?php if ($item['main_image']): ?>
                                                    <img src="<?php echo htmlspecialchars($item['main_image']); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                         style="max-width: 50px; height: auto;">
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($item['name']); ?>
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
                <form method="POST" class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Данные для доставки</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h6>Выберите адрес доставки:</h6>
                            <?php foreach ($addresses as $address): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="address_id" 
                                           id="address_<?php echo $address['id']; ?>" 
                                           value="<?php echo $address['id']; ?>"
                                           <?php echo $address['is_default'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="address_<?php echo $address['id']; ?>">
                                        <?php echo htmlspecialchars($address['address']); ?>, 
                                        <?php echo htmlspecialchars($address['city']); ?>, 
                                        <?php echo htmlspecialchars($address['postal_code']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                            <div class="mt-2">
                                <a href="addresses.php" class="btn btn-outline-primary btn-sm">Управление адресами</a>
                            </div>
                        </div>
                        <div class="mb-4">
                            <h6>Способ оплаты:</h6>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="payment_method" 
                                       id="payment_cash" value="cash" checked>
                                <label class="form-check-label" for="payment_cash">
                                    Наличными при получении
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="payment_method" 
                                       id="payment_card" value="card">
                                <label class="form-check-label" for="payment_card">
                                    Банковской картой при получении
                                </label>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                Подтвердить заказ
                            </button>
                            <a href="cart.php" class="btn btn-outline-secondary">
                                Вернуться в корзину
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Итого</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Товары (<?php echo count($cart_items); ?>):</span>
                            <span><?php echo number_format($total_amount, 2); ?> ₽</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Доставка:</span>
                            <span>Бесплатно</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Итого к оплате:</strong>
                            <strong><?php echo number_format($total_amount, 2); ?> ₽</strong>
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