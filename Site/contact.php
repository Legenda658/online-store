<?php
header('X-Content-Type-Options: nosniff');
require_once 'config/database.php';
$success = '';
$error = '';
$is_logged_in = isset($_SESSION['user_id']);
if ($is_logged_in && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $message = trim($_POST['message']);
    $user_id = $_SESSION['user_id'];
    if (empty($message)) {
        $error = "Пожалуйста, введите ваше сообщение";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO feedback_messages (user_id, message, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$user_id, $message]);
            $success = "Спасибо за ваше сообщение! Мы свяжемся с вами в ближайшее время.";
        } catch (PDOException $e) {
            $error = "Ошибка при сохранении сообщения: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Контакты - AnaLeg</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="AnaLeg.ico">
    <script src="https://api-maps.yandex.ru/2.1/?apikey=YOUR_API_KEY&lang=ru_RU" type="text/javascript"></script>
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
                <li><a href="contact.php" class="active">Контакты</a></li>
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
        <div class="contact-container">
            <h1>Контакты</h1>
            <div class="contact-grid">
                <div class="contact-info">
                    <h2>Наши контакты</h2>
                    <div class="info-item">
                        <h3>Адрес</h3>
                        <p>проспект Будённого, 15/2</p>
                    </div>
                    <div class="info-item">
                        <h3>Режим работы</h3>
                        <p>Пн-Пт: 9:00 - 20:00</p>
                        <p>Сб-Вс: выходной</p>
                    </div>
                    <!-- Телефон и Email удалены по запросу -->
                </div>
                <div class="contact-form">
                    <h2>Напишите нам</h2>
                    <?php if ($error): ?>
                        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    <?php if ($is_logged_in): ?>
                        <form action="contact.php" method="post">
                            <div class="form-group">
                                <label for="message">Сообщение:</label>
                                <textarea id="message" name="message" rows="5" required></textarea>
                            </div>
                            <button type="submit" class="btn-primary">Отправить сообщение</button>
                        </form>
                    <?php else: ?>
                        <div class="info-message">
                            <p>Для отправки сообщения, пожалуйста, <a href="auth/login.php">войдите</a> или <a href="auth/register.php">зарегистрируйтесь</a>.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="map-container">
                <h2>Как нас найти</h2>
                <div id="map" style="width: 100%; height: 400px;"></div>
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
        <div class="footer-bottom">
    </footer>
    <script type="text/javascript">
        ymaps.ready(function () {
            var myMap = new ymaps.Map('map', {
                center: [55.7756, 37.7239], 
                zoom: 16 
            });
            var myPlacemark = new ymaps.Placemark([55.7756, 37.7239], {
                hintContent: 'AnaLeg',
                balloonContent: 'Наш офис'
            });
            myMap.geoObjects.add(myPlacemark);
        });
    </script>
</body>
</html> 