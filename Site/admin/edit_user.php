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
$errors = [];
$success_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $phone = trim($_POST['phone']);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    if (empty($username)) {
        $errors[] = "Имя пользователя обязательно";
    }
    if (empty($phone)) {
        $errors[] = "Телефон обязателен";
    }
    if (!empty($username) && $username !== $user['username']) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Этот никнейм уже занят другим пользователем";
        }
    }
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, phone = ?, is_admin = ? WHERE id = ?");
        if ($stmt->execute([$username, $phone, $is_admin, $user_id])) {
            $success_message = "Данные пользователя успешно обновлены.";
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        } else {
            $errors[] = "Ошибка при обновлении данных пользователя.";
        }
    }
}
$currentPage = 'users'; 
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ: Редактирование пользователя - <?php echo htmlspecialchars($user['username']); ?></title>
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
        <h1>Редактирование пользователя: <?php echo htmlspecialchars($user['username']); ?></h1>
        <div class="admin-section">
            <?php if (!empty($errors)): ?>
                <div class="errors">
                    <?php foreach ($errors as $error): ?>
                        <div class="error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Никнейм:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Телефон:</label>
                     <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_admin" <?php echo $user['is_admin'] ? 'checked' : ''; ?>>
                        Администратор
                    </label>
                </div>
                <button type="submit" class="btn-primary">Сохранить изменения</button>
                <a href="view_user.php?id=<?php echo $user['id']; ?>" class="btn-secondary">Отмена</a>
            </form>
        </div>
    </main>
</body>
</html> 