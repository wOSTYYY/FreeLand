<?php
session_start();
include('db_connection.php');
include('functions.php');

// Проверка авторизации и роли
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role_id'] != 1) {
    header("Location: orders.php");
    exit();
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
if ($order_id == 0) {
    header("Location: orders.php");
    exit();
}

// Проверяем, что заказ принадлежит текущему заказчику
$stmt = $conn->prepare("SELECT customer_id, status FROM orders WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: orders.php");
    exit();
}

$order = $result->fetch_assoc();
$customer_id = getCustomerId($_SESSION['user_id']);

if ($order['customer_id'] != $customer_id || $order['status'] != 'Выполнен') {
    header("Location: orders.php");
    exit();
}

// Меняем статус заказа на "Закрыт"
$stmt = $conn->prepare("UPDATE orders SET status = 'Закрыт' WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();

// Перенаправляем на страницу заказа с сообщением об успехе
$_SESSION['success_message'] = 'Заказ успешно закрыт!';
header("Location: order_details.php?id=$order_id");
exit();
?>