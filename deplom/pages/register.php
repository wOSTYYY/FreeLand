<?php
session_start();
$error_message = ""; // Переменная для ошибки

// Проверяем, есть ли ошибка, если она есть, она будет выводиться внизу формы
if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" href="../styles/vhod.css">
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
        </nav>

        <button class="mobile-menu-btn" id="mobileMenuBtn">☰</button>

        <nav class="mobile-menu" id="mobileMenu">
            <a href="DOindex.php">Главная</a>
            <a href="orders.php">Заказы</a>
            <a href="freelancers.php">Фрилансеры</a>
            <a href="about.php">О нас</a>
        </nav>
    </header>
    
    <main>
        <div class="form-container">
            <h2>Регистрация</h2>
            <form action="register_process.php" method="POST" enctype="multipart/form-data">
                <label for="lastname">Фамилия:</label>
                <input type="text" id="lastname" name="lastname" required>

                <label for="firstname">Имя:</label>
                <input type="text" id="firstname" name="firstname" required>

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>

                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required>

                <label for="role">Роль:</label>
                <select id="role" name="role">
                <option value="customer">Заказчик</option>
                    <option value="freelancer">Фрилансер</option>
                </select>

                <div id="payment-photo-container">
                <label for="payment_photo">Фото с QR-кодом для оплаты:</label>
                    <div class="file-upload">
                        <label class="file-upload-label">
                            Выберите файл
                            <input type="file" id="payment_photo" name="payment_photo" accept="image/*">
                        </label>
                        <div id="file-name" class="file-upload-info">Файл не выбран</div>
                    </div>
                </div>

                <button class="vhod" type="submit">Зарегистрироваться</button>
            </form>

            <?php if (!empty($error_message)) { ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php } ?>

            <a class="slova" href="login.php">Уже зарегистрированы? Войти</a>
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
        // Активация текущей страницы в меню
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = location.pathname.split('/').pop();
            const links = document.querySelectorAll('nav a');
            
            links.forEach(link => {
                if (link.getAttribute('href') === currentPage) {
                    link.classList.add('active');
                }
            });
            
            // Обработчик мобильного меню
            document.getElementById('mobileMenuBtn').addEventListener('click', function() {
                document.getElementById('mobileMenu').classList.toggle('active');
            });
        });

    </script>

<script>
    document.getElementById('payment_photo').addEventListener('change', function () {
        const fileName = this.files[0]?.name || 'Файл не выбран';
        document.getElementById('file-name').textContent = fileName;
    });
</script>

<script>
    const roleSelect = document.getElementById('role');
    const paymentPhotoContainer = document.getElementById('payment-photo-container');

    function togglePaymentPhoto() {
        if (roleSelect.value === 'freelancer') {
            paymentPhotoContainer.style.display = 'block';
        } else {
            paymentPhotoContainer.style.display = 'none';
            // Очистим выбранный файл, если скрываем поле
            document.getElementById('payment_photo').value = '';
            document.getElementById('file-name').textContent = 'Файл не выбран';
        }
    }

    // Запускаем при загрузке страницы
    document.addEventListener('DOMContentLoaded', () => {
        togglePaymentPhoto(); // чтобы сразу скрыть/показать по выбранной роли
    });

    // Запускаем при изменении выбора роли
    roleSelect.addEventListener('change', togglePaymentPhoto);

    // Обновление имени файла при выборе файла
    document.getElementById('payment_photo').addEventListener('change', function () {
        const fileName = this.files[0]?.name || 'Файл не выбран';
        document.getElementById('file-name').textContent = fileName;
    });
</script>


</body>
</html>
