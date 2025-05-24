<?php
header('X-Content-Type-Options: nosniff');
require_once 'init.php';
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    $_SESSION['error'] = "Некорректный ID товара.";
    header("Location: products.php");
    exit();
}
$errors = [];
$success = '';
$product = null;
$images = [];
$stmt_product = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt_product->execute([$product_id]);
$product = $stmt_product->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    $_SESSION['error'] = "Товар с указанным ID не найден.";
    header("Location: products.php");
    exit();
}
$stmt_images = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order");
$stmt_images->execute([$product_id]);
$images = $stmt_images->fetchAll(PDO::FETCH_ASSOC);
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
    $existing_images_to_keep = isset($_POST['existing_images']) ? $_POST['existing_images'] : []; 
    $main_image_id = isset($_POST['main_image']) ? (int)$_POST['main_image'] : null; 
    if (empty($name)) {
        $errors[] = "Название товара обязательно";
    }
    if (empty($slug)) {
        $errors[] = "Slug товара обязателен";
    } else {
        $stmt_check_slug = $pdo->prepare("SELECT COUNT(*) FROM products WHERE slug = ? AND id != ?");
        $stmt_check_slug->execute([$slug, $product_id]);
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
    if (isset($_FILES['new_images'])) {
        $files = $_FILES['new_images'];
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
                            $uploaded_images[] = ['path' => 'assets/images/products/' . $new_file_name]; 
                        } else {
                            $errors[] = "Ошибка при загрузке файла " . htmlspecialchars($file_name) . ".";
                        }
                    } else {
                        $errors[] = "Размер файла " . htmlspecialchars($file_name) . " превышает 10MB.";
                    }
                }
            } elseif ($file_error !== 4) { 
                 $errors[] = "Ошибка при загрузке файла " . htmlspecialchars($file_name) . ": " . $file_error;
            }
        }
    }
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $stmt_update_product = $pdo->prepare("UPDATE products SET name = ?, slug = ?, description = ?, price = ?, old_price = ?, category_id = ?, brand_id = ?, stock = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
            $stmt_update_product->execute([$name, $slug, $description, $price, $old_price, $category_id, $brand_id, $stock, $is_active, $product_id]);
            if (!empty($images)) {
                 $image_ids_to_delete = array_diff(array_column($images, 'id'), $existing_images_to_keep);
                 if (!empty($image_ids_to_delete)) {
                     $placeholders = implode(',', array_fill(0, count($image_ids_to_delete), '?'));
                     $stmt_delete_images = $pdo->prepare("DELETE FROM product_images WHERE id IN (" . $placeholders . ") AND product_id = ?");
                     $stmt_delete_images->execute(array_merge($image_ids_to_delete, [$product_id]));
                     $stmt_deleted_paths = $pdo->prepare("SELECT image_path FROM product_images WHERE id IN (" . $placeholders . ")"); 
                      $stmt_deleted_paths->execute($image_ids_to_delete); 
                     foreach($stmt_deleted_paths->fetchAll(PDO::FETCH_COLUMN) as $deleted_path) {
                         if (file_exists('../' . $deleted_path)) {
                             unlink('../' . $deleted_path);
                         }
                     }
                 }
            }
            if (!empty($uploaded_images)) {
                $stmt_insert_image = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_main, sort_order, created_at) VALUES (?, ?, ?, ?, NOW())");
                 $max_sort_order = 0;
                 if (!empty($images)) {
                     $max_sort_order = max(array_column($images, 'sort_order'));
                 }
                foreach ($uploaded_images as $key => $image_info) {
                    $is_main = 0;
                    if ($main_image_id === null && $key === 0) {
                         $is_main = 1;
                     }
                    $sort_order = $max_sort_order + $key + 1;
                    $stmt_insert_image->execute([$product_id, $image_info['path'], $is_main, $sort_order]);
                     if ($is_main) {
                         $main_image_id = $pdo->lastInsertId();
                     }
                }
            }
             $stmt_reset_main = $pdo->prepare("UPDATE product_images SET is_main = 0 WHERE product_id = ?");
             $stmt_reset_main->execute([$product_id]);
             if ($main_image_id) {
                  $stmt_set_main = $pdo->prepare("UPDATE product_images SET is_main = 1 WHERE id = ? AND product_id = ?");
                  $stmt_set_main->execute([$main_image_id, $product_id]);
             } else {
                 $stmt_first_image = $pdo->prepare("SELECT id FROM product_images WHERE product_id = ? ORDER BY sort_order LIMIT 1");
                 $stmt_first_image->execute([$product_id]);
                 $first_image = $stmt_first_image->fetch(PDO::FETCH_ASSOC);
                 if ($first_image) {
                     $stmt_set_main_fallback = $pdo->prepare("UPDATE product_images SET is_main = 1 WHERE id = ?");
                     $stmt_set_main_fallback->execute([$first_image['id']]);
                 }
             }
            $pdo->commit();
            $_SESSION['success'] = "Товар успешно обновлен.";
            header("Location: products.php"); 
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Ошибка базы данных: " . $e->getMessage();
            foreach ($uploaded_images as $image_info) {
                 if (file_exists('../' . $image_info['path'])) {
                     unlink('../' . $image_info['path']);
                 }
            }
        }
    }
}
$stmt_product = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt_product->execute([$product_id]);
$product = $stmt_product->fetch(PDO::FETCH_ASSOC);
$stmt_images = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order");
$stmt_images->execute([$product_id]);
$images = $stmt_images->fetchAll(PDO::FETCH_ASSOC);
if ($_SERVER['REQUEST_METHOD'] != 'POST' || !empty($errors)) {
    $stmt_categories = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);
    $stmt_brands = $pdo->query("SELECT id, name FROM brands ORDER BY name");
    $brands = $stmt_brands->fetchAll(PDO::FETCH_ASSOC);
}
$currentPage = 'products'; 
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ: Редактировать товар #<?php echo $product['id']; ?></title>
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
                if (slugInput.value === '' || slugInput._generated) {
                     slugInput.value = generateSlug(this.value);
                     slugInput._generated = true; 
                }
            });
            slugInput.addEventListener('input', function() {
                slugInput._generated = false;
            });
            slugInput.addEventListener('focus', function() {
                if (this.value === '' && nameInput.value !== '') {
                    this.value = generateSlug(nameInput.value);
                    this._generated = true;
                }
            });
             document.querySelectorAll('.existing-image .delete-image').forEach(button => {
                 button.addEventListener('click', function() {
                     this.closest('.existing-image').remove();
                 });
             });
              document.querySelectorAll('.existing-image input[type="radio"]').forEach(radio => {
                  radio.addEventListener('change', function() {
                  });
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
                 <li><a href="feedback.php" class="<?php echo ($currentPage == 'feedback') ? 'active' : ''; ?>">Обратная связь</a></li>
            </ul>
            <div class="nav-right">
                <a href="../index.php">На сайт</a>
                <a href="../auth/logout.php">Выйти</a>
            </div>
        </nav>
    </header>
    <main>
        <h1>Редактировать товар #<?php echo $product['id']; ?></h1>
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
            <form action="edit_product.php?id=<?php echo $product['id']; ?>" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Название товара:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="slug">Slug (ЧПУ):</label>
                    <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($product['slug'] ?? ''); ?>" required>
                    <small class="form-text">Пример: если название "Новый товар 2024", то slug будет "novyy-tovar-2024"</small>
                </div>
                <div class="form-group">
                    <label for="description">Описание:</label>
                    <textarea id="description" name="description"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="price">Цена:</label>
                    <input type="number" id="price" name="price" step="0.01" value="<?php echo htmlspecialchars($product['price'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="old_price">Старая цена (опционально):</label>
                    <input type="number" id="old_price" name="old_price" step="0.01" value="<?php echo htmlspecialchars($product['old_price'] ?? ''); ?>">
                </div>
                 <div class="form-group">
                    <label for="category_id">Категория:</label>
                    <select id="category_id" name="category_id">
                        <option value="">-- Выберите категорию --</option>
                         <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo (isset($product['category_id']) && $product['category_id'] == $cat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="brand_id">Бренд:</label>
                    <select id="brand_id" name="brand_id">
                        <option value="">-- Выберите бренд --</option>
                        <?php foreach ($brands as $br): ?>
                            <option value="<?php echo $br['id']; ?>" <?php echo (isset($product['brand_id']) && $product['brand_id'] == $br['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($br['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 <div class="form-group">
                    <label for="stock">Количество на складе:</label>
                    <input type="number" id="stock" name="stock" value="<?php echo htmlspecialchars($product['stock'] ?? ''); ?>" required>
                </div>
                 <div class="form-group">
                    <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo (isset($product['is_active']) && $product['is_active'] == 1) ? 'checked' : ''; ?>>
                    <label for="is_active">Товар активен</label>
                </div>
                <div class="form-group">
                    <label>Существующие изображения:</label>
                    <div class="existing-images">
                         <?php if (!empty($images)): ?>
                            <?php foreach ($images as $image): ?>
                                <div class="existing-image">
                                     <img src="../<?php echo htmlspecialchars($image['image_path']); ?>" alt="Изображение товара" width="100">
                                    <input type="hidden" name="existing_images[]" value="<?php echo $image['id']; ?>"> <!-- Скрытое поле для сохранения ID изображения -->
                                    <label>
                                         <input type="radio" name="main_image" value="<?php echo $image['id']; ?>" <?php echo $image['is_main'] ? 'checked' : ''; ?>> Основное
                                    </label>
                                    <button type="button" class="delete-image">Удалить</button> <!-- Кнопка для удаления -->
                                </div>
                            <?php endforeach; ?>
                         <?php else: ?>
                             <p>Нет загруженных изображений.</p>
                         <?php endif; ?>
                    </div>
                </div>
                 <div class="form-group">
                    <label for="new_images">Загрузить новые изображения:</label>
                    <input type="file" id="new_images" name="new_images[]" accept="image/*" multiple>
                     <small class="form-text">Можно выбрать несколько файлов.</small>
                </div>
                <button type="submit" class="btn-primary" style="width: auto;">Сохранить изменения</button>
            </form>
        </div>
    </main>
</body>
</html> 