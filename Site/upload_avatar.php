<?php
session_start();
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$upload_dir = 'uploads/avatars/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    $file_name = $file['name'];
    $file_tmp_name = $file['tmp_name'];
    $file_error = $file['error'];
    $file_size = $file['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_ext = array('jpg', 'jpeg', 'png', 'gif');
    if ($file_error === 0) {
        if (in_array($file_ext, $allowed_ext)) {
            if ($file_size < 5000000) { 
                $new_file_name = uniqid('', true) . '.' . $file_ext;
                $file_destination = $upload_dir . $new_file_name;
                if (move_uploaded_file($file_tmp_name, $file_destination)) {
                    $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                    if ($stmt->execute([$file_destination, $user_id])) {
                        $_SESSION['success'] = "Фото профиля успешно загружено!";
                        header("Location: profile.php");
                        exit();
                    } else {
                        $errors[] = "Ошибка при сохранении пути к фото в базе данных.";
                    }
                } else {
                    $errors[] = "Ошибка при загрузке файла.";
                }
            } else {
                $errors[] = "Размер файла превышает 5MB.";
            }
        } else {
            $errors[] = "Недопустимый тип файла. Разрешены только JPG, JPEG, PNG, GIF.";
        }
    } else {
        $errors[] = "Ошибка при загрузке файла: " . $file_error;
    }
} else {
    $errors[] = "Файл не был загружен.";
}
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header("Location: profile.php");
    exit();
}
?> 