<?php
session_start();
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $phone = trim($_POST['phone']);
    $errors = [];
    if (empty($username)) {
        $errors[] = "Имя пользователя обязательно";
    }
    if (empty($phone)) {
        $errors[] = "Телефон обязателен";
    }
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, phone = ? WHERE id = ?");
        if ($stmt->execute([$username, $phone, $_SESSION['user_id']])) {
            $_SESSION['success_message'] = "Профиль успешно обновлен";
            header("Location: profile.php");
            exit();
        } else {
            $errors[] = "Ошибка при обновлении профиля";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование профиля</title>
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
            </ul>
            <div class="nav-right">
                <a href="cart.php" class="cart-link">Корзина</a>
                <a href="auth/logout.php" class="logout-link">Выйти</a>
            </div>
        </nav>
    </header>
    <main>
        <div class="auth-container">
            <h2>Редактирование профиля</h2>
            <?php if (!empty($errors)): ?>
                <div class="errors">
                    <?php foreach ($errors as $error): ?>
                        <div class="error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Имя пользователя</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Телефон</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                </div>
                <button type="submit" class="btn-primary">Сохранить изменения</button>
            </form>
            <div class="auth-link">
                <a href="profile.php">Вернуться в профиль</a>
            </div>
        </div>
    </main>
    <footer>
        <div class="footer-content">
            <a href="https://t.me/FitoDomik" class="support-button" target="_blank">Поддержка</a>
        </div>
    </footer>
</body>
</html> 