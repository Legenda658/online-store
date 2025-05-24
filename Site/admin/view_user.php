<?php
header('X-Content-Type-Options: nosniff');
require_once 'init.php';
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../auth/login.php");
    exit();
}
$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    $_SESSION['error_message'] = "ID пользователя не указан.";
    header("Location: users.php");
    exit();
}
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) {
    $_SESSION['error_message'] = "Пользователь с таким ID не найден.";
    header("Location: users.php");
    exit();
}
$currentPage = 'users'; 
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ: Просмотр пользователя - <?php echo htmlspecialchars($user['username']); ?></title>
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
        <h1>Просмотр пользователя: <?php echo htmlspecialchars($user['username']); ?></h1>
        <div class="admin-section">
            <p><strong>ID:</strong> <?php echo $user['id']; ?></p>
            <p><strong>Никнейм:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
            <p><strong>Телефон:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
            <p><strong>Админ:</strong> <?php echo $user['is_admin'] ? 'Да' : 'Нет'; ?></p>
            <p><strong>Дата регистрации:</strong> <?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></p>
            <div class="action-links">
                <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn-primary">Редактировать</a>
                <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="btn-secondary" onclick="return confirm('Вы уверены, что хотите удалить этого пользователя?');">Удалить</a>
                <a href="users.php" class="btn-secondary">Вернуться к списку пользователей</a>
            </div>
        </div>
    </main>
</body>
</html> 