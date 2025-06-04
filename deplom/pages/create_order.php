<?php
session_start();
include('db_connection.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = null;

// Получим customer_id из таблицы customers
$sql_cust = "SELECT customer_id FROM customers WHERE user_id = ?";
$stmt_cust = $conn->prepare($sql_cust);
$stmt_cust->bind_param("i", $_SESSION['user_id']);
$stmt_cust->execute();
$result = $stmt_cust->get_result();
if ($row = $result->fetch_assoc()) {
    $customer_id = $row['customer_id'];
}

if (!$customer_id) {
    die("Ошибка: заказчик не найден");
}

// Данные из формы
$title = $_POST['title'];
$description = $_POST['description'];
$category = $_POST['category'];
$budget = $_POST['budget'];
$deadline = $_POST['deadline'];

$sql = "INSERT INTO orders (customer_id, title, description, category, budget, deadline, status, is_paid, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'Ожидает исполнителя', 0, NOW())";

$stmt = $conn->prepare($sql);
$stmt->bind_param("isssds", $customer_id, $title, $description, $category, $budget, $deadline);

if ($stmt->execute()) {
    header("Location: Cprofile.php");
    exit();
} else {
    echo "Ошибка при создании заказа: " . $conn->error;
}
?>
