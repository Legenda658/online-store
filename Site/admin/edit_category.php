<?php
require_once 'init.php';
require_once '../config/database.php';
if (!isAdmin()) {
    header('Location: ../index.php');
    exit();
}
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) {
    header('Location: categories.php');
    exit();
}
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$id]);
$category = $stmt->fetch();
if (!$category) {
    header('Location: categories.php');
    exit();
}
$stmt = $pdo->query("SELECT id, name FROM categories WHERE id != $id ORDER BY name");
$categories = $stmt->fetchAll();
$errors = [];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    if (empty($name)) {
        $errors[] = 'Название категории обязательно';
    }
    if (empty($slug)) {
        $errors[] = 'Slug категории обязателен';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $id]);
        if ($stmt->fetch()) {
            $errors[] = 'Такой slug уже существует';
        }
    }
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE categories 
                SET name = ?, slug = ?, description = ?, parent_id = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$name, $slug, $description, $parent_id, $id]);
            $success = true;
            $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $category = $stmt->fetch();
        } catch (PDOException $e) {
            $errors[] = 'Ошибка при обновлении категории';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование категории - Админ-панель</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Редактирование категории</h1>
        <?php if ($success): ?>
            <div class="alert alert-success">
                Категория успешно обновлена
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
        <form method="POST" class="mt-4">
            <div class="mb-3">
                <label for="name" class="form-label">Название категории</label>
                <input type="text" class="form-control" id="name" name="name" 
                       value="<?php echo htmlspecialchars($category['name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="slug" class="form-label">Slug</label>
                <input type="text" class="form-control" id="slug" name="slug" 
                       value="<?php echo htmlspecialchars($category['slug']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Описание</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php 
                    echo htmlspecialchars($category['description']); 
                ?></textarea>
            </div>
            <div class="mb-3">
                <label for="parent_id" class="form-label">Родительская категория</label>
                <select class="form-control" id="parent_id" name="parent_id">
                    <option value="">Нет родительской категории</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" 
                            <?php echo $cat['id'] === $category['parent_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                <a href="categories.php" class="btn btn-secondary">Назад к списку категорий</a>
            </div>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 