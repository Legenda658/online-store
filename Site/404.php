<?php
header('X-Content-Type-Options: nosniff');
require_once 'init.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Страница не найдена | AnaLeg</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="AnaLeg.ico">
    <style>
        .error-container {
            text-align: center;
            padding: 50px 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        .error-code {
            font-size: 120px;
            font-weight: bold;
            color: #e74c3c;
            margin: 0;
            line-height: 1;
        }
        .error-message {
            font-size: 24px;
            color: #2c3e50;
            margin: 20px 0;
        }
        .error-description {
            color: #7f8c8d;
            margin-bottom: 30px;
        }
        .home-button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .home-button:hover {
            background-color: #2980b9;
        }
    </style>
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
                    <a href="profile.php">Профиль</a>
                    <a href="cart.php">Корзина</a>
                    <a href="auth/logout.php">Выйти</a>
                <?php else: ?>
                    <a href="auth/login.php">Войти</a>
                    <a href="auth/register.php">Регистрация</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>
    <main>
        <div class="error-container">
            <h1 class="error-code">404</h1>
            <h2 class="error-message">Страница не найдена</h2>
            <p class="error-description">
                К сожалению, запрашиваемая страница не существует или была перемещена.
            </p>
            <a href="index.php" class="home-button">Вернуться на главную</a>
        </div>
    </main>
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>О компании</h3>
                <p>AnaLeg - ваш надежный партнер в мире моды и стиля.</p>
            </div>
            <div class="footer-section">
                <h3>Контакты</h3>
                <p>Email: info@analeg.ru</p>
                <p>Телефон: +7 (999) 123-45-67</p>
            </div>
            <div class="footer-section">
                <h3>Мы в соцсетях</h3>
                <div class="social-links">
                    <a href="#">VK</a>
                    <a href="#">Telegram</a>
                    <a href="#">WhatsApp</a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> AnaLeg. Все права защищены.</p>
        </div>
    </footer>
</body>
</html> 