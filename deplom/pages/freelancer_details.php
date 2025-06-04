<?php
session_start();
include('db_connection.php');
include('functions.php');

// Получаем данные пользователя
$user_id = $_SESSION['user_id'] ?? null;
$role_id = $_SESSION['role_id'] ?? null;

// Получаем customer_id по user_id (если роль заказчика)
$customer_id = null;
if ($user_id && $role_id == 1) {
    $stmt = $conn->prepare("SELECT customer_id FROM customers WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $customer_row = $res->fetch_assoc();
        $customer_id = $customer_row['customer_id'];
    }
}

// Получаем ID фрилансера из URL
$freelancer_id = $_GET['id'] ?? null;

if ($freelancer_id) {
    // Запрос для получения информации о фрилансере
    $sql = "
        SELECT 
            f.freelancer_id,
            f.specialization,
            f.completed_orders,
            f.price,
            u.firstname,
            u.lastname,
            u.profile_photo,
            u.bio
        FROM freelancers f
        JOIN users u ON f.user_id = u.user_id
        WHERE f.freelancer_id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $freelancer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $freelancer = $result->fetch_assoc();
    } else {
        header('Location: freelancers.php');
        exit();
    }
}

// Обработка предложения заказа
$show_success_alert = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $role_id == 1 && $customer_id !== null) {
    if (isset($_POST['title']) && isset($_POST['order_description']) && isset($_POST['budget'])) {
        $title = $_POST['title'];
        $order_description = $_POST['order_description'];
        $budget = $_POST['budget'];

        $offer_sql = "
            INSERT INTO freelancer_offers (freelancer_id, customer_id, title, description, budget, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'ожидает', NOW())
        ";
        $offer_stmt = $conn->prepare($offer_sql);
        $offer_stmt->bind_param('iissd', $freelancer_id, $customer_id, $title, $order_description, $budget);
        $offer_stmt->execute();

        $show_success_alert = true;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Фрилансер: <?= htmlspecialchars($freelancer['firstname'] . ' ' . $freelancer['lastname']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/freelancer_details.css">
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

<main>
    <div class="freelancer-profile">
        <div class="freelancer-header">
            <div class="freelancer-photo">
                <img src="../images/<?= htmlspecialchars($freelancer['profile_photo']) ?>" alt="Фото фрилансера">
            </div>
            <div class="freelancer-info">
                <h2><?= htmlspecialchars($freelancer['firstname'] . ' ' . $freelancer['lastname']) ?></h2>
                <p class="specialization"><?= htmlspecialchars($freelancer['specialization']) ?></p>
                <p class="bio"><?= nl2br(htmlspecialchars($freelancer['bio'])) ?></p>
                <p class="rating">Завершённых проектов: <?= $freelancer['completed_orders'] ?></p>
                <p class="price">Цена: ₽<?= number_format($freelancer['price'], 0, ',', ' ') ?> / час</p>
            </div>
        </div>
</div>
        <?php if (isset($_SESSION['user_id']) && $role_id == 1): ?>
            <button id="openModalBtn" class="open-modal-btn">Предложить заказ</button>
        <?php elseif (isset($_SESSION['user_id']) && $role_id != 1): ?>
            <p class="auth-notice">Фрилансеры могут только просматривать информацию.</p>
        <?php else: ?>
            <p class="auth-notice">Для отправки предложения авторизуйтесь</p>
        <?php endif; ?>
    
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

<!-- Модальное окно -->
<div id="modal" class="modal">
    <div class="modal-content">
        <span class="close-btn" id="closeModalBtn">&times;</span>
        <h2>Предложить заказ</h2>

        <form method="POST" id="offerForm">
            <label for="title">Название заказа</label>
            <input type="text" name="title" id="title" required>

            <label for="order_description">Описание</label>
            <textarea name="order_description" id="order_description" placeholder="Опишите ваш заказ..." required></textarea>

            <label for="budget">Бюджет (₽)</label>
            <input type="number" name="budget" id="budget" step="1" required>

            <div class="form-actions">
                <button type="submit" class="submit-btn">Отправить предложение</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Обработчик мобильного меню
    document.getElementById('mobileMenuBtn').addEventListener('click', function() {
    document.getElementById('mobileMenu').classList.toggle('active');
    });

const modal = document.getElementById("modal");
const openBtn = document.getElementById("openModalBtn");
const closeBtn = document.getElementById("closeModalBtn");
const cancelBtn = document.getElementById("cancelBtn");

function openModal() {
    modal.classList.add("show");
}

function closeModal() {
    modal.classList.remove("show");
}

if (openBtn) openBtn.addEventListener("click", openModal);
if (closeBtn) closeBtn.addEventListener("click", closeModal);
if (cancelBtn) cancelBtn.addEventListener("click", closeModal);

document.getElementById('offerForm').addEventListener('submit', function(event) {
    event.preventDefault();
    const formData = new FormData(this);

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error('Ошибка сети');
        return response.text();
    })
    .then(() => {
        closeModal();
        alert('Ваше предложение успешно отправлено!');
        // Можно очистить форму, если нужно
        this.reset();
    })
    .catch(error => {
        alert('Ошибка при отправке предложения: ' + error.message);
    });
});
</script>

</body>
</html>
