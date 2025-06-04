<?php
function getProfileUrl() {
    if (!isset($_SESSION['role_id'])) return 'login.php';
    return $_SESSION['role_id'] == 2 ? 'Fprofile.php' : 'Cprofile.php';
}


/**
 * Получает ID фрилансера по ID пользователя
 */
function getFreelancerId($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT freelancer_id FROM freelancers WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['freelancer_id'];
    }
    return null;
}

/**
 * Получает ID заказчика по ID пользователя
 */
function getCustomerId($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT customer_id FROM customers WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['customer_id'];
    }
    return null;
}


function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>
