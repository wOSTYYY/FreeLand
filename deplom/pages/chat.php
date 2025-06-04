<?php
session_start();
include('db_connection.php');

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            echo handleCreateConversation();
            break;
        case 'get_messages':
            echo handleGetMessages();
            break;
        case 'send_message':
            echo handleSendMessage();
            break;
        default:
            echo json_encode(['error' => 'Неизвестное действие']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Ошибка сервера: ' . $e->getMessage()]);
}

function handleCreateConversation() {
    global $conn;
    
    $order_id = (int)$_GET['order_id'];
    $customer_id = (int)$_GET['user1'];
    $freelancer_id = (int)$_GET['user2'];
    
    // Проверяем существование чата
    $stmt = $conn->prepare("SELECT conversation_id FROM chat_conversations 
                          WHERE order_id = ? AND customer_id = ? AND freelancer_id = ?");
    $stmt->bind_param("iii", $order_id, $customer_id, $freelancer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $conversation = $result->fetch_assoc();
        return json_encode(['conversation_id' => $conversation['conversation_id']]);
    }
    
    // Создаем новый чат
    $stmt = $conn->prepare("INSERT INTO chat_conversations (order_id, customer_id, freelancer_id) 
                          VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $order_id, $customer_id, $freelancer_id);
    $stmt->execute();
    
    return json_encode(['conversation_id' => $stmt->insert_id]);
}

function handleGetMessages() {
    global $conn;
    
    $conversation_id = (int)$_GET['conversation_id'];
    
    $stmt = $conn->prepare("SELECT 
    m.message_id,
    m.message,
    m.created_at,
    u.user_id,
    u.firstname,
    u.lastname
FROM chat_messages m
JOIN users u ON m.sender_id = u.user_id
WHERE m.conversation_id = ?
ORDER BY m.created_at ASC");

    $stmt->bind_param("i", $conversation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'id' => $row['message_id'],
            'sender_id' => $row['user_id'],
            'sender_name' => $row['firstname'] . ' ' . $row['lastname'],
            'text' => htmlspecialchars($row['message']),
            'time' => date('H:i', strtotime($row['created_at'])),
            'date' => date('d.m.Y', strtotime($row['created_at']))
        ];
    }
    
    return json_encode($messages);
}

function handleSendMessage() {
    global $conn;
    
    $conversation_id = (int)$_POST['conversation_id'];
    $message = trim($_POST['message']);
    $sender_id = $_SESSION['user_id'];
    
    if (empty($message)) {
        return json_encode(['error' => 'Сообщение не может быть пустым']);
    }
    
    // Сохраняем сообщение
    $stmt = $conn->prepare("INSERT INTO chat_messages (conversation_id, sender_id, message) 
                          VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $conversation_id, $sender_id, $message);
    $stmt->execute();
    
    return json_encode(['success' => true, 'message_id' => $stmt->insert_id]);
}
?>