<?php
session_start();
include('db_connection.php');
include('functions.php');

// Получаем данные пользователя
$user_id = $_SESSION['user_id'] ?? null;
$role_id = $_SESSION['role_id'] ?? null;

// Поиск по имени и фамилии
$search_query = $_GET['search_query'] ?? '';
$search_param = '%' . $search_query . '%';

// Пагинация
$per_page = 6;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Получаем фрилансеров с ограничением
$sql = "
    SELECT 
        f.freelancer_id,
        f.specialization,
        f.skills,
        f.completed_orders,
        f.price,
        u.firstname,
        u.lastname,
        u.profile_photo
    FROM freelancers f
    JOIN users u ON f.user_id = u.user_id
    WHERE CONCAT(u.firstname, ' ', u.lastname) LIKE ?
    ORDER BY f.completed_orders DESC
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $search_param, $per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Подсчёт общего количества фрилансеров
$count_sql = "
    SELECT COUNT(*) as total
    FROM freelancers f
    JOIN users u ON f.user_id = u.user_id
    WHERE CONCAT(u.firstname, ' ', u.lastname) LIKE ?
";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("s", $search_param);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_freelancers = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_freelancers / $per_page);
?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Фрилансеры</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/freelancers.css">
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
    <h1>Фрилансеры</h1>

    <div class="filter-container">
        <form method="GET">
            <input type="text" name="search_query" placeholder="Поиск по имени..." value="<?= htmlspecialchars($search_query) ?>">
            <button type="submit">Найти</button>
        </form>
    </div>

    <div class="freelancer-list">
        <?php while ($row = $result->fetch_assoc()): ?>
            <?php $clickable = isset($_SESSION['user_id']); ?>
            <div class="freelancer-card <?= $clickable ? 'clickable' : '' ?>"
                 <?= $clickable ? "onclick=\"location.href='freelancer_details.php?id={$row['freelancer_id']}'\"" : '' ?>>
                <img class="freelancer-photo" src="../images/<?= htmlspecialchars($row['profile_photo']) ?>" alt="Фото фрилансера">
                <div class="freelancer-info">
                    <h3><?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?></h3>
                    <p class="specialization"><?= htmlspecialchars($row['specialization']) ?></p>
                    <p class="rating">Выполнено заказов: <?php echo $row['completed_orders']; ?></p>
                    <p class="price">₽<?= number_format($row['price'], 0, ',', ' ') ?> / час</p>
                </div>
                <?php if (!$clickable): ?>
                    <p class="auth-notice">Для просмотра подробностей авторизуйтесь</p>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>


    <div class="pagination">
    <?php if ($page > 1): ?>
        <a href="freelancers.php?page=<?= $page - 1 ?>&search_query=<?= urlencode($search_query) ?>" class="prev-page">« Назад</a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="freelancers.php?page=<?= $i ?>&search_query=<?= urlencode($search_query) ?>" class="page-link <?= ($i == $page) ? 'active' : '' ?>">
            <?= $i ?>
        </a>
    <?php endfor; ?>

    <?php if ($page < $total_pages): ?>
        <a href="freelancers.php?page=<?= $page + 1 ?>&search_query=<?= urlencode($search_query) ?>" class="next-page">Вперёд »</a>
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
