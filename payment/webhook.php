<?php
// payment/webhook.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Установите библиотеку YooKassa: composer require yoomoney/yookassa-sdk-php
require_once ROOT_PATH . '/vendor/autoload.php';

// Логируем получение webhook
file_put_contents(LOGS_DIR . 'yookassa_webhook.log', date('Y-m-d H:i:s') . " Webhook received\n", FILE_APPEND);

$body = @file_get_contents('php://input');
$event = json_decode($body, true);

if (!$event) {
    http_response_code(400);
    exit;
}

// Проверяем подпись
if (!verifyYookassaSignature($body)) {
    http_response_code(401);
    exit;
}

$paymentId = $event['object']['id'] ?? null;
$status = $event['object']['status'] ?? null;

if ($paymentId && $status === 'succeeded') {
    // Обновляем статус платежа в БД
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT p.*, u.id as user_id, p.tariff_id 
        FROM payments p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.payment_id = ? AND p.status = 'pending'
    ");
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch();
    
    if ($payment) {
        // Обновляем статус платежа
        $stmt = $db->prepare("
            UPDATE payments 
            SET status = 'completed', 
                completed_at = NOW() 
            WHERE payment_id = ?
        ");
        $stmt->execute([$paymentId]);
        
        // Обновляем тариф пользователя
        $stmt = $db->prepare("UPDATE users SET tariff_id = ? WHERE id = ?");
        $stmt->execute([$payment['tariff_id'], $payment['user_id']]);
        
        // Отправляем уведомление пользователю
        sendPaymentSuccessEmail($payment['user_id'], $payment['tariff_id']);
    }
}

http_response_code(200);

function verifyYookassaSignature($body) {
    $signature = $_SERVER['HTTP_CONTENT_SIGNATURE'] ?? '';
    
    if (empty($signature)) {
        return false;
    }
    
    // Извлекаем подпись из заголовка
    $signature = str_replace('sha256=', '', $signature);
    $calculatedSignature = hash_hmac('sha256', $body, YOOKASSA_SECRET_KEY);
    
    return hash_equals($calculatedSignature, $signature);
}

function sendPaymentSuccessEmail($userId, $tariffId) {
    // Реализация отправки email
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT u.email, u.name, t.name as tariff_name 
        FROM users u 
        JOIN tariffs t ON t.id = ? 
        WHERE u.id = ?
    ");
    $stmt->execute([$tariffId, $userId]);
    $data = $stmt->fetch();
    
    if ($data) {
        $subject = 'Оплата тарифа успешно завершена - ' . APP_NAME;
        $message = "
            <h2>Здравствуйте, " . $data['name'] . "!</h2>
            <p>Ваш тариф был успешно обновлен на <strong>" . $data['tariff_name'] . "</strong>.</p>
            <p>Теперь вы можете создавать новые презентации с расширенными возможностями.</p>
            <p>Спасибо за использование нашего сервиса!</p>
        ";
        
        // Используйте PHPMailer или другую библиотеку для отправки email
        // mail($data['email'], $subject, $message, headers);
    }
}