<?php
session_start();
include('db_connection.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

if ($order_id == 0) {
    header("Location: orders.php");
    exit();
}

// Проверяем, что исполнитель назначен на заказ
$stmt = $conn->prepare("
    SELECT o.freelancer_id, o.status 
    FROM orders o
    JOIN freelancers f ON o.freelancer_id = f.freelancer_id
    WHERE o.order_id = ? AND f.user_id = ?
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: order_details.php?id=$order_id&error=unauthorized");
    exit();
}

$order = $result->fetch_assoc();

if ($order['status'] === 'Оплачено') {
    header("Location: order_details.php?id=$order_id");
    exit();
}

// Обновляем статус заказа
$stmt = $conn->prepare("UPDATE orders SET status = 'Оплачено' WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();

// Увеличиваем счетчик выполненных заказов у фрилансера
$updateFreelancer = $conn->prepare("
    UPDATE freelancers 
    SET completed_orders = completed_orders + 1 
    WHERE freelancer_id = ?
");
$updateFreelancer->bind_param("i", $order['freelancer_id']);
$updateFreelancer->execute();

header("Location: order_details.php?id=$order_id&payment=confirmed");
exit();
?>
