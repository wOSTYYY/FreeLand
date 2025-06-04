<?php
// Параметры для подключения к базе данных
$host = 'localhost'; // Обычно localhost
$db_name = 'freeland_db'; // Имя базы данных
$username = 'root'; // Имя пользователя базы данных
$password = ''; // Пароль для доступа к базе данных (по умолчанию для localhost — пустой)

// Создаем подключение
$conn = new mysqli($host, $username, $password, $db_name);

// Проверка соединения
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
?>
