<?php
header('X-Content-Type-Options: nosniff');
require_once 'init.php';
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../auth/login.php");
    exit();
}
$errors = [];
$success = '';
$stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $slug = trim($_POST['slug']);
    $description = trim($_POST['description']);
    $parent_id = $_POST['parent_id'] !== '' ? $_POST['parent_id'] : NULL;
    if (empty($name)) {
        $errors[] = "Название категории обязательно";
    }
    if (empty($slug)) {
        $errors[] = "Slug категории обязателен";
    } else {
        $stmt_check_slug = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE slug = ?");
        $stmt_check_slug->execute([$slug]);
        if ($stmt_check_slug->fetchColumn() > 0) {
            $errors[] = "Slug '$slug' уже существует.";
        }
    }
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, parent_id, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $slug, $description, $parent_id]);
            $success = "Категория успешно добавлена.";
             $name = $slug = $description = '';
             $parent_id = NULL;
        } catch (PDOException $e) {
            $errors[] = "Ошибка базы данных: " . $e->getMessage();
        }
    }
}
?>
<?php $currentPage = 'categories'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить категорию</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" href="../AnaLeg.ico">
    <script>
        function generateSlug(text) {
            return text
                .toString()
                .toLowerCase()
                .replace(/\s+/g, '-')
                .replace(/[^\w\-]+/g, '')
                .replace(/\-\-+/g, '-')
                .replace(/^-+/, '')
                .replace(/-+$/, '');
        }
        document.addEventListener('DOMContentLoaded', function() {
            const nameInput = document.getElementById('name');
            const slugInput = document.getElementById('slug');
            nameInput.addEventListener('input', function() {
                if (slugInput.value === '') {
                    slugInput.value = generateSlug(this.value);
                }
            });
            slugInput.addEventListener('focus', function() {
                if (this.value === '') {
                    this.value = generateSlug(nameInput.value);
                }
            });
        });
    </script>
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
            </ul>
            <div class="nav-right">
                <a href="../index.php">На сайт</a>
                <a href="../auth/logout.php">Выйти</a>
            </div>
        </nav>
    </header>
    <main>
        <h1>Добавить новую категорию</h1>
        <div class="admin-section">
            <?php if (!empty($errors)): ?>
                <div class="errors">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success">
                    <p><?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>
            <form action="add_category.php" method="post">
                <div class="form-group">
                    <label for="name">Название категории:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="slug">Slug (ЧПУ):</label>
                    <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($slug ?? ''); ?>" required>
                    <small class="form-text">Пример: если название "Новая категория", то slug будет "novaya-kategoriya"</small>
                </div>
                <div class="form-group">
                    <label for="description">Описание:</label>
                    <textarea id="description" name="description"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="parent_id">Родительская категория (опционально):</label>
                    <select id="parent_id" name="parent_id">
                        <option value="">-- Выберите родительскую категорию --</option>
                        <?php foreach ($categories as $pc): ?>
                            <option value="<?php echo $pc['id']; ?>" <?php echo (isset($parent_id) && $parent_id == $pc['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($pc['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-primary" style="width: auto;">Добавить категорию</button>
            </form>
        </div>
    </main>
</body>
</html> 