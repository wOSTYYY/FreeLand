<?php
session_start();
include('db_connection.php');

$user_id = $_SESSION['user_id'];

$firstname = $_POST['firstname'];
$lastname = $_POST['lastname'];
$company_name = $_POST['company_name'];
$contact_info = $_POST['contact_info'];
$new_password = $_POST['new_password'];
$photo_filename = null;

// Обработка фото
if ($_FILES['profile_photo']['name']) {
    $photo_filename = basename($_FILES['profile_photo']['name']);
    move_uploaded_file($_FILES['profile_photo']['tmp_name'], "../images/$photo_filename");

    $sql_photo = "UPDATE users SET profile_photo = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql_photo);
    $stmt->bind_param("si", $photo_filename, $user_id);
    $stmt->execute();
}

// Обновление users
$sql_user = "UPDATE users SET firstname = ?, lastname = ? WHERE user_id = ?";
$stmt = $conn->prepare($sql_user);
$stmt->bind_param("ssi", $firstname, $lastname, $user_id);
$stmt->execute();

// Обновление customers
$sql_customer = "UPDATE customers SET company_name = ?, contact_info = ? WHERE user_id = ?";
$stmt = $conn->prepare($sql_customer);
$stmt->bind_param("ssi", $company_name, $contact_info, $user_id);
$stmt->execute();

// Обновление пароля
if (!empty($new_password)) {
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $sql_pass = "UPDATE users SET password = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql_pass);
    $stmt->bind_param("si", $hashed_password, $user_id);
    $stmt->execute();
}

header("Location: Cprofile.php");
exit();
?>
