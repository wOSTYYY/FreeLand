<?php
session_start();
include('../pages/db_connection.php');

if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

$order_id = (int)$_GET['order_id'];

// Проверяем, является ли пользователь владельцем заказа
$stmt = $conn->prepare("SELECT o.customer_id, c.user_id 
                       FROM orders o
                       JOIN customers c ON o.customer_id = c.customer_id
                       WHERE o.order_id = ? AND c.user_id = ?");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

// Получаем отклики
$stmt = $conn->prepare("
    SELECT r.*, 
           u.firstname, u.lastname, u.profile_photo, f.specialization, f.completed_orders
    FROM order_responses r
    JOIN users u ON r.freelancer_id = u.user_id
    JOIN freelancers f ON u.user_id = f.user_id
    WHERE r.order_id = ?
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$responses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode($responses);