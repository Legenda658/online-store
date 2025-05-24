<?php
header('X-Content-Type-Options: nosniff');
require_once 'config/database.php';
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me_token'])) {
    $token = $_COOKIE['remember_me_token'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];
    } else {
        setcookie('remember_me_token', '', time() - 3600, "/");
    }
}
$stmt = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name");
$categories = $stmt->fetchAll();
$stmt = $pdo->query("
    SELECT p.*, c.name as category_name, b.name as brand_name, 
           (SELECT image_path FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN brands b ON p.brand_id = b.id
    WHERE p.is_active = 1
    ORDER BY p.created_at DESC
    LIMIT 8
");
$popular_products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Интернет-магазин</title>
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
                <li><a href="about.php">О нас</a></li>
                <li><a href="contact.php">Контакты</a></li>
            </ul>
            <div class="nav-right">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="cart.php" class="cart-link">Корзина</a>
                    <a href="profile.php" class="profile-link">Профиль</a>
                    <a href="auth/logout.php" class="logout-link">Выйти</a>
                <?php else: ?>
                    <a href="auth/login.php" class="login-link">Войти</a>
                    <a href="auth/register.php" class="register-link">Регистрация</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>
    <main>
        <section class="categories">
            <h2>Категории</h2>
            <div class="category-grid">
                <?php foreach ($categories as $category): ?>
                    <a href="catalog.php?category=<?php echo htmlspecialchars($category['slug']); ?>" class="category-card">
                        <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <section class="popular-products">
            <h2>Популярные товары</h2>
            <div class="product-grid">
                <?php foreach ($popular_products as $product): ?>
                    <div class="product-card">
                        <a href="product.php?slug=<?php echo htmlspecialchars($product['slug']); ?>">
                            <?php if ($product['main_image']): ?>
                                <img src="<?php echo htmlspecialchars($product['main_image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php else: ?>
                                <img src="assets/images/no-image.jpg" alt="Нет изображения">
                            <?php endif; ?>
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="price"><?php echo number_format($product['price'], 2); ?> ₽</p>
                            <?php if ($product['old_price']): ?>
                                <p class="old-price"><?php echo number_format($product['old_price'], 2); ?> ₽</p>
                            <?php endif; ?>
                        </a>
                        <!-- Форма добавления в корзину -->
                        <form action="add_to_cart.php" method="post" class="add-to-cart-form" style="display: inline-block; width: 100%;">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="quantity" value="1"> <!-- По умолчанию добавляем 1 шт -->
                            <button type="submit" class="btn-primary">В корзину</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Мы в соцсетях</h3>
                <div class="social-links">
                    <a href="https://www.avito.ru/user/e67de56d37cbed90589142d5361b54b2/profile/all/audio_i_video?src=sharing&sellerId=e67de56d37cbed90589142d5361b54b2" target="_blank">Авито</a>
                    <a href="https://t.me/FitoDomik" target="_blank">Telegram</a>
                </div>
            </div>
        </div>
    </footer>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addToCartForms = document.querySelectorAll('.add-to-cart-form');
            addToCartForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    fetch('add_to_cart.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const cartCount = document.querySelector('.cart-count');
                            if (cartCount) {
                                cartCount.textContent = data.cart_count;
                            }
                            alert('Товар добавлен в корзину');
                        } else {
                            alert(data.message || 'Ошибка при добавлении товара в корзину');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Произошла ошибка при добавлении товара в корзину');
                    });
                });
            });
        });
    </script>
</body>
</html> 