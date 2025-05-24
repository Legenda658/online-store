<?php
header('X-Content-Type-Options: nosniff');
require_once 'init.php';
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../auth/login.php');
    exit();
}
$message = null;
$feedback_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_response'])) {
    $admin_response = trim($_POST['admin_response']);
    try {
        $stmt = $pdo->prepare("UPDATE feedback_messages SET admin_response = ?, responded_at = NOW() WHERE id = ?");
        $stmt->execute([$admin_response, $feedback_id]);
        $success = "Ответ администратора сохранен.";
        header("Location: view_feedback.php?id=" . $feedback_id);
        exit();
    } catch (PDOException $e) {
        $error = "Ошибка при сохранении ответа: " . $e->getMessage();
    }
}
if ($feedback_id > 0) {
    $stmt = $pdo->prepare("SELECT fm.*, u.username FROM feedback_messages fm JOIN users u ON fm.user_id = u.id WHERE fm.id = ?");
    $stmt->execute([$feedback_id]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
}
if (!$message) {
    header('Location: feedback.php');
    exit();
}
$currentPage = 'feedback'; 
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотр сообщения #<?php echo $message['id']; ?> - Админ-панель Farm429</title>
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
        <div class="admin-container">
            <h1>Просмотр сообщения #<?php echo $message['id']; ?></h1>
            <?php if ($error): ?>
                <div class="errors">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success">
                    <p><?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>
            <div class="feedback-details">
                <p><strong>ID Сообщения:</strong> <?php echo $message['id']; ?></p>
                <p><strong>Пользователь:</strong> <?php echo htmlspecialchars($message['username']); ?></p>
                <p><strong>Дата создания:</strong> <?php echo $message['created_at']; ?></p>
                <p><strong>Сообщение:</strong></p>
                <div class="message-text"><?php echo nl2br(htmlspecialchars($message['message'])); ?></div>
            </div>
            <h2 style="margin-top: 20px;">Ответ администратора</h2>
            <form action="view_feedback.php?id=<?php echo $message['id']; ?>" method="post">
                <div class="form-group">
                    <label for="admin_response">Ваш ответ:</label>
                    <textarea id="admin_response" name="admin_response" rows="5" required><?php echo htmlspecialchars($message['admin_response'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="btn-primary">Сохранить ответ</button>
            </form>
            <?php if ($message['admin_response']): ?>
                <div class="admin-response-info" style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee;">
                    <p><strong>Последний ответ отправлен:</strong> <?php echo $message['responded_at']; ?></p>
                </div>
            <?php endif; ?>
            <p style="margin-top: 20px;"><a href="feedback.php">&larr; Вернуться к списку сообщений</a></p>
        </div>
    </main>
    <footer>
        <!-- Футер админки -->
    </footer>
</body>
</html> 