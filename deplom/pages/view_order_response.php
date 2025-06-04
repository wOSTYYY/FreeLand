<?php
session_start();
include('db_connection.php');
include('functions.php');

// Проверка авторизации и роли
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role_id'] != 1) {
    header("Location: orders.php");
    exit();
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id == 0) {
    header("Location: orders.php");
    exit();
}

// Проверяем, что заказ принадлежит текущему заказчику
$stmt = $conn->prepare("SELECT customer_id FROM orders WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: orders.php");
    exit();
}

$order = $result->fetch_assoc();
$customer_id = getCustomerId($_SESSION['user_id']);

if ($order['customer_id'] != $customer_id) {
    header("Location: orders.php");
    exit();
}

// Получаем информацию о заказе
$stmt = $conn->prepare("SELECT title, status FROM orders WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_info = $stmt->get_result()->fetch_assoc();

// Получаем прикрепленные файлы
$stmt = $conn->prepare("SELECT * FROM order_files WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$files = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Получаем комментарии
$stmt = $conn->prepare("
    SELECT oc.*, u.firstname, u.lastname 
    FROM order_comments oc
    JOIN users u ON oc.user_id = u.user_id
    WHERE oc.order_id = ?
    ORDER BY oc.created_at DESC
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ответ по заказу | Freeland</title>
    <link rel="stylesheet" href="../styles/view_response.css">
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

<main class="view-response-container">
    <h1>Ответ по заказу: <?= htmlspecialchars($order_info['title']) ?></h1>
    
    <div class="response-section">
        <h2>Прикрепленные файлы</h2>
        <?php if (!empty($files)): ?>
            <ul class="file-list">
                <?php foreach ($files as $file): ?>
                    <li>
                        <a href="<?= htmlspecialchars($file['file_path']) ?>" download>
                            <?= htmlspecialchars($file['file_name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Файлы не прикреплены</p>
        <?php endif; ?>
    </div>
    
    <div class="response-section">
        <h2>Комментарии</h2>
        <?php if (!empty($comments)): ?>
            <div class="comments-list">
                <?php foreach ($comments as $comment): ?>
                    <div class="comment">
                        <div class="comment-header">
                            <span class="comment-author">
                                <?= htmlspecialchars($comment['firstname'] . ' ' . htmlspecialchars($comment['lastname'])) ?>
                            </span>
                            <span class="comment-date">
                                <?= date('d.m.Y H:i', strtotime($comment['created_at'])) ?>
                            </span>
                        </div>
                        <div class="comment-text">
                            <?= nl2br(htmlspecialchars($comment['comment'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Комментариев нет</p>
        <?php endif; ?>
    </div>
    
    <div class="response-actions">
        <a href="order_details.php?id=<?= $order_id ?>" class="btn btn-back">Назад к заказу</a>
        
        <?php if ($order_info['status'] == 'Выполнен'): ?>
            <form action="confirm_order_completion.php" method="POST" style="display: inline;">
                <input type="hidden" name="order_id" value="<?= $order_id ?>">
                <button type="submit" class="btn btn-confirm">Подтвердить выполнение</button>
            </form>
        <?php endif; ?>
    </div>
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
    // Обработчик мобильного меню
            document.getElementById('mobileMenuBtn').addEventListener('click', function() {
                document.getElementById('mobileMenu').classList.toggle('active');
            });
    </script>
</body>
</html>