<?php
session_start();
include('../pages/db_connection.php');

// Включим отображение ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
    header("HTTP/1.1 403 Forbidden");
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit();
}

$order_id = (int)$_POST['order_id'];
$freelancer_user_id = (int)$_POST['freelancer_id']; // Это user_id из таблицы users
$response_id = (int)$_POST['response_id'];

// 1. Проверяем права доступа (что пользователь - владелец заказа)
$stmt = $conn->prepare("SELECT o.customer_id 
                       FROM orders o
                       JOIN customers c ON o.customer_id = c.customer_id
                       WHERE o.order_id = ? AND c.user_id = ?");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Нет прав для выполнения этого действия']);
    exit();
}

// 2. Получаем freelancer_id по user_id
$stmt = $conn->prepare("SELECT freelancer_id FROM freelancers WHERE user_id = ?");
$stmt->bind_param("i", $freelancer_user_id);
$stmt->execute();
$freelancer_result = $stmt->get_result();

if ($freelancer_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Указанный пользователь не является фрилансером']);
    exit();
}

$freelancer_data = $freelancer_result->fetch_assoc();
$freelancer_id = $freelancer_data['freelancer_id'];

// 3. Проверяем, что отклик существует и принадлежит указанному фрилансеру
$stmt = $conn->prepare("SELECT response_id FROM order_responses 
                       WHERE response_id = ? AND freelancer_id = ? AND order_id = ?");
$stmt->bind_param("iii", $response_id, $freelancer_user_id, $order_id);
$stmt->execute();
$response_result = $stmt->get_result();

// Получаем цену из отклика фрилансера
$stmt = $conn->prepare("SELECT price FROM order_responses WHERE response_id = ?");
$stmt->bind_param("i", $response_id);
$stmt->execute();
$price_result = $stmt->get_result();


if ($response_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Отклик не найден или не принадлежит указанному фрилансеру']);
    exit();
}

$price_row = $price_result->fetch_assoc();
$new_price = $price_row['price'];

// Начинаем транзакцию
$conn->begin_transaction();

try {
    // 1. Назначаем фрилансера и обновляем цену заказа
    $stmt = $conn->prepare("UPDATE orders SET 
                          freelancer_id = ?, 
                          status = 'В процессе',
                          budget = ?
                          WHERE order_id = ?");
    $stmt->bind_param("idi", $freelancer_id, $new_price, $order_id);
    $stmt->execute();
    
    // 2. Помечаем отклик как принятый
    $stmt = $conn->prepare("UPDATE order_responses SET 
                          status = 'Принят'
                          WHERE response_id = ?");
    $stmt->bind_param("i", $response_id);
    $stmt->execute();
    
    // 3. Помечаем другие отклики как отклоненные
    $stmt = $conn->prepare("UPDATE order_responses SET 
                          status = 'Отклонен'
                          WHERE order_id = ? AND response_id != ?");
    $stmt->bind_param("ii", $order_id, $response_id);
    $stmt->execute();
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Фрилансер успешно назначен на заказ'
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка базы данных: ' . $e->getMessage()
    ]);
}