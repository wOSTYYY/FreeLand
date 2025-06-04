<?php
session_start();
include('db_connection.php');
include('functions.php');

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];

// Получаем данные заказчика
$sql = "SELECT u.firstname, u.lastname, u.profile_photo, c.company_name, c.contact_info
        FROM users u
        JOIN customers c ON u.user_id = c.user_id
        WHERE u.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$customer_data = $stmt->get_result()->fetch_assoc();

// Заказы
$order_sql = "SELECT order_id, title, description, budget, deadline, status FROM orders WHERE customer_id = (SELECT customer_id FROM customers WHERE user_id = ?)";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param('i', $user_id);
$order_stmt->execute();
$orders = $order_stmt->get_result();

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль фрилансера</title>
    <link rel="stylesheet" href="../styles/profile.css">
</head>
<body>
<div class="wrapper">
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

    <main class="profile-container">
    <div class="profile-header">
        <div class="profile-photo">
            <img src="../images/<?php echo $customer_data['profile_photo']; ?>" alt="Фото профиля">
        </div>
        <div class="profile-info">
            <h1><?php echo ($customer_data['company_name']); ?></h1>
            <p class="specialization"><?php echo $customer_data['firstname'], ' ', $customer_data['lastname']; ?></p>
            <p class="bio"><?php echo ($customer_data['contact_info']); ?></p>
            <button class="edit-profile-btn" onclick="openModal()">Изменить профиль</button>
            <button class="zakaz-btn">Новый заказ</button>
        </div>
    </div>

    <div class="my-orders">
    <h2>Мои заказы:</h2>
        <div class="order-list">
            <?php while ($order = $orders->fetch_assoc()) { ?>
                <div class="order-card clickable" onclick="location.href='order_details.php?id=<?= $order['order_id'] ?>'">
                    <h3><?php echo htmlspecialchars($order['title']); ?></h3>
                    <p><?php echo nl2br(htmlspecialchars(substr($order['description'], 0, 150) . (strlen($order['description']) > 150 ? '...' : ''))); ?></p>
                    <div class="order-details">
                        <span class="price">₽<?php echo number_format($order['budget'], 0, ',', ' '); ?></span>
                        <span class="deadline">До <?php echo date('d.m.Y', strtotime($order['deadline'])); ?></span>
                    </div>
                    <span class="status"><?php echo $order['status']; ?></span>
                    </div>
                <?php } ?>
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
    </div>

    <div id="editProfileModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeModal()">&times;</span>
    <form action="update_profile_customer.php" method="POST" enctype="multipart/form-data">
      <h2 style="margin-bottom: 15px;" class="h2exe">Редактирование профиля</h2>

      <label>Имя</label>
      <input type="text" name="firstname" value="<?= htmlspecialchars($customer_data['firstname'] ?? '') ?>">

      <label>Фамилия</label>
      <input type="text" name="lastname" value="<?= htmlspecialchars($customer_data['lastname'] ?? '') ?>">

      <label>Название компании</label>
      <input type="text" name="company_name" value="<?= htmlspecialchars($customer_data['company_name'] ?? '') ?>">

      <label>Контактная информация</label>
      <textarea name="contact_info"><?= htmlspecialchars($customer_data['contact_info'] ?? '') ?></textarea>

      <label>Новая фотография</label>
      <input type="file" name="profile_photo">

      <label>Новый пароль</label>
      <input type="password" name="new_password">

      <button type="submit" style="margin-top: 10px;">Сохранить изменения</button>
    </form>
  </div>
</div>

<div id="orderModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeOrderModal()">&times;</span>
    <form action="create_order.php" method="POST">
      <h2 style="margin-bottom: 15px;" class="h2exe">Новый заказ</h2>

      <label >Название заказа</label>
      <input type="text" name="title" required>

      <label>Описание</label>
      <textarea name="description" rows="4" required></textarea>

      <label>Категория</label>
      <input type="text" name="category" required>

      <label>Бюджет (₽)</label>
      <input type="number" name="budget" step="1" required>

      <label>Срок сдачи</label>
      <input type="date" name="deadline" required>

      <button type="submit" style="margin-top: 10px;">Разместить заказ</button>
    </form>
  </div>
</div>


<script>
document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('mobileMenu').classList.toggle('active');
        });


const editProfileModal = document.getElementById('editProfileModal');
const orderModal = document.getElementById('orderModal');

function openEditProfileModal() {
  editProfileModal.classList.add('show');
}

function closeEditProfileModal() {
  editProfileModal.classList.remove('show');
}

function openOrderModal() {
  orderModal.classList.add('show');
}

function closeOrderModal() {
  orderModal.classList.remove('show');
}

// Обработчики для кнопок
document.querySelector('.edit-profile-btn').addEventListener('click', openEditProfileModal);
document.querySelector('.zakaz-btn').addEventListener('click', openOrderModal);

// Закрывающие кнопки
document.querySelector('#editProfileModal .close-btn').addEventListener('click', closeEditProfileModal);
document.querySelector('#orderModal .close-btn').addEventListener('click', closeOrderModal);

window.addEventListener('click', (event) => {
  if(event.target === editProfileModal) {
    closeEditProfileModal();
  }
  if(event.target === orderModal) {
    closeOrderModal();
  }
});


</script>
</body>
</html>