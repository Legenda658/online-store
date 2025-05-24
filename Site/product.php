<?php
header('X-Content-Type-Options: nosniff');
require_once 'config/database.php';
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if (empty($slug)) {
    header("Location: catalog.php");
    exit();
}
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name, b.name as brand_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN brands b ON p.brand_id = b.id 
    WHERE p.slug = ? AND p.is_active = 1
");
$stmt->execute([$slug]);
$product = $stmt->fetch();
if (!$product) {
    header("Location: catalog.php");
    exit();
}
$stmt_images = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order");
$stmt_images->execute([$product['id']]);
$images = $stmt_images->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - AnaLeg</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
                <a href="cart.php">Корзина</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="profile.php">Профиль</a>
                    <a href="auth/logout.php">Выйти</a>
                <?php else: ?>
                    <a href="auth/login.php">Войти</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>
    <main>
        <div class="product-container">
            <div class="product-gallery">
                <?php if (!empty($images)): ?>
                    <div class="main-image">
                        <img src="<?php echo htmlspecialchars($images[0]['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" id="main-product-image">
                    </div>
                    <?php if (count($images) > 1): ?>
                        <div class="thumbnail-images">
                            <?php foreach ($images as $image): ?>
                                <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     onclick="changeMainImage(this.src)"
                                     class="thumbnail">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="main-image">
                        <img src="assets/images/no-image.jpg" alt="Нет изображения">
                    </div>
                <?php endif; ?>
            </div>
            <div class="product-info">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <div class="product-meta">
                    <?php if ($product['category_name']): ?>
                        <p>Категория: <a href="catalog.php?category=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a></p>
                    <?php endif; ?>
                    <?php if ($product['brand_name']): ?>
                        <p>Бренд: <a href="catalog.php?brand=<?php echo $product['brand_id']; ?>"><?php echo htmlspecialchars($product['brand_name']); ?></a></p>
                    <?php endif; ?>
                </div>
                <div class="product-price">
                    <?php if ($product['old_price']): ?>
                        <span class="old-price"><?php echo number_format($product['old_price'], 2); ?> ₽</span>
                    <?php endif; ?>
                    <span class="current-price"><?php echo number_format($product['price'], 2); ?> ₽</span>
                </div>
                <div class="product-description">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>
                <div class="product-actions">
                    <div class="quantity-selector">
                        <label for="quantity" class="visually-hidden">Количество:</label>
                        <button onclick="decreaseQuantity()">-</button>
                        <input type="number" id="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                        <button onclick="increaseQuantity()">+</button>
                    </div>
                     <!-- Форма добавления в корзину -->
                    <form action="add_to_cart.php" method="post" class="add-to-cart-form">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" name="quantity" id="form_quantity" value="1">
                        <button type="submit" class="btn-primary add-to-cart">Добавить в корзину</button>
                    </form>
                </div>
                <div class="stock-info">
                    <?php if ($product['stock'] > 0): ?>
                        <p class="in-stock">В наличии: <?php echo $product['stock']; ?> шт.</p>
                    <?php else: ?>
                        <p class="out-of-stock">Нет в наличии</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
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
        function changeMainImage(src) {
            document.getElementById('main-product-image').src = src;
        }
        const quantityInput = document.getElementById('quantity');
        const formQuantityInput = document.getElementById('form_quantity');
        function updateFormQuantity() {
            formQuantityInput.value = quantityInput.value;
        }
        function decreaseQuantity() {
            if (quantityInput.value > 1) {
                quantityInput.value = parseInt(quantityInput.value) - 1;
                updateFormQuantity();
            }
        }
        function increaseQuantity() {
            const max = parseInt(quantityInput.getAttribute('max'));
            if (quantityInput.value < max) {
                quantityInput.value = parseInt(quantityInput.value) + 1;
                updateFormQuantity();
            }
        }
        quantityInput.addEventListener('input', updateFormQuantity);
    </script>
</body>
</html> 