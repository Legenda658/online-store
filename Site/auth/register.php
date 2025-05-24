<?php
session_start();
require_once '../config/database.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $phone = '+7' . preg_replace('/\D/', '', $_POST['phone']);
    $password = trim($_POST['password']);
    $password_confirm = trim($_POST['password_confirm']);
    $errors = [];
    if (empty($username)) {
        $errors[] = "Никнейм обязателен";
    } elseif (strlen($username) < 3) {
        $errors[] = "Никнейм должен содержать минимум 3 символа";
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Этот никнейм уже занят";
        }
    }
    if (empty($phone) || $phone === '+7') {
        $errors[] = "Номер телефона обязателен";
    } elseif (!preg_match('/^\+7[0-9]{10}$/', $phone)) {
        $errors[] = "Неверный формат номера телефона. Используйте формат: +7XXXXXXXXXX (10 цифр после +7)";
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Этот номер телефона уже зарегистрирован";
        }
    }
    if (empty($password)) {
        $errors[] = "Пароль обязателен";
    } elseif (strlen($password) < 6) {
        $errors[] = "Пароль должен содержать минимум 6 символов";
    }
    if ($password !== $password_confirm) {
        $errors[] = "Пароли не совпадают";
    }
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, phone, password, created_at) VALUES (?, ?, ?, NOW())");
        if ($stmt->execute([$username, $phone, $hashed_password])) {
            $_SESSION['success'] = "Регистрация успешна! Теперь вы можете войти.";
            header("Location: login.php");
            exit();
        } else {
            $errors[] = "Ошибка при регистрации. Пожалуйста, попробуйте позже.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <h2>Регистрация</h2>
        <?php if (!empty($errors)): ?>
            <div class="errors">
                <?php foreach ($errors as $error): ?>
                    <p class="error"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Никнейм:</label>
                <input type="text" id="username" name="username" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Номер телефона:</label>
                <div class="phone-input-container">
                    <span class="phone-prefix">+7</span>
                    <input type="tel" 
                           id="phone" 
                           name="phone" 
                           class="phone-input" 
                           placeholder="(XXX) XXX-XX-XX" 
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                           required>
                </div>
                <span class="phone-format-hint">Формат: +7 (XXX) XXX-XX-XX</span>
            </div>
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="password_confirm">Подтвердите пароль:</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
            </div>
            <button type="submit" class="btn-primary">Зарегистрироваться</button>
        </form>
        <p class="auth-link">Уже есть аккаунт? <a href="login.php">Войти</a></p>
    </div>
    <script>
        const phoneInput = document.getElementById('phone');
        phoneInput.addEventListener('input', function(e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/);
            let formattedNumber = '';
            if (x[1]) {
                formattedNumber = '(' + x[1];
                if (x[2]) formattedNumber += ') ' + x[2];
                if (x[3]) formattedNumber += '-' + x[3];
                if (x[4]) formattedNumber += '-' + x[4];
            }
            e.target.value = formattedNumber;
        });
        document.querySelector('form').addEventListener('submit', function(e) {
            const phoneDigits = phoneInput.value.replace(/\D/g, '');
            if (phoneDigits.length !== 10) {
                e.preventDefault();
                phoneInput.classList.add('error');
                alert('Пожалуйста, введите полный номер телефона (10 цифр после +7)');
            } else {
                phoneInput.classList.remove('error');
            }
        });
        phoneInput.addEventListener('focus', function() {
            this.classList.remove('error');
        });
    </script>
</body>
</html> 