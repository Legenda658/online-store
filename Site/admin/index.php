<?php
header('X-Content-Type-Options: nosniff');
require_once 'init.php';
$stats = [
    'products' => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'categories' => $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
    'brands' => $pdo->query("SELECT COUNT(*) FROM brands")->fetchColumn(),
    'orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()
];
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../auth/login.php");
    exit();
}
?>
<?php $currentPage = 'index'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
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
        <h1>Добро пожаловать в Админ панель, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        <p>Здесь вы можете управлять всем содержимым вашего магазина.</p>
        <div class="admin-dashboard">
            <div class="dashboard-card">
                <h3><a href="products.php">Управление товарами</a></h3>
                <p>Добавление, редактирование и удаление товаров.</p>
            </div>
            <div class="dashboard-card">
                 <h3><a href="categories.php">Управление категориями</a></h3>
                <p>Создание и редактирование категорий товаров.</p>
            </div>
            <div class="dashboard-card">
                <h3><a href="orders.php">Управление заказами</a></h3>
                <p>Просмотр и обработка заказов.</p>
            </div>
             <div class="dashboard-card">
                <h3><a href="users.php">Управление пользователями</a></h3>
                <p>Просмотр и управление зарегистрированными пользователями.</p>
            </div>
        </div>
    </main>
</body>
</html> 