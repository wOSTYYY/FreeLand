<?php
session_start();
include('db_connection.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


if (isset($_GET['id'])) {
    $order_id = (int)$_GET['id'];

    // Проверяем принадлежность заказа
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

    // Проверяем, что пользователь владелец
    $stmt = $conn->prepare("SELECT user_id FROM customers WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0 || $result->fetch_assoc()['user_id'] != $_SESSION['user_id']) {
        $_SESSION['error'] = "У вас нет прав для удаления этого заказа";
        header("Location: orders.php");
        exit();
    }

    // --- Получаем все conversation_id связанных с заказом ---
    $chat_conversation_ids = [];
    $stmt = $conn->prepare("SELECT conversation_id FROM chat_conversations WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $chat_conversation_ids[] = $row['conversation_id'];
    }

    // --- Удаляем сообщения из chat_messages по conversation_id ---
    if (!empty($chat_conversation_ids)) {
        $ids_str = implode(',', array_map('intval', $chat_conversation_ids));
        $conn->query("DELETE FROM chat_messages WHERE conversation_id IN ($ids_str)");
    }

    // --- Удаляем chat_conversations по order_id ---
    $stmt = $conn->prepare("DELETE FROM chat_conversations WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();

    // --- Удаляем отклики ---
    $stmt = $conn->prepare("DELETE FROM order_responses WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();

    // --- Удаляем файлы ---
    $stmt = $conn->prepare("DELETE FROM order_files WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();

    // --- Удаляем комментарии ---
    $stmt = $conn->prepare("DELETE FROM order_comments WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();

    // --- Удаляем сам заказ ---
    $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Заказ успешно удалён!";
    } else {
        $_SESSION['error'] = "Ошибка при удалении заказа: " . $conn->error;
    }
}

header("Location: Cprofile.php");
exit();
?>
