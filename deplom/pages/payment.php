<?php
session_start();
include('db_connection.php');
include('functions.php');

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Получаем ID заказа
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id == 0) {
    header("Location: orders.php");
    exit();
}

// Получаем информацию о заказе
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo "Заказ не найден.";
    exit();
}

// Получаем user_id фрилансера через freelancer_id
$payment_photo = null;

if (!empty($order['freelancer_id'])) {
    $stmt = $conn->prepare("
        SELECT u.payment_photo 
        FROM freelancers f 
        JOIN users u ON f.user_id = u.user_id 
        WHERE f.freelancer_id = ?
    ");
    $stmt->bind_param("i", $order['freelancer_id']);
    $stmt->execute();
    $freelancer_result = $stmt->get_result()->fetch_assoc();
    $payment_photo = $freelancer_result['payment_photo'] ?? null;
}


// Проверяем, что заказ принадлежит текущему пользователю
$stmt = $conn->prepare("SELECT user_id FROM customers WHERE customer_id = ?");
$stmt->bind_param("i", $order['customer_id']);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

if ($customer['user_id'] != $_SESSION['user_id']) {
    echo "У вас нет прав на оплату этого заказа.";
    exit();
}

// Обработка оплаты
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_as_paid'])) {
    $stmt = $conn->prepare("UPDATE orders SET is_paid = 1 WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();

    header("Location: order_details.php?id=" . $order_id . "&payment=waiting_confirmation");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оплата заказа | Freeland</title>
    <link rel="stylesheet" href="../styles/payment.css">
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

<main class="payment-container">
    <h1>Оплата заказа №<?= $order_id ?></h1>
    
    <div class="payment-card">
        <div class="payment-info">
            <h2><?= htmlspecialchars($order['title']) ?></h2>
            <p class="amount">Сумма к оплате: <strong>₽<?= number_format($order['budget'], 2, ',', ' ') ?></strong></p>
            
            <div class="qr-code-placeholder">
                <?php if ($payment_photo): ?>
                    <img src="../images/ОПЛАТА/<?= htmlspecialchars($payment_photo) ?>" alt="QR-код для оплаты">
                    <p>Отсканируйте QR-код для оплаты</p>
                <?php else: ?>
                    <p>QR-код для оплаты не загружен.</p>
                <?php endif; ?>
            </div>

            
            <form method="POST" class="payment-form">
                <input type="hidden" name="mark_as_paid" value="1">
                <button type="submit" class="btn btn-pay">Оплатил</button>
            </form>
            
            <p class="payment-note">
                После оплаты нажмите кнопку "Оплатил" для подтверждения платежа.
                Статус заказа будет автоматически обновлен.
            </p>
        </div>
    </div>
    
    <a href="order_details.php?id=<?= $order_id ?>" class="btn btn-back">Вернуться к заказу</a>
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