<?php
session_start();
include('db_connection.php');
include('functions.php');

// Проверка авторизации и роли
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login.php");
    exit();
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
if ($order_id == 0) {
    header("Location: orders.php");
    exit();
}

// Проверяем, что заказ принадлежит текущему фрилансеру
$stmt = $conn->prepare("SELECT freelancer_id FROM orders WHERE order_id = ? AND status = 'Выполнено'");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: orders.php");
    exit();
}

$order = $result->fetch_assoc();
$freelancer_id = getFreelancerId($_SESSION['user_id']);

if ($order['freelancer_id'] != $freelancer_id) {
    header("Location: orders.php");
    exit();
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Создаем папку для заказа, если ее нет
    $upload_dir = "uploads/order_$order_id/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Обработка загруженных файлов
    if (!empty($_FILES['additional_files']['name'][0])) {
        foreach ($_FILES['additional_files']['name'] as $key => $name) {
            if ($_FILES['additional_files']['error'][$key] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['additional_files']['tmp_name'][$key];
                $file_name = basename($name);
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($tmp_name, $file_path)) {
                    // Сохраняем информацию о файле в БД
                    $stmt = $conn->prepare("INSERT INTO order_files (order_id, freelancer_id, file_name, file_path) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiss", $order_id, $freelancer_id, $file_name, $file_path);
                    $stmt->execute();
                }
            }
        }
    }

    // Сохраняем дополнительный комментарий
    $comment = $_POST['additional_comment'] ?? '';
    if (!empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO order_comments (order_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $order_id, $_SESSION['user_id'], $comment);
        $stmt->execute();
    }

    $_SESSION['success_message'] = 'Ответ по заказу успешно обновлен!';
    header("Location: order_details.php?id=$order_id");
    exit();
}