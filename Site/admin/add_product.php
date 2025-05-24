<?php
header('X-Content-Type-Options: nosniff');
require_once 'init.php';
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../auth/login.php");
    exit();
}
$errors = [];
$success = '';
$stmt_categories = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);
$stmt_brands = $pdo->query("SELECT id, name FROM brands ORDER BY name");
$brands = $stmt_brands->fetchAll(PDO::FETCH_ASSOC);
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $slug = trim($_POST['slug']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $old_price = trim($_POST['old_price']) !== '' ? trim($_POST['old_price']) : NULL;
    $category_id = trim($_POST['category_id']) !== '' ? trim($_POST['category_id']) : NULL;
    $brand_id = trim($_POST['brand_id']) !== '' ? trim($_POST['brand_id']) : NULL;
    $stock = trim($_POST['stock']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    if (empty($name)) {
        $errors[] = "Название товара обязательно";
    }
    if (empty($slug)) {
        $errors[] = "Slug товара обязателен";
    } else {
        $stmt_check_slug = $pdo->prepare("SELECT COUNT(*) FROM products WHERE slug = ?");
        $stmt_check_slug->execute([$slug]);
        if ($stmt_check_slug->fetchColumn() > 0) {
            $errors[] = "Slug '$slug' уже существует.";
        }
    }
    if (empty($price) || !is_numeric($price) || $price < 0) {
        $errors[] = "Укажите корректную цену товара";
    }
     if (!empty($old_price) && (!is_numeric($old_price) || $old_price < 0)) {
        $errors[] = "Укажите корректную старую цену товара";
    }
    if (empty($stock) || !is_numeric($stock) || $stock < 0) {
        $errors[] = "Укажите корректное количество товара на складе";
    }
    $uploaded_images = [];
    if (isset($_FILES['images'])) {
        $files = $_FILES['images'];
        $upload_dir = '../assets/images/products/';
        $allowed_ext = array('jpg', 'jpeg', 'png', 'gif');
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        foreach ($files['tmp_name'] as $key => $tmp_name) {
            $file_name = $files['name'][$key];
            $file_tmp_name = $files['tmp_name'][$key];
            $file_error = $files['error'][$key];
            $file_size = $files['size'][$key];
            if ($file_error === 0) {
                 $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                if (in_array($file_ext, $allowed_ext)) {
                    if ($file_size < 10000000) { 
                        $new_file_name = uniqid('', true) . '.' . $file_ext;
                        $file_destination = $upload_dir . $new_file_name;
                        if (move_uploaded_file($file_tmp_name, $file_destination)) {
                            $uploaded_images[] = 'assets/images/products/' . $new_file_name; 
                        } else {
                            $errors[] = "Ошибка при загрузке файла " . htmlspecialchars($file_name) . ".";
                        }
                    } else {
                        $errors[] = "Размер файла " . htmlspecialchars($file_name) . " превышает 5MB.";
                    }
                } else {
                    $errors[] = "Недопустимый тип файла " . htmlspecialchars($file_name) . ". Разрешены только JPG, JPEG, PNG, GIF.";
                }
            } elseif ($file_error !== 4) { 
                 $errors[] = "Ошибка при загрузке файла " . htmlspecialchars($file_name) . ": " . $file_error;
            }
        }
    }
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $stmt_product = $pdo->prepare("INSERT INTO products (name, slug, description, price, old_price, category_id, brand_id, stock, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt_product->execute([$name, $slug, $description, $price, $old_price, $category_id, $brand_id, $stock, $is_active]);
            $product_id = $pdo->lastInsertId();
            if (!empty($uploaded_images)) {
                $stmt_image = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_main, sort_order, created_at) VALUES (?, ?, ?, ?, NOW())");
                foreach ($uploaded_images as $key => $image_path) {
                    $is_main = ($key === 0) ? 1 : 0; 
                    $sort_order = $key;
                    $stmt_image->execute([$product_id, $image_path, $is_main, $sort_order]);
                }
            }
            $pdo->commit();
            $_SESSION['success'] = "Товар успешно добавлен.";
            header("Location: products.php"); 
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Ошибка базы данных: " . $e->getMessage();
            foreach ($uploaded_images as $image_path) {
                 if (file_exists('../' . $image_path)) {
                     unlink('../' . $image_path);
                 }
            }
        }
    }
}
?>
<?php $currentPage = 'products'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить товар</title>
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
        <h1>Добавить новый товар</h1>
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
            <form action="add_product.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Название товара:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="slug">Slug (ЧПУ):</label>
                    <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($slug ?? ''); ?>" required>
                    <small class="form-text">Пример: если название "Новый товар 2024", то slug будет "novyy-tovar-2024"</small>
                </div>
                <div class="form-group">
                    <label for="description">Описание:</label>
                    <textarea id="description" name="description"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="price">Цена:</label>
                    <input type="number" id="price" name="price" step="0.01" value="<?php echo htmlspecialchars($price ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="old_price">Старая цена (опционально):</label>
                    <input type="number" id="old_price" name="old_price" step="0.01" value="<?php echo htmlspecialchars($old_price ?? ''); ?>">
                </div>
                 <div class="form-group">
                    <label for="category_id">Категория:</label>
                    <select id="category_id" name="category_id">
                        <option value="">-- Выберите категорию --</option>
                         <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo (isset($category_id) && $category_id == $cat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="brand_id">Бренд:</label>
                    <select id="brand_id" name="brand_id">
                        <option value="">-- Выберите бренд --</option>
                        <?php foreach ($brands as $br): ?>
                            <option value="<?php echo $br['id']; ?>" <?php echo (isset($brand_id) && $brand_id == $br['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($br['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 <div class="form-group">
                    <label for="stock">Количество на складе:</label>
                    <input type="number" id="stock" name="stock" value="<?php echo htmlspecialchars($stock ?? ''); ?>" required>
                </div>
                 <div class="form-group">
                    <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo (isset($is_active) && $is_active == 1) ? 'checked' : ''; ?>>
                    <label for="is_active">Товар активен</label>
                </div>
                 <div class="form-group">
                    <label for="images">Изображения товара (можно выбрать несколько):</label>
                    <input type="file" id="images" name="images[]" accept="image/*" multiple>
                </div>
                <button type="submit" class="btn-primary" style="width: auto;">Добавить товар</button>
            </form>
        </div>
    </main>
</body>
</html> 