<?php
session_start();
include('db_connection.php');
include('functions.php');

// Получение данных о заказах
$sql_orders = "SELECT * FROM orders WHERE status = 'Ожидает исполнителя' ORDER BY created_at DESC LIMIT 4";
$result_orders = $conn->query($sql_orders);

// Получение данных о фрилансерах
$sql_freelancers = "
    SELECT u.user_id, u.lastname, u.firstname, u.profile_photo, f.specialization, f.completed_orders
    FROM users u
    INNER JOIN freelancers f ON u.user_id = f.user_id
    ORDER BY f.completed_orders DESC
    LIMIT 4";  // Получаем 4 лучших фрилансера
$result_freelancers = $conn->query($sql_freelancers);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreeLand</title>
    <link rel="stylesheet" href="../styles/index.css">
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
            
            <?php
             if (isset($_SESSION['user_id'])) { ?>
            <!-- Если пользователь авторизован, показываем кнопку "Профиль" -->
            <a href="<?= getProfileUrl() ?>">Профиль</a>
        <?php } else { ?>
            <!-- Если пользователь не авторизован, показываем кнопку "Авторизация" -->
            <a href="login.php" style="color: #fdd835;" class="auth-button">Авторизоваться</a>
        <?php } ?>
        <?php if (isset($_SESSION['user_id'])) { ?>
        <!-- Кнопка для выхода из системы -->
        <a href="logout.php" class="auth-button">Выход</a>
        <?php } ?>
        </nav>

        <button class="mobile-menu-btn" id="mobileMenuBtn">☰</button>

        <nav class="mobile-menu" id="mobileMenu">
            <a href="DOindex.php">Главная</a>
            <a href="orders.php">Заказы</a>
            <a href="freelancers.php">Фрилансеры</a>
            <a href="about.php">О нас</a>
            <?php 
            if (isset($_SESSION['user_id'])) { ?>
            <!-- Если пользователь авторизован, показываем ссылку "Профиль" -->
            <a href="<?= getProfileUrl() ?>">Профиль</a>
        <?php } else { ?>
            <!-- Если пользователь не авторизован, показываем ссылку "Авторизация" -->
            <a href="login.php">Авторизоваться</a>
        <?php } ?>
        <?php if (isset($_SESSION['user_id'])) { ?>
            <!-- Кнопка для выхода из системы -->
            <a href="logout.php">Выход</a>
        <?php } ?>
        </nav>
    </header>
    
    <main>
        <section class="hero">
            <h1>Добро пожаловать в FreeLand</h1>
            <p class="subtitle">Вашу идеальную платформу для поиска фрилансеров<br>и заказчиков. Быстро, удобно и безопасно</p>
            
            <div class="gif-container">
                <img class="chel" src="../images/animation1.gif"> <img class="robot" src="../images/animation2.gif"> <img class="chel" src="../images/animation3.gif">           
            </div>
        </section>

         <!-- Горящие заказы -->
    <section class="urgent-orders">
        <h2>Горящие заказы</h2>
        <div class="orders-grid">
            <?php while ($order = $result_orders->fetch_assoc()) { ?>
                <div class="order-card">
                    <h3><?php echo $order['title']; ?></h3>
                    <div class="order-info">
                        <span class="price">₽<?php echo $order['budget']; ?></span>
                        <span class="deadline">До <?php echo $order['deadline']; ?></span>
                    </div>
                </div>
            <?php } ?>
        </div>
    </section>

        <!-- Лучшие исполнители -->
    <section class="top-freelancers">
        <h2>Лучшие исполнители</h2>
        <div class="freelancers-grid">
            <?php while ($freelancer = $result_freelancers->fetch_assoc()) { ?>
                <div class="freelancer-card">
                    <img src="../images/<?php echo $freelancer['profile_photo']; ?>" alt="<?php echo $freelancer['lastname']; ?>" class="freelancer-photo">
                    <h3><?php echo $freelancer['lastname'], " ", $freelancer['firstname']; ?></h3>
                    <p class="specialization"><?php echo $freelancer['specialization']; ?></p>
                    <div class="rating">
                            Выполнено заказов: <?php echo $freelancer['completed_orders']; ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </section>
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
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('mobileMenu').classList.toggle('active');
        });
    </script>
</body>
</html>
