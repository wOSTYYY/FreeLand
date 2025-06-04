<?php
session_start();
include('db_connection.php');
include('functions.php');

// Получаем данные пользователя
$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];

$sql = "SELECT u.user_id, u.lastname, u.firstname, u.profile_photo, f.specialization, f.skills, f.price, u.bio
        FROM users u
        INNER JOIN freelancers f ON u.user_id = f.user_id
        WHERE u.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$freelancer_data = $stmt->get_result()->fetch_assoc();


$order_sql = "SELECT order_id, title, description, budget, deadline, status 
              FROM orders 
              WHERE freelancer_id = (
                  SELECT freelancer_id FROM freelancers WHERE user_id = ?
              )";
$order_stmt = $conn->prepare($order_sql);
$order_stmt->bind_param('i', $user_id);
$order_stmt->execute();
$orders_result = $order_stmt->get_result();

$sql_payment = "SELECT payment_photo FROM users WHERE user_id = ?";
$stmt_payment = $conn->prepare($sql_payment);
$stmt_payment->bind_param('i', $user_id);
$stmt_payment->execute();
$payment_photo_data = $stmt_payment->get_result()->fetch_assoc();

// Получаем freelancer_id текущего пользователя
$stmt = $conn->prepare("SELECT freelancer_id FROM freelancers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$freelancer_id = null;
if ($row = $res->fetch_assoc()) {
    $freelancer_id = $row['freelancer_id'];
}

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
<header>
    <style>
    html, body {
        height: 100%;
        margin: 0;
        padding: 0;
    }

    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    main {
        flex: 1 0 auto;
    }

    footer {
        flex-shrink: 0;
        background-color: var(--footer-bg);
        color: var(--text-color);
        padding: 30px 0;
        width: 100%;
        margin-top: auto;
    }

    </style>
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
<div class="profile-container">
    <div class="profile-header">
        <div class="profile-photo">
            <img src="../images/<?php echo $freelancer_data['profile_photo']; ?>" alt="Фото профиля" />
        </div>
        <div class="profile-info">
            <h1><?php echo $freelancer_data['firstname'] . ' ' . $freelancer_data['lastname']; ?></h1>
            <p class="specialization"><?php echo $freelancer_data['specialization']; ?></p>
            <p class="bio"><?php echo $freelancer_data['bio']; ?></p>
            <button class="edit-profile-btn" onclick="openModal()">Изменить профиль</button>
            <!-- Кнопка открытия модального окна с предложениями -->
            <?php if ($freelancer_id): ?>
            <button id="openOffersBtn" class="offers-btn">Предложения заказов</button>
            <?php endif; ?>
        </div>
    </div>

        <div class="my-orders">
        <h2>Мои заказы:</h2>
        <div class="order-list">
            <?php while ($order = $orders_result->fetch_assoc()) { ?>
                <div class="order-card clickable" onclick="location.href='order_details.php?id=<?= $order['order_id'] ?>'">
                    <h3><?php echo htmlspecialchars($order['title']); ?></h3>
                    <p><?php echo nl2br(htmlspecialchars(substr($order['description'], 0, 150) . (strlen($order['description']) > 150 ? '...' : ''))); ?></p>
                    <div class="order-details">
                        <span class="price">₽<?php echo number_format($order['budget'], 0, ',', ' '); ?></span>
                        <span class="deadline">До <?php echo date('d.m.Y', strtotime($order['deadline'])); ?></span>
                    </div>
                    <span class="status" data-status="<?php echo $order['status']; ?>"><?php echo $order['status']; ?></span>
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

    <div id="editProfileModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <form action="update_profile.php" method="POST" enctype="multipart/form-data">
            <h2 style="margin-bottom: 15px;">Редактирование профиля</h2>

            <label>Имя</label>
            <input type="text" name="firstname" value="<?php echo isset($freelancer_data['firstname']) ? $freelancer_data['firstname'] : ''; ?>">
            <label>Фамилия</label>
            <input type="text" name="lastname" value="<?php echo isset($freelancer_data['lastname']) ? $freelancer_data['lastname'] : ''; ?>">

            <label>QR-code для оплаты</label>
            <input type="file" name="payment_photo" accept="image/*">

            <?php if (!empty($payment_photo_data['payment_photo'])): ?>
                <div style="margin-top: 10px;">
                    <p>Текущее фото QR-кода:</p>
                    <img src="../images/ОПЛАТА/<?php echo htmlspecialchars($payment_photo_data['payment_photo']); ?>" alt="QR-код для оплаты" style="max-width: 150px; max-height: 150px; border-radius: 8px; border: 1px solid #ccc;">
                </div>
            <?php endif; ?>

            <label>Специализация</label>
            <input type="text" name="specialization" value="<?php echo isset($freelancer_data['specialization']) ? $freelancer_data['specialization'] : ''; ?>">
            <label>Биография</label>
            <textarea name="bio"><?php echo isset($freelancer_data['bio']) ? $freelancer_data['bio'] : ''; ?></textarea>
            <label>Навыки</label>
            <textarea name="skills"><?php echo isset($freelancer_data['skills']) ? $freelancer_data['skills'] : ''; ?></textarea>
            <label>Цена за 1 час (₽)</label>
            <input type="number" name="price" step="0.01" value="<?php echo isset($freelancer_data['price']) ? $freelancer_data['price'] : ''; ?>">
            <label>Фотография профиля</label>
            <input type="file" name="profile_photo">

            <label>Новый пароль</label>
            <input type="password" name="new_password">

            <button type="submit">Сохранить изменения</button>
        </form>
    </div>
</div>


<!-- Модальное окно для предложений -->
<div id="offersModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <span class="close-btn" id="closeOffersModal">&times;</span>
        <h2>Предложения заказов</h2>
        <div id="offersContainer">
            <p>Загрузка предложений...</p>
        </div>
    </div>
</div>


<script>
document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('mobileMenu').classList.toggle('active');
        });


   const editProfileModal = document.getElementById('editProfileModal');

function openEditProfileModal() {
  editProfileModal.classList.add('show');
}

function closeEditProfileModal() {
  editProfileModal.classList.remove('show');
}


// Обработчики для кнопок
document.querySelector('.edit-profile-btn').addEventListener('click', openEditProfileModal);

// Закрывающие кнопки
document.querySelector('#editProfileModal .close-btn').addEventListener('click', closeEditProfileModal);

window.addEventListener('click', (event) => {
  if(event.target === editProfileModal) {
    closeEditProfileModal();
  }
});
</script>

<script>
const offersModal = document.getElementById('offersModal');
const openOffersBtn = document.getElementById('openOffersBtn');
const closeOffersModal = document.getElementById('closeOffersModal');

if (openOffersBtn) {
    openOffersBtn.addEventListener('click', () => {
        offersModal.classList.add('show');
        loadOffers();
    });
}

closeOffersModal.addEventListener('click', () => {
    offersModal.classList.remove('show');
});

window.addEventListener('click', (e) => {
    if (e.target === offersModal) {
        offersModal.classList.remove('show');
    }
});

function loadOffers() {
    const container = document.getElementById('offersContainer');
    container.innerHTML = '<p>Загрузка предложений...</p>';

    fetch('offers/get_offers.php')  // создадим этот файл для загрузки предложений
        .then(res => res.json())
        .then(data => {
            if (data.length === 0) {
                container.innerHTML = '<p style="margin-top: 20px;">Нет предложений</p>';
                return;
            }
            container.innerHTML = '';
            data.forEach(offer => {
                const div = document.createElement('div');
                div.className = 'offer-card';
                div.innerHTML = `
                    <h3>${offer.title}</h3>
                    <p>${offer.description}</p>
                    <p><strong>Бюджет: </strong>₽${offer.budget}</p>
                    <button class="accept-offer-btn" data-offer-id="${offer.offer_id}" data-customer-id="${offer.customer_id}">Выполнять</button>
                    <button class="decline-btn" onclick="declineOffer(${offer.offer_id}, ${offer.customer_id}, event)">Отказаться</button>
                `;
                container.appendChild(div);
            });

            document.querySelectorAll('.accept-offer-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const offerId = btn.getAttribute('data-offer-id');
                    const customerId = btn.getAttribute('data-customer-id');
                    acceptOffer(offerId, customerId);
                });
            });
        })
        .catch(() => {
            container.innerHTML = '<p>Ошибка загрузки предложений</p>';
        });
}

function acceptOffer(offerId, customerId) {
    if (!confirm('Назначить этот заказ себе?')) return;

    fetch('offers/accept_offer.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `offer_id=${offerId}&customer_id=${customerId}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Заказ успешно принят!');
            offersModal.classList.remove('show');
            location.reload();
        } else {
            alert(data.message || 'Ошибка');
        }
    })
    .catch(() => {
        alert('Ошибка сети');
    });
}

function declineOffer(offerId, customerId, event) {
    event.stopPropagation();

    if (!confirm("Вы уверены, что хотите отклонить это предложение?")) {
        return;
    }

    const formData = new FormData();
    formData.append('offer_id', offerId);
    formData.append('customer_id', customerId);

    fetch('offers/decline_offer.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            alert('Предложение успешно отклонено.');
            loadOffers();
        } else {
            alert('Ошибка при отклонении предложения.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при отклонении предложения.');
    });
}
</script>


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</body>
</html>