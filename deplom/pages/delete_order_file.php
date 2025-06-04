<?php
session_start();
include('db_connection.php');
include('functions.php');

// Проверяем авторизацию
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    echo json_encode(['success' => false, 'message' => 'Нет доступа']);
    exit();
}

$file_id = (int)($_POST['file_id'] ?? 0);
$user_id = $_SESSION['user_id'];
$freelancer_id = getFreelancerId($user_id);

// Проверяем файл
$stmt = $conn->prepare("SELECT * FROM order_files WHERE file_id = ? AND freelancer_id = ?");
$stmt->bind_param("ii", $file_id, $freelancer_id);
$stmt->execute();
$file_result = $stmt->get_result();

if ($file_result->num_rows === 1) {
    $file = $file_result->fetch_assoc();
    $file_path = $file['file_path'];

    // Удаляем файл с сервера
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    // Удаляем запись из базы данных
    $stmt = $conn->prepare("DELETE FROM order_files WHERE file_id = ?");
    $stmt->bind_param("i", $file_id);
    $stmt->execute();

    echo json_encode(['success' => true]);
    exit();
} else {
    echo json_encode(['success' => false, 'message' => 'Файл не найден или доступ запрещён']);
    exit();
}
?>
