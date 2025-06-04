<?php
session_start();
include('db_connection.php');
include('functions.php');

// Получаем данные пользователя
$user_id = $_SESSION['user_id'] ?? null;
$role_id = $_SESSION['role_id'] ?? null; // Получаем роль пользователя

// Фильтрация заказов
$nazvanie = isset($_GET['nazvanie']) ? $_GET['nazvanie'] : '';
$min_budget = isset($_GET['min_budget']) ? $_GET['min_budget'] : '';
$max_budget = isset($_GET['max_budget']) ? $_GET['max_budget'] : '';
$days_left = isset($_GET['days_left']) ? (int)$_GET['days_left'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Строим запрос с учетом фильтров
$sql_orders = "SELECT * FROM orders WHERE status = 'Ожидает исполнителя'";

// Добавляем условия фильтрации
if ($nazvanie) {
    $sql_orders .= " AND title LIKE ?";
}
if ($min_budget) {
    $sql_orders .= " AND budget >= ?";
}
if ($max_budget) {
    $sql_orders .= " AND budget <= ?";
}
if ($days_left) {
    $current_date = date('Y-m-d');
    $sql_orders .= " AND DATEDIFF(deadline, '$current_date') <= ?";
}
if ($category) {
    $sql_orders .= " AND category LIKE ?";
}

// Сортировка по дате
$sql_orders .= " ORDER BY created_at DESC";

// Пагинация
$per_page = 4;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

$sql_orders .= " LIMIT $per_page OFFSET $offset";

// Подготавливаем запрос
$stmt = $conn->prepare($sql_orders);

// Связываем параметры
$params = [];
$types = '';

if ($nazvanie) {
    $params[] = "%" . $nazvanie . "%";
    $types .= 's';
}
if ($min_budget) {
    $params[] = $min_budget;
    $types .= 'd';
}
if ($max_budget) {
    $params[] = $max_budget;
    $types .= 'd';
}
if ($days_left) {
    $params[] = $days_left;
    $types .= 'i';
}
if ($category) {
    $params[] = "%" . $category . "%";
    $types .= 's';
}

// Связываем параметры, если они есть
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}

// Выполняем запрос для выборки заказов
$stmt->execute();
$result_orders = $stmt->get_result();

// Получаем общее количество заказов для пагинации
$total_orders_sql = "SELECT COUNT(*) AS total FROM orders WHERE status = 'Ожидает исполнителя'";
if ($nazvanie) $total_orders_sql .= " AND title LIKE ?";
if ($min_budget) $total_orders_sql .= " AND budget >= ?";
if ($max_budget) $total_orders_sql .= " AND budget <= ?";
if ($days_left) {
    $current_date = date('Y-m-d');
    $total_orders_sql .= " AND DATEDIFF(deadline, '$current_date') <= ?";
}
if ($category) $total_orders_sql .= " AND category LIKE ?";

$total_stmt = $conn->prepare($total_orders_sql);
if (!empty($types)) {
    $total_stmt->bind_param($types, ...$params);
}
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_orders = $total_result->fetch_assoc()['total'];

$total_pages = ceil($total_orders / $per_page);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список заказов</title>
    <link rel="stylesheet" href="../styles/orders.css">
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
    <h1>Список заказов</h1>
    
    <div class="main-container">
        <div class="filter-container">
            <form method="GET">
                <label for="nazvanie">Название</label>
                <input type="text" name="nazvanie" id="nazvanie" value="<?= htmlspecialchars($nazvanie) ?>">

                <label for="category">Категория</label>
                <input type="text" name="category" id="category" value="<?= htmlspecialchars($category) ?>">

                <label for="min_budget">Бюджет от</label>
                <input type="number" name="min_budget" id="min_budget" value="<?= htmlspecialchars($min_budget) ?>">

                <label for="max_budget">Бюджет до</label>
                <input type="number" name="max_budget" id="max_budget" value="<?= htmlspecialchars($max_budget) ?>">

                <label for="days_left">Дней до дедлайна (макс.)</label>
                <input type="number" name="days_left" id="days_left" value="<?= htmlspecialchars($days_left) ?>">

                <button type="submit">Применить фильтр</button>
            </form>
        </div>

        <div class="orders-container">
            <div class="order-list">
                <?php while ($order = $result_orders->fetch_assoc()) { 
                $current_date = new DateTime();
                $deadline_date = new DateTime($order['deadline']);
                $days_remaining = $current_date->diff($deadline_date)->days;
                $days_remaining = $deadline_date > $current_date ? $days_remaining : 0;
                
                // Проверяем, можно ли кликать на карточку
                $clickable = isset($_SESSION['user_id']);
            ?>
            <div class="order-card <?= $clickable ? 'clickable' : '' ?>" <?= $clickable ? 'onclick="location.href=\'order_details.php?id='.$order['order_id'].'\'"' : '' ?>>
                <div class="order-main-info">
                    <h2><?= htmlspecialchars($order['title']) ?></h2>
                    <p>Категория: <?= htmlspecialchars($order['category']) ?></p>
                    <p>Срок: До <?= date('d.m.Y', strtotime($order['deadline'])) ?> (осталось <?= $days_remaining ?> дн.)</p>
                    <p>Статус: <?= $order['status'] ?></p>
                </div>
                <div class="order-price">
                    <span class="budget">₽<?= number_format($order['budget'], 0, ',', ' ') ?></span>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <p class="auth-notice">Для просмотра подробностей авторизуйтесь</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php } ?>
            </div>

            <?php if (isset($_SESSION['user_id']) && $role_id == 1): ?>
                <button class="zakaz-btn" id="openOrderModalBtn">Подача нового заказа</button>
            <?php elseif (!isset($_SESSION['user_id'])): ?>
                <div class="guest-notice">
                    <p>Хотите разместить заказ? <a href="register.php">Зарегистрируйтесь</a> как заказчик.</p>
                    <p>Или <a href="login.php">войдите</a> в свой аккаунт.</p>
                </div>
            <?php endif; ?>

            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="orders.php?page=<?= $page - 1 ?>&nazvanie=<?= urlencode($nazvanie) ?>&category=<?= urlencode($category) ?>&min_budget=<?= $min_budget ?>&max_budget=<?= $max_budget ?>&days_left=<?= $days_left ?>" class="prev-page">« Назад</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="orders.php?page=<?= $i ?>&nazvanie=<?= urlencode($nazvanie) ?>&category=<?= urlencode($category) ?>&min_budget=<?= $min_budget ?>&max_budget=<?= $max_budget ?>&days_left=<?= $days_left ?>" class="page-link <?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="orders.php?page=<?= $page + 1 ?>&nazvanie=<?= urlencode($nazvanie) ?>&category=<?= urlencode($category) ?>&min_budget=<?= $min_budget ?>&max_budget=<?= $max_budget ?>&days_left=<?= $days_left ?>" class="next-page">Вперед »</a>
                <?php endif; ?>
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

    const orderModal = document.getElementById('orderModal');

    function openOrderModal() {
    orderModal.classList.add('show');
    }

    function closeOrderModal() {
    orderModal.classList.remove('show');
    }

    // Обработчики для кнопок
    document.querySelector('.zakaz-btn').addEventListener('click', openOrderModal);

    // Закрывающие кнопки
    document.querySelector('#orderModal .close-btn').addEventListener('click', closeOrderModal);

    // Если хочешь закрывать модалки кликом вне содержимого
    window.addEventListener('click', (event) => {
    if(event.target === orderModal) {
        closeOrderModal();
    }
    });
</script>
</body>
</html>