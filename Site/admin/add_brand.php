<?php
header('X-Content-Type-Options: nosniff');
require_once 'init.php';
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../auth/login.php");
    exit();
}
$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $slug = trim($_POST['slug']);
    $description = trim($_POST['description']);
    $logo_path = NULL;
    if (empty($name)) {
        $errors[] = "Название бренда обязательно";
    }
    if (empty($slug)) {
        $errors[] = "Slug бренда обязателен";
    } else {
        $stmt_check_slug = $pdo->prepare("SELECT COUNT(*) FROM brands WHERE slug = ?");
        $stmt_check_slug->execute([$slug]);
        if ($stmt_check_slug->fetchColumn() > 0) {
            $errors[] = "Slug '$slug' уже существует.";
        }
    }
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        $file = $_FILES['logo'];
        $upload_dir = '../assets/images/brands/';
        $allowed_ext = array('jpg', 'jpeg', 'png', 'gif');
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_ext)) {
            $errors[] = "Недопустимый тип файла для логотипа. Разрешены только JPG, JPEG, PNG, GIF.";
        } elseif ($file['size'] > 10000000) { 
            $errors[] = "Размер файла логотипа превышает 10MB.";
        } else {
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $new_file_name = uniqid('', true) . '.' . $file_ext;
            $file_destination = $upload_dir . $new_file_name;
            if (move_uploaded_file($file['tmp_name'], $file_destination)) {
                $logo_path = 'assets/images/brands/' . $new_file_name; 
            } else {
                $errors[] = "Ошибка при загрузке файла логотипа.";
            }
        }
    }
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO brands (name, slug, description, logo, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $slug, $description, $logo_path]);
            $success = "Бренд успешно добавлен.";
             $name = $slug = $description = '';
             $logo_path = NULL;
        } catch (PDOException $e) {
            $errors[] = "Ошибка базы данных: " . $e->getMessage();
        }
    }
}
?>
<?php $currentPage = 'brands'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ: Добавить бренд</title>
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
        <h1>Добавить новый бренд</h1>
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
            <form action="add_brand.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Название бренда:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="slug">Slug (ЧПУ):</label>
                    <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($slug ?? ''); ?>" required>
                    <small class="form-text">Пример: если название "Новый бренд", то slug будет "novyy-brend"</small>
                </div>
                <div class="form-group">
                    <label for="description">Описание:</label>
                    <textarea id="description" name="description"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                </div>
                 <div class="form-group">
                    <label for="logo">Логотип (опционально):</label>
                    <input type="file" id="logo" name="logo" accept="image/*">
                </div>
                <button type="submit" class="btn-primary" style="width: auto;">Добавить бренд</button>
            </form>
        </div>
    </main>
</body>
</html> 