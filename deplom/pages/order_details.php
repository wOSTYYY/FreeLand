<?php
session_start();
include('db_connection.php');
include('functions.php');


// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];

// Получаем ID заказа
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id == 0) {
    header("Location: orders.php");
    exit();
}

// 1. Получаем основные данные заказа
$order = [];
$stmt = $conn->prepare("
    SELECT o.*, c.user_id as customer_user_id, f.user_id as freelancer_user_id 
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.customer_id
    LEFT JOIN freelancers f ON o.freelancer_id = f.freelancer_id
    WHERE o.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows === 0) {
    header("Location: orders.php");
    exit();
}

$order = $order_result->fetch_assoc();

$is_paid = !empty($order['is_paid']);

// 2. Получаем полную информацию о заказчике
$customer_info = [];
if (!empty($order['customer_user_id'])) {
    $stmt = $conn->prepare("
        SELECT u.user_id, u.firstname, u.lastname, u.profile_photo, u.bio, 
               c.company_name, c.contact_info
        FROM users u
        LEFT JOIN customers c ON u.user_id = c.user_id
        WHERE u.user_id = ?
    ");
    $stmt->bind_param("i", $order['customer_user_id']);
    $stmt->execute();
    $customer_result = $stmt->get_result();
    
    if ($customer_result->num_rows > 0) {
        $customer_info = $customer_result->fetch_assoc();
    }
}

// 3. Получаем информацию о фрилансере (если назначен)
$freelancer_info = [];
if (!empty($order['freelancer_user_id'])) {
    $stmt = $conn->prepare("
        SELECT u.user_id, u.firstname, u.lastname, u.profile_photo,
               f.specialization, f.completed_orders
        FROM users u
        LEFT JOIN freelancers f ON u.user_id = f.user_id
        WHERE u.user_id = ?
    ");
    $stmt->bind_param("i", $order['freelancer_user_id']);
    $stmt->execute();
    $freelancer_result = $stmt->get_result();
    
    if ($freelancer_result->num_rows > 0) {
        $freelancer_info = $freelancer_result->fetch_assoc();
    }
}

// Проверяем роли и принадлежность заказа
$is_owner = ($role_id == 1 && isset($order['customer_user_id']) && $order['customer_user_id'] == $user_id);
$is_assigned_freelancer = ($role_id == 2 && !empty($order['freelancer_user_id']) && $order['freelancer_user_id'] == $user_id);

// Получаем количество откликов
$responses_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM order_responses WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $responses_count = $result->fetch_assoc()['count'];
}

// Получаем файлы заказа
$order_files = [];
$stmt = $conn->prepare("SELECT * FROM order_files WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_files = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$freelancer_uploaded_files = [];
if ($is_assigned_freelancer && $order['status'] === 'Выполнено') {
    $stmt = $conn->prepare("SELECT * FROM order_files WHERE order_id = ? AND freelancer_id = ?");
    $stmt->bind_param("ii", $order_id, $freelancer_id);
    $stmt->execute();
    $freelancer_uploaded_files = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $has_uploaded_files = count($freelancer_uploaded_files) > 0;
} else {
    $has_uploaded_files = false;
}



// Получаем комментарии к заказу, сортируем по дате создания (по возрастанию)
$comments = [];
$stmt = $conn->prepare("SELECT c.*, u.firstname, u.lastname, u.profile_photo FROM order_comments c JOIN users u ON c.user_id = u.user_id WHERE c.order_id = ? ORDER BY c.created_at ASC");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$comments_result = $stmt->get_result();
if ($comments_result) {
    $comments = $comments_result->fetch_all(MYSQLI_ASSOC);
}


?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($order['title'] ?? 'Заказ') ?> | Freeland</title>
    <link rel="stylesheet" href="../styles/order_details.css">
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

<main class="order-details-container">
    <div class="order-header">
        <h1><?= htmlspecialchars($order['title'] ?? 'Название заказа') ?></h1>
        <div class="order-meta">
            <span class="budget">₽<?= isset($order['budget']) ? number_format($order['budget'], 0, ',', ' ') : '0' ?></span>
            <span class="deadline">До <?= !empty($order['deadline']) ? date('d.m.Y', strtotime($order['deadline'])) : 'дата не указана' ?></span>
            <span class="category"><?= htmlspecialchars($order['category'] ?? 'Категория не указана') ?></span>
        </div>
    </div>

    <div class="order-content">
        <section class="order-description">
            <h2>Описание заказа</h2>
            <div class="description-text">
                <?= !empty($order['description']) ? nl2br(htmlspecialchars($order['description'])) : 'Описание отсутствует' ?>
            </div>
            
            <?php if (!empty($order_files)): ?>
            <div class="order-files">
                <h3>Прикрепленные файлы</h3>
                <div class="file-list">
                    <?php foreach ($order_files as $file): 
                        $file_path = "uploads/order_$order_id/" . htmlspecialchars($file['file_name']);
                        $file_exists = file_exists($file_path);
                        $file_extension = pathinfo($file['file_name'], PATHINFO_EXTENSION);
                    ?>
                    <div class="file-item">
                        <div class="file-icon">
                            <?php 
                            $icon = "../images/file-icons/$file_extension.png";
                            if (!file_exists($icon)) {
                                $icon = "../images/file-icons/default.png";
                            }
                            ?>
                            <img src="<?= $icon ?>" alt="<?= $file_extension ?>">
                        </div>
                        <div class="file-info">
                            <div class="file-name"><?= htmlspecialchars($file['file_name']) ?></div>
                            <?php if ($file_exists): ?>
                                <div class="file-size"><?= formatFileSize(filesize($file_path)) ?></div>
                            <?php else: ?>
                                <div class="file-size">Файл недоступен</div>
                            <?php endif; ?>
                        </div>
                        <div class="file-actions">
                        <?php if ($file_exists && ($is_assigned_freelancer || ($is_owner && $order['status'] == 'Оплачено'))): ?>
                            <button type="button" class="delete-file-btn" data-file-id="<?= $file['file_id'] ?>" data-file-path="<?= $file_path ?>">×</button>
                            <a href="<?= $file_path ?>" download class="btn btn-small">Скачать</a>
                        <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="order-comments">
                    <h3>Комментарии</h3>
                    <?php if (count($comments) === 0): ?>
                        <p>Комментариев пока нет.</p>
                    <?php else: ?>
                            <?php 
                            $last_comment = end($comments); // получаем последний комментарий из массива
                            ?>
                            <div class="comment-item">
                                 <div class="comment-text"><?= nl2br(htmlspecialchars($last_comment['comment'])) ?></div>
                            </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($is_owner && $order['status'] == 'Выполнено'): ?>
            <div class="payment-section">
                <h3>Оплата заказа</h3>
                <?php if (isset($_GET['payment']) && $_GET['payment'] == 'success'): ?>
                    <div class="alert alert-success">Оплата прошла успешно! Теперь вы можете скачать файлы.</div>
                <?php endif; ?>
                <p>Сумма к оплате: <strong>₽<?= number_format($order['budget'], 0, ',', ' ') ?></strong></p>
                <a href="payment.php?order_id=<?= $order_id ?>" class="btn btn-payment">Перейти к оплате</a>
            </div>
        <?php endif; ?>
                <?php if (isset($_GET['payment'])): ?>
                    <?php if ($_GET['payment'] == 'waiting_confirmation'): ?>
                        <div style="margin-top: 30px" class="alert alert-info">Ждём подтверждения от исполнителя.</div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($is_assigned_freelancer && $order['status'] == 'Выполнено'): ?>
                    <div class="edit-response-form">
                        <h3>Редактировать ответ</h3>
                        <form action="edit_order_response.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="order_id" value="<?= $order_id ?>">
                            
                            <div class="form-group">
                                <label>Добавить дополнительные файлы:</label>
                                <input type="file" name="additional_files[]" multiple>
                            </div>
                            
                            <div class="form-group">
                                <label>Комментарий:</label>
                                <textarea name="additional_comment"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-edit">Обновить ответ</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            

            
        </section>

        <aside class="order-sidebar">
            <!-- Информация о заказчике -->
            <section class="customer-info">
                <h2>Информация о заказчике</h2>
                <?php if (!empty($customer_info)): ?>
                <div class="customer-card">
                    <?php if (!empty($customer_info['profile_photo'])): ?>
                    <img src="../images/<?= htmlspecialchars($customer_info['profile_photo']) ?>" alt="Фото заказчика" class="customer-photo">
                    <?php endif; ?>
                    <div class="customer-details">
                        <h3><?= htmlspecialchars(($customer_info['firstname'] ?? '') . ' ' . ($customer_info['lastname'] ?? '')) ?></h3>
                        <?php if (!empty($customer_info['company_name'])): ?>
                        <p>Компания: <?= htmlspecialchars($customer_info['company_name']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($customer_info['bio'])): ?>
                        <p class="customer-bio"><?= htmlspecialchars($customer_info['bio']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($customer_info['contact_info'])): ?>
                        <p>Контакты: <?= htmlspecialchars($customer_info['contact_info']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <p>Информация о заказчике недоступна</p>
                <?php endif; ?>
            </section>

            <!-- Информация о фрилансере -->
            <?php if (!empty($freelancer_info)): ?>
            <section class="freelancer-info">
                <h2>Исполнитель</h2>
                <div class="freelancer-card">
                    <?php if (!empty($freelancer_info['profile_photo'])): ?>
                    <img src="../images/<?= htmlspecialchars($freelancer_info['profile_photo']) ?>" alt="Фото исполнителя" class="freelancer-photo">
                    <?php endif; ?>
                    <div class="freelancer-details">
                        <h3><?= htmlspecialchars(($freelancer_info['firstname'] ?? '') . ' ' . ($freelancer_info['lastname'] ?? '')) ?></h3>
                        <?php if (!empty($freelancer_info['specialization'])): ?>
                        <p>Специализация: <?= htmlspecialchars($freelancer_info['specialization']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($freelancer_info['rating'])): ?>
                        <p>Рейтинг: <?= number_format($freelancer_info['rating'], 1) ?> ★</p>
                        <?php endif; ?>
                        <?php if (!empty($freelancer_info['completed_orders'])): ?>
                        <p>Выполнено заказов: <?= $freelancer_info['completed_orders'] ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>
        </aside>
    </div>

    

            <!-- Кнопки действий -->
            <div class="order-actions">
                <?php if ($role_id == 2 && $is_assigned_freelancer && $is_paid && $order['status'] !== 'Оплачено'): ?>
                <form action="confirm_payment.php" method="POST" style="display:inline;">
                    <input type="hidden" name="order_id" value="<?= $order_id ?>">
                    <button type="submit" class="btn btn-confirm-payment">Получил деньги</button>
                </form>
            <?php endif; ?>
        <?php if ($is_owner): ?>
        <!-- Кнопки для заказчика -->
        <button class="btn btn-edit" id="editOrderBtn">Редактировать заказ</button>
        <a href="delete_order.php?id=<?= $order_id ?>" class="btn btn-delete" onclick="return confirmDelete()">Удалить заказ</a>

        <?php if (empty($order['freelancer_id'])): ?>
            <!-- Фрилансер НЕ назначен, показываем отклики -->
            <a href="order_responses.php?id=<?= $order_id ?>" class="btn btn-responses">Отклики (<?= $responses_count ?>)</a>
        <?php endif; ?>

        <?php if (!empty($order['freelancer_id'])): ?>
            <!-- Фрилансер назначен -->
            <button onclick="openChatModal(
                <?= $order_id ?>, 
                <?= $order['freelancer_id'] ?>, 
                <?= $order['customer_id'] ?>)" class="btn btn-chat">
                Чат с исполнителем
            </button>
        <?php endif; ?>

    <?php elseif ($role_id == 2): ?>
        <?php if ($is_assigned_freelancer): ?>
            <button onclick="openChatModal(
        <?= $order_id ?>, 
        <?= $order['freelancer_id'] ?? 0 ?>, 
        <?= $order['customer_id'] ?? 0 ?>)" class="btn btn-chat">
        <?= $is_owner ? 'Чат с исполнителем' : 'Чат с заказчиком' ?>
    </button>
            <!-- Кнопки для назначенного фрилансера -->
            <?php if ($order['status'] == 'В процессе' && !$has_uploaded_files): ?>
                <a href="complete_order.php?id=<?= $order_id ?>" class="btn btn-complete">Завершить заказ</a>
            <?php endif; ?>
        
        <?php elseif (($order['status'] ?? '') == 'Ожидает исполнителя'): ?>
            <?php 
            // Фрилансер ещё не назначен => может откликнуться
            $stmt = $conn->prepare("SELECT response_id FROM order_responses WHERE order_id = ? AND freelancer_id = ?");
            $stmt->bind_param("ii", $order_id, $user_id);
            $stmt->execute();
            $has_responded = $stmt->get_result()->num_rows > 0;
            ?>
            
            <?php if ($has_responded): ?>
                <button class="btn btn-disabled" disabled>Вы уже откликнулись</button>
            <?php else: ?>
                <button class="btn btn-respond" id="respondBtn">Откликнуться</button>
            <?php endif; ?>
        
        <?php endif; ?>

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

<div id="responsesModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <span class="close">&times;</span>
        <h2>Отклики на заказ</h2>
        <div class="responses-container" id="responsesContainer">
            <!-- Здесь будут загружаться карточки откликов -->
            <div class="loading">Загрузка откликов...</div>
        </div>
    </div>
</div>


<!-- Модальное окно редактирования заказа -->
<div id="editOrderModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Редактировать заказ</h2>
        <form id="editOrderForm" action="update_order.php" method="POST">
            <input type="hidden" name="order_id" value="<?= $order_id ?>">
            
            <div class="form-group">
                <label for="edit_title">Название заказа:</label>
                <input type="text" id="edit_title" name="title" value="<?= htmlspecialchars($order['title'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="edit_description">Описание:</label>
                <textarea id="edit_description" name="description" required><?= htmlspecialchars($order['description'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="edit_category">Категория:</label>
                <input type="text" id="edit_category" name="category" value="<?= htmlspecialchars($order['category'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="edit_budget">Бюджет (₽):</label>
                <input type="number" id="edit_budget" name="budget" min="1" step="0.01" value="<?= $order['budget'] ?? 0 ?>" required>
            </div>
            
            <div class="form-group">
                <label for="edit_deadline">Срок выполнения:</label>
                <input type="date" id="edit_deadline" name="deadline" value="<?= !empty($order['deadline']) ? date('Y-m-d', strtotime($order['deadline'])) : '' ?>" required>
            </div>
            
            <button type="submit" class="btn btn-submit">Сохранить изменения</button>
        </form>
    </div>
</div>

<script>
// Глобальные переменные
let currentConversationId = null;
let currentUserId = <?= $_SESSION['user_id'] ?? 0 ?>;

// Открытие модального окна чата
function openChatModal(orderId, freelancerId, customerId) {
    fetch(`chat.php?action=create&order_id=${orderId}&user1=${customerId}&user2=${freelancerId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) throw new Error(data.error);
            currentConversationId = data.conversation_id;

            document.getElementById('conversationId').value = currentConversationId;
            document.getElementById('conversationDebug').textContent = currentConversationId;
            document.getElementById('chatModal').classList.add('show');

            loadMessages();
            attachChatFormHandler(); // навешиваем обработчик только после открытия
        })
        .catch(error => {
            console.error('Ошибка открытия чата:', error);
            alert('Ошибка открытия чата: ' + error.message);
        });
}

// Загрузка сообщений
function loadMessages() {
    if (!currentConversationId) return;

    fetch(`chat.php?action=get_messages&conversation_id=${currentConversationId}`)
        .then(response => response.json())
        .then(messages => {
            const chatContainer = document.getElementById('chatMessages');
            chatContainer.innerHTML = '';

            if (messages.length === 0) {
                chatContainer.innerHTML = '<div class="no-messages">Нет сообщений</div>';
                return;
            }

            let currentDate = '';
            messages.forEach(msg => {
                if (msg.date !== currentDate) {
                    currentDate = msg.date;
                    const dateDiv = document.createElement('div');
                    dateDiv.className = 'message-date';
                    dateDiv.textContent = msg.date;
                    chatContainer.appendChild(dateDiv);
                }

                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${msg.sender_id == currentUserId ? 'my-message' : 'other-message'}`;
                messageDiv.innerHTML = `
                    <div class="message-sender">${msg.sender_name}</div>
                    <div class="message-text">${msg.text}</div>
                    <div class="message-time">${msg.time}</div>
                `;
                chatContainer.appendChild(messageDiv);
            });

            chatContainer.scrollTop = chatContainer.scrollHeight;
        })
        .catch(error => console.error('Ошибка загрузки сообщений:', error));
}

// Обработчик отправки формы чата
function attachChatFormHandler() {
    const chatForm = document.getElementById('chatForm');
    if (!chatForm) return;

    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const messageInput = document.getElementById('messageInput');
        const message = messageInput.value.trim();
        if (!message || !currentConversationId) return;

        const formData = new FormData();
        formData.append('conversation_id', currentConversationId);
        formData.append('message', message);

        fetch('chat.php?action=send_message', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) throw new Error(data.error);

            messageInput.value = '';
            loadMessages();
        })
        .catch(error => {
            console.error('Ошибка отправки:', error);
            alert('Ошибка отправки: ' + error.message);
        });
    });
}

// Закрытие модального окна чата
function closeChatModal() {
    document.getElementById('chatModal').classList.remove('show');
    currentConversationId = null;
}

// Автообновление чата каждые 3 секунды
setInterval(() => {
    if (currentConversationId) {
        loadMessages();
    }
}, 3000);

    document.addEventListener('DOMContentLoaded', function() {
    // Мобильное меню
    document.getElementById('mobileMenuBtn')?.addEventListener('click', function() {
        document.getElementById('mobileMenu').classList.toggle('active');
    });

    // Модальное окно
    const modal = document.getElementById('responseModal');
    const respondBtn = document.getElementById('respondBtn');
    const closeBtn = document.querySelector('.modal .close');

    // Показ модального окна
    if (respondBtn && modal) {
        respondBtn.addEventListener('click', function() {
            modal.style.display = 'block';
            document.body.classList.add('modal-open');
            modal.classList.add('show');
        });
    }

    // Скрытие модального окна
    if (closeBtn && modal) {
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
            modal.classList.remove('show');
        });
    }

    // Закрытие по клику вне окна
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
            modal.classList.remove('show');
        }
    });

    // Закрытие по ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modal.classList.contains('show')) {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
            modal.classList.remove('show');
        }
    });

    // Обработка формы
    const responseForm = document.getElementById('responseForm');
    if (responseForm) {
        responseForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    }
});
//_______________________________________________________________________________________

// Редактирование заказа
const editOrderBtn = document.getElementById('editOrderBtn');
const editOrderModal = document.getElementById('editOrderModal');
const editOrderClose = editOrderModal.querySelector('.close');

if (editOrderBtn) {
    editOrderBtn.addEventListener('click', function() {
        editOrderModal.style.display = 'block';
        document.body.classList.add('modal-open');
        editOrderModal.classList.add('show');
    });
}

if (editOrderClose) {
    editOrderClose.addEventListener('click', function() {
        editOrderModal.style.display = 'none';
        document.body.classList.remove('modal-open');
        editOrderModal.classList.remove('show');
    });
}

// Подтверждение удаления
function confirmDelete() {
    return confirm('Вы уверены, что хотите удалить этот заказ? Это действие нельзя отменить.');
}

// Обработка формы редактирования
const editOrderForm = document.getElementById('editOrderForm');
if (editOrderForm) {
    editOrderForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.redirected) {
                window.location.href = response.url;
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });
}

//________________________________________________________________________________________
// Модальное окно откликов
const responsesModal = document.getElementById('responsesModal');
const responsesBtn = document.querySelector('.btn-responses');
const responsesClose = responsesModal.querySelector('.close');

if (responsesBtn) {
    responsesBtn.addEventListener('click', function(e) {
        e.preventDefault();
        responsesModal.style.display = 'block';
        document.body.classList.add('modal-open');
        responsesModal.classList.add('show');
        
        // Загружаем отклики
        loadResponses(<?= $order_id ?>);
    });
}

if (responsesClose) {
    responsesClose.addEventListener('click', function() {
        responsesModal.style.display = 'none';
        document.body.classList.remove('modal-open');
        responsesModal.classList.remove('show');
    });
}

// Функция загрузки откликов
function loadResponses(orderId) {
    const container = document.getElementById('responsesContainer');
    container.innerHTML = '<div class="loading">Загрузка откликов...</div>';
    
    fetch(`../response/get_responses.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.length === 0) {
                container.innerHTML = '<div class="loading">Нет откликов на этот заказ</div>';
                return;
            }
            
            container.innerHTML = '';
            data.forEach(response => {
                const card = document.createElement('div');
                card.className = 'response-card';
                card.innerHTML = `
                    <div class="response-header" onclick="window.location.href='freelancer_profile.php?id=${response.freelancer_id}'">
                        <img src="../images/${response.profile_photo || 'default-avatar.png'}" alt="Аватар" class="response-avatar">
                        <div class="response-info">
                            <h3>${response.firstname} ${response.lastname}</h3>
                            <p>Выполнено заказов: ${response.completed_orders}</p>
                        </div>
                    </div>
                    <div class="response-description">
                        <p>${response.proposal}</p>
                    </div>
                    <div class="response-meta">
                        <span class="response-price">${response.price} ₽</span>
                        <span class="response-timeline">${response.timeline} дней</span>
                    </div>
                    <div class="response-actions">
                        <button class="assign-btn" onclick="assignFreelancer(${orderId}, ${response.freelancer_id}, ${response.response_id}, event)">Назначить</button>
                    </div>
                `;
                container.appendChild(card);
            });
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = '<div class="loading">Ошибка загрузки откликов</div>';
        });
}

// Функция назначения фрилансера
function assignFreelancer(orderId, freelancerId, responseId, event) {
    event.stopPropagation(); // Предотвращаем переход по ссылке
    
    if (!confirm(`Вы уверены, что хотите назначить этого фрилансера на заказ?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('freelancer_id', freelancerId);
    formData.append('response_id', responseId);
    
    fetch('../response/assign_freelancer.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
        } else if (response.ok) {
            return response.json();
        } else {
            throw new Error('Ошибка сервера');
        }
    })
    .then(data => {
        if (data.success) {
            alert('Фрилансер успешно назначен!');
            window.location.reload();
        } else {
            alert(data.message || 'Произошла ошибка');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при назначении фрилансера');
    });
}

// Обработка удаления файлов
document.querySelectorAll('.delete-file-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const fileId = this.getAttribute('data-file-id');
        const filePath = this.getAttribute('data-file-path');
        
        if (!confirm('Вы уверены, что хотите удалить этот файл?')) {
            return;
        }
        
        fetch('delete_order_file.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `file_id=${fileId}&file_path=${encodeURIComponent(filePath)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.closest('.file-item').remove();
            } else {
                alert('Ошибка при удалении файла');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Произошла ошибка');
        });
    });
});

</script>


<!-- Модальное окно отклика -->
<div id="responseModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Откликнуться на заказ</h2>
        <form id="responseForm" action="submit_response.php" method="POST">
            <input type="hidden" name="order_id" value="<?= $order_id ?>">
            
            <div class="form-group">
                <label for="proposal">Ваше предложение:</label>
                <textarea id="proposal" name="proposal" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="price">Ваша цена (₽):</label>
                <input type="number" id="price" name="price" min="1" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="timeline">Срок выполнения (дней):</label>
                <input type="number" id="timeline" name="timeline" min="1" required>
            </div>
            
            <button type="submit" class="btn btn-submit">Отправить отклик</button>
        </form>
    </div>
</div>


<!-- Модальное окно чата -->
<div id="chatModal" class="modal">
<span class="closer" onclick="closeChatModal()">&times;</span>
<small style="color:gray">Chat ID: <span id="conversationDebug"></span></small>
    <div class="modal-content chat-modal-content">
        <div class="chat-container">
            <div class="chat-messages" id="chatMessages">
                <!-- Сообщения будут загружены здесь -->
                <div class="no-messages">Выберите чат для начала общения</div>
            </div>
            <div class="chat-input">
                <form id="chatForm" method="POST">

                <input type="hidden" id="conversationId" name="conversation_id">
                <textarea id="messageInput" name="message" placeholder="Введите сообщение..." required></textarea>
                    <div class="chat-actions">

                        <button type="submit" class="btn-send">Отправить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>