<?php
include('db_connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lastname = $_POST['lastname'];
    $firstname = $_POST['firstname'];
    $email = $_POST['email'];
    $password_raw = $_POST['password'];

    if (strlen($password_raw) < 6) {
        header('Location: register.php?error=Пароль должен содержать минимум 6 символов');
        exit();
    }

    $password = password_hash($password_raw, PASSWORD_DEFAULT);
    $role = $_POST['role'];

    if ($role === 'freelancer') {
        $role_id = 2;
    } elseif ($role === 'customer') {
        $role_id = 1;
    } else {
        header('Location: register.php?error=Некорректная роль');
        exit();
    }

    $check_sql = "SELECT * FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('s', $email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        header('Location: register.php?error=Пользователь с таким email уже зарегистрирован.');
        exit();
    }

    // Сначала регистрируем пользователя
    $sql_user = "INSERT INTO users (role_id, lastname, firstname, email, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param('issss', $role_id, $lastname, $firstname, $email, $password);

    if ($stmt_user->execute()) {
        $user_id = $conn->insert_id;

        // Обработка фото с QR-кодом
        if (!empty($_FILES['payment_photo']['name'])) {
            $qr_filename = basename($_FILES['payment_photo']['name']);
            move_uploaded_file($_FILES['payment_photo']['tmp_name'], "../images/ОПЛАТА/$qr_filename");

            $sql_qr = "UPDATE users SET payment_photo = ? WHERE user_id = ?";
            $stmt_qr = $conn->prepare($sql_qr);
            $stmt_qr->bind_param("si", $qr_filename, $user_id);
            $stmt_qr->execute();
        }

        // Запись в роли
        if ($role === 'freelancer') {
            $sql_freelancer = "INSERT INTO freelancers (user_id) VALUES (?)";
            $stmt_freelancer = $conn->prepare($sql_freelancer);
            $stmt_freelancer->bind_param('i', $user_id);
            $stmt_freelancer->execute();
        } elseif ($role === 'customer') {
            $sql_customer = "INSERT INTO customers (user_id) VALUES (?)";
            $stmt_customer = $conn->prepare($sql_customer);
            $stmt_customer->bind_param('i', $user_id);
            $stmt_customer->execute();
        }

        header('Location: login.php');
        exit();
    } else {
        header('Location: register.php?error=Ошибка при регистрации. Попробуйте снова.');
        exit();
    }
}
?>
