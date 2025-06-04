<?php
session_start();
include('db_connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];


    // Проверка наличия пользователя в БД
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['email'] = $user['email'];

            if (isset($_POST['remember']) && $_POST['remember'] == 'on') {
                setcookie('user_id', $user['user_id'], time() + (30 * 24 * 60 * 60), "/");
            }

            if ($user['role_id'] == 2) {
                header('Location: Fprofile.php');
            } elseif ($user['role_id'] == 1) {
                header('Location: Cprofile.php');
            } else {
                header('Location: login.php?error=Неизвестная роль пользователя');
            }
            exit();
        } else {
            header('Location: login.php?error=Неверный пароль');
            exit();
        }
    } else {
        header('Location: login.php?error=Пользователь с таким email не найден');
        exit();
    }
}
?>
