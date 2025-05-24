<?php
header('X-Content-Type-Options: nosniff');
session_start();
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>О нас - AnaLeg</title>
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
                <li><a href="about.php" class="active">О нас</a></li>
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
        <div class="about-container">
            <h1>О компании AnaLeg</h1>
            <section class="about-section">
                <h2>Наша миссия</h2>
                <p>AnaLeg — это современный магазин техники, сочетающий передовые решения и доступность. Мы стремимся сделать технологии ближе каждому.</p>
            </section>
            <section class="about-section">
                <h2>Почему выбирают нас</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <h3>Качество</h3>
                        <p>Мы сотрудничаем только с проверенными брендами и лично тестируем устройства перед продажей.</p>
                    </div>
                    <div class="feature-card">
                        <h3>Надёжность</h3>
                        <p>Гарантируем честные условия, прозрачную гарантию и быструю доставку.</p>
                    </div>
                    <div class="feature-card">
                        <h3>Поддержка</h3>
                        <p>Техническая поддержка, консультации по выбору и помощь в эксплуатации — всегда на связи.</p>
                    </div>
                    <div class="feature-card">
                        <h3>Цены</h3>
                        <p>Мы предлагаем конкурентные цены и постоянно радуем клиентов акциями и скидками.</p>
                    </div>
                </div>
            </section>
            <section class="about-section">
                <h2>Наша история</h2>
                <p>Компания AnaLeg была основана в 2024 году специалистами с опытом в электронике и ретро-технике. </p>
            </section>
            <section class="about-section">
                <h2>Наши достижения</h2>
                <ul class="achievements-list">
                    <li>Более 500 довольных клиентов</li>
                    <li>Быстрая доставка по всей России</li>
                    <li>Собственная служба поддержки и техпомощи</li>
                    <li>Регулярные новинки и уникальные устройства в ассортименте</li>
                </ul>
            </section>
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
</body>
</html> 