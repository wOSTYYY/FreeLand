<?php
session_start();
include('db_connection.php');

// Проверка авторизации и прав
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $order_id = (int)$_POST['order_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $budget = (float)$_POST['budget'];
    $deadline = $_POST['deadline'];
    
    // Проверяем, принадлежит ли заказ текущему пользователю
    $stmt = $conn->prepare("SELECT customer_id FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Заказ не найден";
        header("Location: orders.php");
        exit();
    }
    
    $order = $result->fetch_assoc();
    $customer_id = $order['customer_id'];
    
    // Проверяем, является ли пользователь владельцем заказа
    $stmt = $conn->prepare("SELECT user_id FROM customers WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0 || $result->fetch_assoc()['user_id'] != $_SESSION['user_id']) {
        $_SESSION['error'] = "У вас нет прав для редактирования этого заказа";
        header("Location: orders.php");
        exit();
    }
    
    // Обновляем заказ
    $stmt = $conn->prepare("UPDATE orders SET 
                          title = ?, 
                          description = ?, 
                          category = ?, 
                          budget = ?, 
                          deadline = ?
                          WHERE order_id = ?");
    $stmt->bind_param("sssdsi", $title, $description, $category, $budget, $deadline, $order_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Заказ успешно обновлен!";
    } else {
        $_SESSION['error'] = "Ошибка при обновлении заказа: " . $conn->error;
    }
    
    header("Location: order_details.php?id=".$order_id);
    exit();
}

header("Location: orders.php");
exit();
?>