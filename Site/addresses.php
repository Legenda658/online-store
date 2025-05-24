<?php
header('X-Content-Type-Options: nosniff');
session_start();
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}
// Получаем список адресов пользователя
$stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC");
$stmt->execute([$_SESSION['user_id']]);
$addresses = $stmt->fetchAll();
// Обработка добавления нового адреса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $address = trim($_POST['address']);
        $city = trim($_POST['city']);
        $postal_code = trim($_POST['postal_code']);
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        $errors = [];
        if (empty($address)) {
            $errors[] = "Адрес обязателен";
        }
        if (empty($city)) {
            $errors[] = "Город обязателен";
        }
        if (empty($postal_code)) {
            $errors[] = "Почтовый индекс обязателен";
        }
        if (empty($errors)) {
            if ($is_default) {
                $stmt = $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
            }
            $stmt = $pdo->prepare("INSERT INTO addresses (user_id, address, city, postal_code, is_default) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$_SESSION['user_id'], $address, $city, $postal_code, $is_default])) {
                $_SESSION['success_message'] = "Адрес успешно добавлен";
                header("Location: addresses.php");
                exit();
            } else {
                $errors[] = "Ошибка при добавлении адреса";
            }
        }
    } elseif ($_POST['action'] === 'delete' && isset($_POST['address_id'])) {
        $stmt = $pdo->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$_POST['address_id'], $_SESSION['user_id']])) {
            $_SESSION['success_message'] = "Адрес успешно удален";
            header("Location: addresses.php");
            exit();
        }
    } elseif ($_POST['action'] === 'set_default' && isset($_POST['address_id'])) {
        $stmt = $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $stmt = $pdo->prepare("UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$_POST['address_id'], $_SESSION['user_id']])) {
            $_SESSION['success_message'] = "Адрес по умолчанию обновлен";
            header("Location: addresses.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление адресами</title>
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
            <h2>Мои адреса</h2>
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success">
                    <?php 
                    echo htmlspecialchars($_SESSION['success_message']);
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="errors">
                    <?php foreach ($errors as $error): ?>
                        <div class="error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="addresses-list">
                <?php if ($addresses): ?>
                    <?php foreach ($addresses as $address): ?>
                        <div class="address-item">
                            <p><strong>Адрес:</strong> <?php echo htmlspecialchars($address['address']); ?></p>
                            <p><strong>Город:</strong> <?php echo htmlspecialchars($address['city']); ?></p>
                            <p><strong>Индекс:</strong> <?php echo htmlspecialchars($address['postal_code']); ?></p>
                            <?php if ($address['is_default']): ?>
                                <p class="default-badge">Адрес по умолчанию</p>
                            <?php endif; ?>
                            <div class="address-actions">
                                <?php if (!$address['is_default']): ?>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="set_default">
                                        <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                        <button type="submit" class="btn-secondary">Сделать адресом по умолчанию</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                    <button type="submit" class="btn-secondary" onclick="return confirm('Вы уверены, что хотите удалить этот адрес?')">Удалить</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>У вас пока нет сохраненных адресов</p>
                <?php endif; ?>
            </div>
            <h3>Добавить новый адрес</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="address">Адрес</label>
                    <input type="text" id="address" name="address" required>
                </div>
                <div class="form-group">
                    <label for="city">Город</label>
                    <input type="text" id="city" name="city" required>
                </div>
                <div class="form-group">
                    <label for="postal_code">Почтовый индекс</label>
                    <input type="text" id="postal_code" name="postal_code" required>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_default">
                        Сделать адресом по умолчанию
                    </label>
                </div>
                <button type="submit" class="btn-primary">Добавить адрес</button>
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