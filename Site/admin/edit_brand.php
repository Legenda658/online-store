<?php
require_once 'init.php';
require_once '../config/database.php';
if (!isAdmin()) {
    header('Location: ../index.php');
    exit();
}
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) {
    header('Location: brands.php');
    exit();
}
$stmt = $pdo->prepare("SELECT * FROM brands WHERE id = ?");
$stmt->execute([$id]);
$brand = $stmt->fetch();
if (!$brand) {
    header('Location: brands.php');
    exit();
}
$errors = [];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $logo = $brand['logo']; 
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxFileSize = 5 * 1024 * 1024; 
        if (!in_array($_FILES['logo']['type'], $allowedTypes)) {
            $errors[] = 'Недопустимый формат файла. Разрешены только JPG, PNG и GIF';
        } elseif ($_FILES['logo']['size'] > $maxFileSize) {
            $errors[] = 'Размер файла превышает 5MB';
        } else {
            $uploadDir = '../assets/images/brands/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $newFileName = 'brand_' . $id . '_' . time() . '.' . $extension;
            $uploadPath = $uploadDir . $newFileName;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadPath)) {
                if ($brand['logo'] && file_exists('../' . $brand['logo'])) {
                    unlink('../' . $brand['logo']);
                }
                $logo = 'assets/images/brands/' . $newFileName;
            } else {
                $errors[] = 'Ошибка при загрузке файла';
            }
        }
    }
    if (empty($name)) {
        $errors[] = 'Название бренда обязательно';
    }
    if (empty($slug)) {
        $errors[] = 'Slug бренда обязателен';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM brands WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $id]);
        if ($stmt->fetch()) {
            $errors[] = 'Такой slug уже существует';
        }
    }
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE brands 
                SET name = ?, slug = ?, description = ?, logo = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$name, $slug, $description, $logo, $id]);
            $success = true;
            $stmt = $pdo->prepare("SELECT * FROM brands WHERE id = ?");
            $stmt->execute([$id]);
            $brand = $stmt->fetch();
        } catch (PDOException $e) {
            $errors[] = 'Ошибка при обновлении бренда';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование бренда - Админ-панель</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Редактирование бренда</h1>
        <?php if ($success): ?>
            <div class="alert alert-success">
                Бренд успешно обновлен
            </div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="mt-4">
            <div class="mb-3">
                <label for="name" class="form-label">Название бренда</label>
                <input type="text" class="form-control" id="name" name="name" 
                       value="<?php echo htmlspecialchars($brand['name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="slug" class="form-label">Slug</label>
                <input type="text" class="form-control" id="slug" name="slug" 
                       value="<?php echo htmlspecialchars($brand['slug']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Описание</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php 
                    echo htmlspecialchars($brand['description']); 
                ?></textarea>
            </div>
            <div class="mb-3">
                <label for="logo" class="form-label">Логотип</label>
                <?php if ($brand['logo']): ?>
                    <div class="mb-2">
                        <img src="../<?php echo htmlspecialchars($brand['logo']); ?>" 
                             alt="Текущий логотип" style="max-width: 200px; max-height: 200px;">
                    </div>
                <?php endif; ?>
                <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                <div class="form-text">Оставьте пустым, чтобы сохранить текущий логотип. Максимальный размер файла: 5MB</div>
            </div>
            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                <a href="brands.php" class="btn btn-secondary">Назад к списку брендов</a>
            </div>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 