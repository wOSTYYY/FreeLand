<?php
session_start();
session_unset();  // Удаляем все переменные сессии
session_destroy(); // Уничтожаем сессию

// Перенаправление на страницу входа
header('Location: login.php');
exit();
?>
