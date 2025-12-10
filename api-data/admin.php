<?php
// /api-data/admin.php
function handleAdminRequest($action) {
    switch ($action) {
        case 'admin_impersonate':
            handleAdminImpersonate();
            break;
        case 'admin_update_payment':
            handleAdminUpdatePayment();
            break;
        default:
            jsonResponse(['error' => 'Неизвестное действие администратора'], 400);
    }
}

function handleAdminImpersonate() {
    requireAuth();
    requireAdmin();
    
    $userId = $_POST['user_id'] ?? 0;
    
    if (!$userId) {
        jsonResponse(['error' => 'Не указан ID пользователя'], 400);
    }
    
    $userModel = new User();
    $user = $userModel->find($userId);
    
    if (!$user) {
        jsonResponse(['error' => 'Пользователь не найден'], 404);
    }
    
    // Запоминаем админа, под кем входим
    $_SESSION['admin_id'] = $_SESSION['user_id'];
    $_SESSION['impersonated'] = true;
    $_SESSION['user_id'] = $user['id'];
    
    // Сохраняем в сессии информацию о пользователе
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role_id'];
    
    jsonResponse(['success' => true]);
}

function handleAdminUpdatePayment() {
    requireAuth();
    requireAdmin();
    
    $paymentId = $_POST['payment_id'] ?? 0;
    $status = $_POST['status'] ?? '';
    
    if (!$paymentId || !in_array($status, ['succeeded', 'canceled'])) {
        jsonResponse(['error' => 'Неверные параметры'], 400);
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Получаем информацию о платеже
    $stmt = $db->prepare("
        SELECT p.*, t.duration_days, u.id as user_id
        FROM payments p
        JOIN tariffs t ON p.tariff_id = t.id
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch();
    
    if (!$payment) {
        jsonResponse(['error' => 'Платеж не найден'], 404);
    }
    
    // Обновляем статус платежа
    $stmt = $db->prepare("
        UPDATE payments 
        SET status = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$status, $paymentId]);
    
    // Если платеж успешен, активируем тариф для пользователя
    if ($status === 'succeeded') {
        // Рассчитываем дату окончания тарифа
        $expiresAt = date('Y-m-d H:i:s', strtotime("+" . $payment['duration_days'] . " days"));
        
        $stmt = $db->prepare("
            UPDATE users 
            SET tariff_id = ?, 
                tariff_expires_at = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$payment['tariff_id'], $expiresAt, $payment['user_id']]);
        
        // Создаем уведомление для пользователя
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, type, title, message, created_at) 
            VALUES (?, 'payment', 'Оплата успешна', 'Ваш тариф успешно активирован!', NOW())
        ");
        $stmt->execute([$payment['user_id']]);
    }
    
    jsonResponse(['success' => true]);
}

// Вспомогательная функция для проверки прав администратора
function requireAdmin() {
    if (!isAdmin()) {
        jsonResponse(['error' => 'Доступ запрещен. Требуются права администратора'], 403);
    }
}

function isAdmin() {
    $userId = getCurrentUserId();
    if (!$userId) return false;
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT role_id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    return $user && $user['role_id'] == ADMIN_ROLE_ID;
}
?>