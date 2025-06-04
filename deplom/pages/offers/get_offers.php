<?php
session_start();
include('../db_connection.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Forbidden');
}

$user_id = $_SESSION['user_id'];

// Получаем freelancer_id текущего пользователя
$stmt = $conn->prepare("SELECT freelancer_id FROM freelancers WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $freelancer_id = $row['freelancer_id'];
} else {
    echo json_encode([]);
    exit();
}

// Выбираем предложения со статусом "ожидает"
$stmt = $conn->prepare("SELECT offer_id, title, description, budget, customer_id FROM freelancer_offers WHERE freelancer_id = ? AND status = 'ожидает' ORDER BY created_at DESC");
$stmt->bind_param('i', $freelancer_id);
$stmt->execute();
$result = $stmt->get_result();

$offers = [];
while ($row = $result->fetch_assoc()) {
    $offers[] = $row;
}

header('Content-Type: application/json');
echo json_encode($offers);
