<?php
header('X-Content-Type-Options: nosniff');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';
if (isset($_POST['remove_item']) && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    $user_id = $_SESSION['user_id'] ?? null;
    if ($user_id) {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
    }
    header('Location: cart.php');
    exit();
}
if (isset($_POST['update_quantity']) && isset($_POST['product_id']) && isset($_POST['quantity'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = max(1, (int)$_POST['quantity']);
    $user_id = $_SESSION['user_id'] ?? null;
    if ($user_id) {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$quantity, $user_id, $product_id]);
    }
    header('Location: cart.php');
    exit();
}
$cart_items = [];
$total_amount = 0;
$user_id = $_SESSION['user_id'] ?? null;
$addresses = [];
if ($user_id) {
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
    foreach ($cart_items as $item) {
        $total_amount += $item['quantity'] * $item['price'];
    }
    $stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC");
    $stmt->execute([$user_id]);
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина</title>
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
        <h1>Ваша корзина</h1>
        <?php if ($user_id === null): ?>
            <div class="alert alert-info">
                <p>Для просмотра корзины, пожалуйста, <a href="auth/login.php">войдите</a> или <a href="auth/register.php">зарегистрируйтесь</a>.</p>
            </div>
        <?php elseif (empty($cart_items)): ?>
            <div class="alert alert-info">
                <p>Ваша корзина пуста.</p>
                <p><a href="catalog.php" class="btn btn-primary">Перейти в каталог</a>, чтобы добавить товары.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Изображение</th>
                            <th>Название товара</th>
                            <th>Цена за шт.</th>
                            <th>Количество</th>
                            <th>Сумма</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td>
                                    <?php if ($item['main_image']): ?>
                                        <img src="<?php echo htmlspecialchars($item['main_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                             style="max-width: 100px; height: auto;">
                                    <?php else: ?>
                                        <span class="text-muted">Нет изображения</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo number_format($item['price'], 2); ?> ₽</td>
                                <td>
                                    <form method="POST" class="d-flex align-items-center" style="max-width: 150px;">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                               min="1" max="<?php echo $item['stock']; ?>" 
                                               class="form-control form-control-sm me-2">
                                        <button type="submit" name="update_quantity" class="btn btn-sm btn-outline-primary">
                                            ✓
                                        </button>
                                    </form>
                                </td>
                                <td><?php echo number_format($item['quantity'] * $item['price'], 2); ?> ₽</td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <button type="submit" name="remove_item" class="btn btn-sm btn-danger" 
                                                onclick="return confirm('Вы уверены, что хотите удалить этот товар из корзины?')">
                                            Удалить
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Адрес доставки</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($addresses)): ?>
                                <p class="text-muted">У вас пока нет сохраненных адресов.</p>
                                <a href="addresses.php" class="btn btn-primary">Добавить адрес</a>
                            <?php else: ?>
                                <form method="POST" action="checkout.php">
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
                                    <div class="mt-3">
                                        <a href="addresses.php" class="btn btn-outline-primary">Управление адресами</a>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Итого</h5>
                        </div>
                        <div class="card-body">
                            <h3 class="mb-3"><?php echo number_format($total_amount, 2); ?> ₽</h3>
                            <?php if (!empty($addresses)): ?>
                                <form method="POST" action="checkout.php">
                                    <input type="hidden" name="total_amount" value="<?php echo $total_amount; ?>">
                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                        Оформить заказ
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    Для оформления заказа необходимо добавить адрес доставки
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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