<?php
session_start();
include('../db_connection.php');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo "Доступ запрещен";
    exit();
}

$offer_id = isset($_POST['offer_id']) ? (int)$_POST['offer_id'] : 0;
$customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;

if ($offer_id === 0 || $customer_id === 0) {
    http_response_code(400);
    echo "Неверные данные";
    exit();
}

// Проверка прав: пользователь должен быть фрилансером, которому принадлежит предложение
$user_id = $_SESSION['user_id'];

// Проверяем, что предложение принадлежит текущему фрилансеру
$stmt = $conn->prepare("
    SELECT o.freelancer_id 
    FROM freelancer_offers o
    JOIN freelancers f ON o.freelancer_id = f.freelancer_id
    WHERE o.offer_id = ? AND f.user_id = ?
");
$stmt->bind_param("ii", $offer_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(403);
    echo "Нет прав на отказ от этого предложения";
    exit();
}

// Обновляем статус предложения на "отклонено"
$stmt = $conn->prepare("UPDATE freelancer_offers SET status = 'отклонено' WHERE offer_id = ?");
$stmt->bind_param("i", $offer_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo "OK";
} else {
    http_response_code(500);
    echo "Ошибка при обновлении статуса";
}
