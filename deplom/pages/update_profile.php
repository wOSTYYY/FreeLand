<?php
session_start();
include('db_connection.php');

$user_id = $_SESSION['user_id'];
$firstname = $_POST['firstname'];
$lastname = $_POST['lastname'];
$specialization = $_POST['specialization'];
$bio = $_POST['bio'];
$skills = $_POST['skills'];
$price = $_POST['price'];
$new_password = $_POST['new_password'];

// Обработка фото профиля
if (!empty($_FILES['profile_photo']['name'])) {
    $photo_filename = basename($_FILES['profile_photo']['name']);
    move_uploaded_file($_FILES['profile_photo']['tmp_name'], "../images/$photo_filename");

    $sql_photo = "UPDATE users SET profile_photo = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql_photo);
    $stmt->bind_param("si", $photo_filename, $user_id);
    $stmt->execute();
}

// Обработка фото с QR-кодом для оплаты
if (!empty($_FILES['payment_photo']['name'])) {
    $payment_photo_filename = basename($_FILES['payment_photo']['name']);
    move_uploaded_file($_FILES['payment_photo']['tmp_name'], "../images/ОПЛАТА/$payment_photo_filename");

    $sql_payment_photo = "UPDATE users SET payment_photo = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql_payment_photo);
    $stmt->bind_param("si", $payment_photo_filename, $user_id);
    $stmt->execute();
}

// Обновление данных пользователя
$sql_user = "UPDATE users SET firstname = ?, lastname = ?, bio = ? WHERE user_id = ?";
$stmt = $conn->prepare($sql_user);
$stmt->bind_param("sssi", $firstname, $lastname, $bio, $user_id);
$stmt->execute();

// Обновление данных фрилансера
$sql_freelancer = "UPDATE freelancers SET specialization = ?, skills = ?, price = ? WHERE user_id = ?";
$stmt = $conn->prepare($sql_freelancer);
$stmt->bind_param("ssdi", $specialization, $skills, $price, $user_id);
$stmt->execute();

// Обновление пароля, если задан
if (!empty($new_password)) {
    if (strlen($new_password) < 6) {
        // Пароль слишком короткий — возвращаем с ошибкой
        header("Location: Fprofile.php?error=Пароль должен содержать минимум 6 символов");
        exit();
    }
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $sql_pass = "UPDATE users SET password = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql_pass);
    $stmt->bind_param("si", $hashed_password, $user_id);
    $stmt->execute();
}

header("Location: Fprofile.php");
exit();
?>
