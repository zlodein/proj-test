<?php
// /payment/process.php - обработка платежей (демо-версия)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    redirect('/auth/login.php');
}

$paymentId = $_GET['id'] ?? '';
if (!$paymentId) {
    setFlash('error', 'Не указан ID платежа');
    redirect('/index.php');
}

$db = Database::getInstance()->getConnection();

// Получаем информацию о платеже
$stmt = $db->prepare("
    SELECT p.*, t.name as tariff_name, t.price, u.name as user_name, u.email
    FROM payments p
    JOIN tariffs t ON p.tariff_id = t.id
    JOIN users u ON p.user_id = u.id
    WHERE p.payment_id = ? AND p.user_id = ?
");
$stmt->execute([$paymentId, $_SESSION['user_id']]);
$payment = $stmt->fetch();

if (!$payment) {
    setFlash('error', 'Платеж не найден');
    redirect('/index.php');
}

// Обработка успешной оплаты (демо-режим)
if (isset($_GET['success'])) {
    if ($payment['status'] !== 'succeeded') {
        // Обновляем статус платежа
        $stmt = $db->prepare("
            UPDATE payments 
            SET status = 'succeeded', updated_at = NOW()
            WHERE payment_id = ?
        ");
        $stmt->execute([$paymentId]);
        
        // Активируем тариф для пользователя
        $stmt = $db->prepare("
            SELECT duration_days FROM tariffs WHERE id = ?
        ");
        $stmt->execute([$payment['tariff_id']]);
        $tariff = $stmt->fetch();
        
        $expiresAt = date('Y-m-d H:i:s', strtotime("+" . $tariff['duration_days'] . " days"));
        
        $stmt = $db->prepare("
            UPDATE users 
            SET tariff_id = ?, 
                tariff_expires_at = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$payment['tariff_id'], $expiresAt, $_SESSION['user_id']]);
        
        setFlash('success', 'Оплата успешна! Тариф активирован.');
    }
    
    redirect('/index.php');
}

// Обработка отмены оплаты
if (isset($_GET['cancel'])) {
    $stmt = $db->prepare("
        UPDATE payments 
        SET status = 'canceled', updated_at = NOW()
        WHERE payment_id = ?
    ");
    $stmt->execute([$paymentId]);
    
    setFlash('info', 'Оплата отменена');
    redirect('/index.php');
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оплата тарифа - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        .payment-container {
            max-width: 500px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .payment-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .payment-header i {
            font-size: 60px;
            color: var(--secondary-color);
            margin-bottom: 20px;
        }
        
        .payment-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .payment-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .payment-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .payment-label {
            color: #6c757d;
        }
        
        .payment-value {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .payment-amount {
            font-size: 24px;
            font-weight: bold;
            color: var(--secondary-color);
            text-align: center;
            margin: 20px 0;
        }
        
        .payment-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn-pay {
            flex: 1;
            padding: 15px;
            background: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background 0.3s;
        }
        
        .btn-pay:hover {
            background: var(--secondary-color-dark);
        }
        
        .btn-cancel {
            flex: 1;
            padding: 15px;
            background: #f8f9fa;
            color: #6c757d;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .btn-cancel:hover {
            background: #e9ecef;
            border-color: #ced4da;
        }
        
        .payment-note {
            margin-top: 20px;
            padding: 15px;
            background: #fff3cd;
            border: 1px solid #ffecb5;
            border-radius: 8px;
            color: #856404;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-presentation"></i>
                <?php echo APP_NAME; ?>
            </div>
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <span><?php echo escape($_SESSION['user_name'] ?? 'Пользователь'); ?></span>
                </div>
                <a href="/index.php" class="btn-logout">
                    <i class="fas fa-home"></i> На главную
                </a>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="payment-container">
            <div class="payment-header">
                <i class="fas fa-credit-card"></i>
                <h1>Оплата тарифа</h1>
                <p>Подтвердите оплату выбранного тарифа</p>
            </div>
            
            <div class="payment-info">
                <div class="payment-item">
                    <span class="payment-label">Тариф:</span>
                    <span class="payment-value"><?php echo escape($payment['tariff_name']); ?></span>
                </div>
                <div class="payment-item">
                    <span class="payment-label">Пользователь:</span>
                    <span class="payment-value"><?php echo escape($payment['user_name']); ?></span>
                </div>
                <div class="payment-item">
                    <span class="payment-label">Email:</span>
                    <span class="payment-value"><?php echo escape($payment['email']); ?></span>
                </div>
                <div class="payment-item">
                    <span class="payment-label">ID платежа:</span>
                    <span class="payment-value" style="font-size: 12px;"><?php echo $paymentId; ?></span>
                </div>
            </div>
            
            <div class="payment-amount">
                <?php echo number_format($payment['price'], 0, ',', ' '); ?> ₽
            </div>
            
            <div class="payment-actions">
                <a href="/payment/process.php?id=<?php echo $paymentId; ?>&success=1" class="btn-pay">
                    <i class="fas fa-lock"></i> Оплатить
                </a>
                <a href="/payment/process.php?id=<?php echo $paymentId; ?>&cancel=1" class="btn-cancel">
                    <i class="fas fa-times"></i> Отмена
                </a>
            </div>
            
            <div class="payment-note">
                <i class="fas fa-info-circle"></i>
                Демо-режим. Для тестирования нажмите "Оплатить" для успешной оплаты или "Отмена" для отмены.
            </div>
        </div>
    </div>
</body>
</html>