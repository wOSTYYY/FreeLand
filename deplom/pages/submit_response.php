<?php
session_start();
include('db_connection.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    $_SESSION['error'] = "Только фрилансеры могут оставлять отклики";
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $order_id = (int)$_POST['order_id'];
    $freelancer_id = (int)$_SESSION['user_id'];
    $proposal = trim($_POST['proposal']);
    $price = (float)$_POST['price'];
    $timeline = (int)$_POST['timeline'];
    
    // Валидация данных
    if (empty($proposal) || $price <= 0 || $timeline <= 0) {
        $_SESSION['error'] = "Пожалуйста, заполните все поля корректно";
        header("Location: order_details.php?id=".$order_id);
        exit();
    }
    
    // Проверяем, существует ли заказ
    $stmt = $conn->prepare("SELECT order_id FROM orders WHERE order_id = ? AND status = 'Ожидает исполнителя'");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $_SESSION['error'] = "Заказ не найден или уже принят в работу";
        header("Location: order_details.php?id=".$order_id);
        exit();
    }
    
    // Проверяем, не оставлял ли уже фрилансер отклик
    $stmt = $conn->prepare("SELECT response_id FROM order_responses 
                           WHERE order_id = ? AND freelancer_id = ?");
    $stmt->bind_param("ii", $order_id, $freelancer_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        $_SESSION['error'] = "Вы уже оставляли отклик на этот заказ";
        header("Location: order_details.php?id=".$order_id);
        exit();
    }
    
    // Добавляем отклик
    $stmt = $conn->prepare("INSERT INTO order_responses 
                          (order_id, freelancer_id, proposal, price, timeline, status, created_at) 
                          VALUES (?, ?, ?, ?, ?, 'В ожидании', NOW())");
    $stmt->bind_param("iisdi", $order_id, $freelancer_id, $proposal, $price, $timeline);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Ваш отклик успешно отправлен!";
    } else {
        $_SESSION['error'] = "Ошибка при отправке отклика: " . $conn->error;
    }
    
    header("Location: order_details.php?id=".$order_id);
    exit();
}

header("Location: orders.php");
exit();
?>