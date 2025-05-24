<?php
header('X-Content-Type-Options: nosniff');
require_once 'config/database.php';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
$brand_id = isset($_GET['brand']) ? (int)$_GET['brand'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$stmt_categories = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt_categories->fetchAll();
$stmt_brands = $pdo->query("SELECT * FROM brands ORDER BY name");
$brands = $stmt_brands->fetchAll();
$sql = "SELECT p.*, c.name as category_name, b.name as brand_name, pi.image_path as main_image 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN brands b ON p.brand_id = b.id 
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1 
        WHERE p.is_active = 1";
$params = [];
if ($category_id) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category_id;
}
if ($brand_id) {
    $sql .= " AND p.brand_id = ?";
    $params[] = $brand_id;
}
if ($search) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог товаров - AnaLeg</title>
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
                <li><a href="catalog.php" class="active">Каталог</a></li>
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
        <div class="catalog-container">
            <aside class="filters">
                <h2>Фильтры</h2>
                <form action="catalog.php" method="get" class="filter-form">
                    <div class="form-group">
                        <label for="search">Поиск:</label>
                        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <label for="category">Категория:</label>
                        <select id="category" name="category">
                            <option value="">Все категории</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="brand">Бренд:</label>
                        <select id="brand" name="brand">
                            <option value="">Все бренды</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo $brand['id']; ?>" <?php echo $brand_id == $brand['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($brand['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary">Применить фильтры</button>
                </form>
            </aside>
            <div class="products-grid">
                <?php if (empty($products)): ?>
                    <p class="no-products">Товары не найдены</p>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <?php if ($product['main_image']): ?>
                                <img src="<?php echo htmlspecialchars($product['main_image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php else: ?>
                                <img src="assets/images/no-image.jpg" alt="Нет изображения">
                            <?php endif; ?>
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p>
                                <?php if ($product['old_price']): ?>
                                    <span class="old-price"><?php echo number_format($product['old_price'], 2); ?> ₽</span>
                                <?php endif; ?>
                                <span class="current-price"><?php echo number_format($product['price'], 2); ?> ₽</span>
                            </p>
                            <div class="product-actions">
                                <a href="product.php?slug=<?php echo htmlspecialchars($product['slug']); ?>" class="btn-primary">Подробнее</a>
                                <!-- Форма добавления в корзину -->
                                <form action="add_to_cart.php" method="post" class="add-to-cart-form" style="display: inline-block;">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="hidden" name="quantity" value="1"> <!-- По умолчанию добавляем 1 шт -->
                                    <button type="submit" class="btn-primary">В корзину</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
                            alert(data.message);
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