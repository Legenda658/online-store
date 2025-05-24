<?php
session_start();
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $errors = [];
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!password_verify($current_password, $user['password'])) {
        $errors[] = "Неверный текущий пароль";
    }
    if (strlen($new_password) < 6) {
        $errors[] = "Новый пароль должен содержать минимум 6 символов";
    }
    if ($new_password !== $confirm_password) {
        $errors[] = "Пароли не совпадают";
    }
    if (empty($errors)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
            $_SESSION['success_message'] = "Пароль успешно изменен";
            header("Location: profile.php");
            exit();
        } else {
            $errors[] = "Ошибка при изменении пароля";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Изменение пароля</title>
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
            <h2>Изменение пароля</h2>
            <?php if (!empty($errors)): ?>
                <div class="errors">
                    <?php foreach ($errors as $error): ?>
                        <div class="error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="current_password">Текущий пароль</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Новый пароль</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Подтвердите новый пароль</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn-primary">Изменить пароль</button>
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