<?php
// /api-data/tariffs.php
function handleTariffRequest($action) {
    switch ($action) {
        case 'get_tariffs':
            handleGetTariffs();
            break;
        case 'create_payment':
            handleCreatePayment();
            break;
        case 'can_create_presentation':
            handleCanCreatePresentation();
            break;
        default:
            jsonResponse(['error' => 'Неизвестное действие'], 400);
    }
}

function handleGetTariffs() {
    $tariffModel = new Tariff();
    $tariffs = $tariffModel->getAll();
    
    // Добавляем информацию о текущем тарифе пользователя
    $userTariff = null;
    if (isset($_SESSION['user_id'])) {
        $userTariff = $tariffModel->getUserTariff($_SESSION['user_id']);
    }
    
    jsonResponse([
        'success' => true,
        'tariffs' => $tariffs,
        'current_tariff_id' => $userTariff ? $userTariff['id'] : null
    ]);
}

function handleCreatePayment() {
    requireAuth();
    
    $tariffId = $_POST['tariff_id'] ?? 0;
    if (!$tariffId) {
        jsonResponse(['error' => 'Не указан тариф'], 400);
    }
    
    $tariffModel = new Tariff();
    $tariff = $tariffModel->getById($tariffId);
    
    if (!$tariff) {
        jsonResponse(['error' => 'Тариф не найден'], 404);
    }
    
    $userId = $_SESSION['user_id'];
    
    // Если тариф бесплатный - сразу активируем
    if ($tariff['price'] == 0) {
        $db = Database::getInstance()->getConnection();
        
        // Рассчитываем дату окончания (если duration_days = 0 - бессрочно)
        $expiresAt = $tariff['duration_days'] > 0 
            ? date('Y-m-d H:i:s', strtotime("+" . $tariff['duration_days'] . " days"))
            : null;
        
        $stmt = $db->prepare("
            UPDATE users 
            SET tariff_id = ?, 
                tariff_expires_at = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$tariffId, $expiresAt, $userId]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Тариф успешно активирован'
        ]);
    } else {
        // Для платных тарифов создаем платеж
        $db = Database::getInstance()->getConnection();
        
        // Генерируем ID платежа
        $paymentId = 'pay_' . time() . '_' . bin2hex(random_bytes(4));
        
        $stmt = $db->prepare("
            INSERT INTO payments (user_id, tariff_id, amount, status, payment_id, created_at)
            VALUES (?, ?, ?, 'pending', ?, NOW())
        ");
        
        $stmt->execute([
            $userId,
            $tariffId,
            $tariff['price'],
            $paymentId
        ]);
        
        // В реальном проекте здесь была бы интеграция с платежной системой (ЮKassa и т.д.)
        // Для демо создаем тестовый URL оплаты
        
        $paymentUrl = '/payment/process.php?id=' . $paymentId;
        
        jsonResponse([
            'success' => true,
            'payment_id' => $paymentId,
            'payment_url' => $paymentUrl,
            'amount' => $tariff['price']
        ]);
    }
}

function handleCanCreatePresentation() {
    requireAuth();
    
    $userId = $_SESSION['user_id'];
    $tariffModel = new Tariff();
    $canCreate = $tariffModel->canCreatePresentation($userId);
    
    jsonResponse([
        'success' => true,
        'can_create' => $canCreate
    ]);
}
?>