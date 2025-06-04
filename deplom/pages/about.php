<?php
session_start();
include('db_connection.php');
include('functions.php');

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreeLand</title>
    <link rel="stylesheet" href="../styles/about.css">
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
  <h1>О нас</h1>
  <p>FreeLand - это инновационная онлайн-платформа, которая помогает фрилансерам и заказчикам находить друг друга для успешного сотрудничества. Мы стремимся сделать процесс поиска работы и сотрудников простым, удобным и безопасным.</p>
<div class="image-wrapper">
      <img src="../images/about_photo.jpeg" alt="О нашей команде" />
    </div>
  <div class="content-wrapper">
    <div class="content-text">
      <div class="two-columns">
        <div class="column">
          <h3>Наша миссия</h3>
          <p>Наша цель - представить платформу, где фрилансеры и заказчики могут легко и безопасно взаимодействовать, предлагая или находя работу. Мы фокусируемся на удобстве пользователей, обеспечивая надежную систему оплаты и высокий уровень защиты сделок.</p>

          <h3>Наша команда</h3>
          <p>FreeLand был создан в 2025 году с целью решить проблемы, с которыми сталкиваются фрилансеры и заказчики на других платформах. Мы поняли, что существующие биржи не всегда предлагают то, что хотят видеть пользователи и решили создать сервис, который решит все проблемы.</p>
        </div>

        <div class="column">
          <h3>Почему выбирают нас</h3>
          <ul>
            <li>Удобный интерфейс: интуитивно понятный и простой интерфейс для всех пользователей.</li>
            <li>Безопасные сделки; защита сделок через нашу систему безопасных платежей.</li>
            <li>Широкий выбор заказов и фрилансеров: ежедневно новые проекты и исполнители.</li>
          </ul>

          <h3>Связаться с нами</h3>
          <p>Если у вас есть вопросы или предложения, свяжитесь с нами по электронной почте или номеру телефону.</p>
        </div>
      </div>
    </div>
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
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('mobileMenu').classList.toggle('active');
        });
    </script>
</body>
</html>
