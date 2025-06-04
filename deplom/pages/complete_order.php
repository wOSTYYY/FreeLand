<?php
session_start();
include('db_connection.php');
include('functions.php');

// Проверка авторизации и роли
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role_id'] != 2) {
    header("Location: orders.php");
    exit();
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id == 0) {
    header("Location: orders.php");
    exit();
}

// Проверяем, что заказ принадлежит текущему фрилансеру
$stmt = $conn->prepare("SELECT freelancer_id FROM orders WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: orders.php");
    exit();
}

$order = $result->fetch_assoc();
$freelancer_id = getFreelancerId($_SESSION['user_id']);

if (!$freelancer_id || $order['freelancer_id'] != $freelancer_id) {
    header("Location: orders.php");
    exit();
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Создаем папку для заказа, если ее нет
    $upload_dir = "uploads/order_$order_id/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Обработка загруженных файлов
    $uploaded_files = [];
    if (!empty($_FILES['project_files']['name'][0])) {
        foreach ($_FILES['project_files']['name'] as $key => $name) {
            if ($_FILES['project_files']['error'][$key] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['project_files']['tmp_name'][$key];
                $file_name = basename($name);
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($tmp_name, $file_path)) {
                    $uploaded_files[] = [
                        'name' => $file_name,
                        'path' => $file_path
                    ];
                    
                    // Сохраняем информацию о файле в БД
                    $stmt = $conn->prepare("INSERT INTO order_files (order_id, freelancer_id, file_name, file_path) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiss", $order_id, $freelancer_id, $file_name, $file_path);
                    $stmt->execute();
                }
            }
        }
    }

    
    // Сохраняем комментарий
    $comment = $_POST['comment'] ?? '';
    if (!empty($comment)) {
        try {
            $stmt = $conn->prepare("INSERT INTO order_comments (order_id, user_id, comment) VALUES (?, ?, ?)");
            if ($stmt === false) {
                throw new Exception("Ошибка подготовки запроса: " . $conn->error);
            }
            
            $stmt->bind_param("iis", $order_id, $_SESSION['user_id'], $comment);
            if (!$stmt->execute()) {
                throw new Exception("Ошибка выполнения запроса: " . $stmt->error);
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            $_SESSION['error_message'] = 'Ошибка при сохранении комментария';
        }
    }

    // Меняем статус заказа на "Выполнено"
    $stmt = $conn->prepare("UPDATE orders SET status = 'Выполнено' WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();

    // Перенаправляем на страницу заказа с сообщением об успехе
    $_SESSION['success_message'] = 'Заказ успешно завершен!';
    header("Location: order_details.php?id=$order_id");
    exit();
}

// Получаем информацию о заказе
$stmt = $conn->prepare("SELECT title FROM orders WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_info = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Завершение заказа | Freeland</title>
    <link rel="stylesheet" href="../styles/complete_order.css">
</head>
<body>
<header>
    <div class="logo-container">
        <div class="logo">
            <img src="../images/logo.png" alt="Freeland Logo">
            Freeland
        </div>
    </div>

    <nav class="desktop-menu">
        <a href="DOindex.php">Главная</a>
        <a href="orders.php">Заказы</a>
        <a href="freelancers.php">Фрилансеры</a>
        <a href="about.php">О нас</a>
        
        <?php if (isset($_SESSION['user_id'])) { ?>
            <a href="<?= getProfileUrl() ?>">Профиль</a>
        <?php } else { ?>
            <a href="login.php" style="color: #fdd835;" class="auth-button">Авторизоваться</a>
        <?php } ?>
        <?php if (isset($_SESSION['user_id'])) { ?>
            <a href="logout.php" class="auth-button">Выход</a>
        <?php } ?>
    </nav>

    <button class="mobile-menu-btn" id="mobileMenuBtn">☰</button>

    <nav class="mobile-menu" id="mobileMenu">
        <a href="DOindex.php">Главная</a>
        <a href="orders.php">Заказы</a>
        <a href="freelancers.php">Фрилансеры</a>
        <a href="about.php">О нас</a>
        <?php if (isset($_SESSION['user_id'])) { ?>
            <a href="<?= getProfileUrl() ?>">Профиль</a>
        <?php } else { ?>
            <a href="login.php">Авторизоваться</a>
        <?php } ?>
        <?php if (isset($_SESSION['user_id'])) { ?>
            <a href="logout.php">Выход</a>
        <?php } ?>
    </nav>
</header>

<main class="complete-order-container">
    <h1>Завершение заказа: <?= htmlspecialchars($order_info['title']) ?></h1>
    
    <form action="complete_order.php?id=<?= $order_id ?>" method="POST" enctype="multipart/form-data" class="complete-order-form">
        <div class="form-section">
            <h2>Прикрепите выполненные файлы</h2>
            <div class="file-upload">
                <label for="project_files" class="file-upload-label">
                    <span>Выберите файлы</span>
                    <input type="file" id="project_files" name="project_files[]" multiple>
                </label>
                <div class="file-upload-info" id="file-info">Файлы не выбраны</div>
            </div>
        </div>
        
        <div class="form-section">
            <h2>Комментарий к выполнению</h2>
            <textarea name="comment" placeholder="Опишите выполненные работы, особенности проекта и т.д."></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-complete">Завершить заказ</button>
            <a href="order_details.php?id=<?= $order_id ?>" class="btn btn-cancel">Отмена</a>
        </div>
    </form>
</main>

<footer>
    <div class="footer-container">
        <div class="footer-section">
            <div class="footer-logo">
                <img src="../images/logo.png" alt="Freeland Logo">
                Freeland
            </div>
            <p>Платформа для фрилансеров и заказчиков</p>
        </div>

        <div class="footer-section">
            <h3 class="footer-title">Найди нас также:</h3>
            <div class="social-links">
                <a href="#" class="social-link">
                    <img src="../images/vk.png" alt="VK" width="40" height="40">
                </a>
                <a href="#" class="social-link">
                    <img src="../images/inst.png" alt="Instagram" width="40" height="40">
                </a>
                <a href="#" class="social-link">
                    <img src="../images/tg.png" alt="Telegram" width="40" height="40">
                </a> 
            </div>
        </div>

        <div class="footer-section">
            <h3 class="footer-title">Контакты</h3>
            <p class="contact-info">Email: freeland.servis@mail.ru</p>
            <p class="contact-info">Телефон: 8 (800) 552-25-52</p>
        </div>

        <div class="footer-section">
            <h3 class="footer-title">Меню</h3>
            <a href="about.html" class="footer-link">О Нас</a>
            <a href="#" class="footer-link">Политика конфиденциальности</a>
            <a href="#" class="footer-link">Условия использования</a>
        </div>
    </div>

    <div class="copyright">
        © Наумов Глеб Александрович
    </div>
</footer>

<script>
document.getElementById('project_files').addEventListener('change', function(e) {
    const files = e.target.files;
    const fileInfo = document.getElementById('file-info');
    
    if (files.length === 0) {
        fileInfo.textContent = 'Файлы не выбраны';
    } else if (files.length === 1) {
        fileInfo.textContent = `Выбран файл: ${files[0].name}`;
    } else {
        fileInfo.textContent = `Выбрано файлов: ${files.length}`;
    }
});
</script>
</body>
</html>