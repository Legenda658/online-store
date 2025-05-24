<?php
header('X-Content-Type-Options: nosniff');
session_start();
require_once '../config/database.php';
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me_token'])) {
    $token = $_COOKIE['remember_me_token'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];
        header("Location: ../index.php");
        exit();
    }
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $remember_me = isset($_POST['remember_me']);
    $errors = [];
    if (empty($username)) {
        $errors[] = "Никнейм обязателен";
    }
    if (empty($password)) {
        $errors[] = "Пароль обязателен";
    }
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];
            if ($remember_me) {
                $token = bin2hex(random_bytes(32));
                $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                $stmt->execute([$token, $user['id']]);
                setcookie('remember_me_token', $token, time() + (86400 * 30), "/");
            } else {
                $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
                $stmt->execute([$user['id']]);
                setcookie('remember_me_token', '', time() - 3600, "/");
            }
            header("Location: ../index.php");
            exit();
        } else {
            $errors[] = "Неверный никнейм или пароль";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" href="../AnaLeg.ico">
</head>
<body>
    <div class="auth-container">
        <h2>Вход</h2>
        <?php if (!empty($errors)): ?>
            <div class="errors">
                <?php foreach ($errors as $error): ?>
                    <p class="error"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success">
                <?php 
                echo htmlspecialchars($_SESSION['success']);
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Никнейм:</label>
                <input type="text" id="username" name="username" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <input type="checkbox" id="remember_me" name="remember_me">
                <label for="remember_me">Запомнить меня</label>
            </div>
            <button type="submit" class="btn-primary">Войти</button>
        </form>
        <p class="auth-link">Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
        <p class="auth-link"><a href="forgot_password.php">Забыли пароль?</a></p>
    </div>
</body>
</html> 