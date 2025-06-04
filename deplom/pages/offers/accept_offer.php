<?php
session_start();
include('../db_connection.php');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit();
}

$user_id = $_SESSION['user_id'];
$offer_id = $_POST['offer_id'] ?? null;
$customer_id = $_POST['customer_id'] ?? null;

if (!$offer_id || !$customer_id) {
    echo json_encode(['success' => false, 'message' => 'Некорректные данные']);
    exit();
}

// Получаем freelancer_id текущего пользователя
$stmt = $conn->prepare("SELECT freelancer_id FROM freelancers WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $freelancer_id = $row['freelancer_id'];
} else {
    echo json_encode(['success' => false, 'message' => 'Фрилансер не найден']);
    exit();
}

// Получаем данные предложения, проверяем что оно действительно "ожидает"
$stmt = $conn->prepare("SELECT * FROM freelancer_offers WHERE offer_id = ? AND freelancer_id = ? AND status = 'ожидает'");
$stmt->bind_param('ii', $offer_id, $freelancer_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Предложение не найдено или уже принято']);
    exit();
}
$offer = $result->fetch_assoc();

// Начинаем транзакцию
$conn->begin_transaction();

try {
    // Создаем заказ с назначенным фрилансером, статусом "В процессе"
    $stmt = $conn->prepare("INSERT INTO orders (customer_id, freelancer_id, title, description, budget, deadline, status, is_paid, created_at) VALUES (?, ?, ?, ?, ?, NULL, 'В процессе', 0, NOW())");
    $stmt->bind_param('iissd', $customer_id, $freelancer_id, $offer['title'], $offer['description'], $offer['budget']);
    $stmt->execute();

    // Обновляем статус предложения
    $stmt = $conn->prepare("UPDATE freelancer_offers SET status = 'принято' WHERE offer_id = ?");
    $stmt->bind_param('i', $offer_id);
    $stmt->execute();

    $conn->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
}
