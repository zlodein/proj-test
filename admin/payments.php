<?php
// admin/payments.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

spl_autoload_register(function ($class) {
    $paths = [__DIR__ . '/../src/Models/', __DIR__ . '/../src/Controllers/'];
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

session_start();
requireAdmin();

$user = getCurrentUser();

// Получаем платежи
$db = Database::getInstance()->getConnection();

// Статистика
$statsStmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'succeeded' THEN amount ELSE 0 END) as total_amount,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'succeeded' THEN 1 ELSE 0 END) as succeeded,
        SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as canceled
    FROM payments
");
$stats = $statsStmt->fetch();

// Фильтры
$status = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$where = [];
$params = [];

if ($status) {
    $where[] = "p.status = ?";
    $params[] = $status;
}

if ($dateFrom) {
    $where[] = "DATE(p.created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $where[] = "DATE(p.created_at) <= ?";
    $params[] = $dateTo;
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Получаем список платежей
$query = "
    SELECT p.*, u.name as user_name, u.email, t.name as tariff_name
    FROM payments p
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN tariffs t ON p.tariff_id = t.id
    $whereClause
    ORDER BY p.created_at DESC
    LIMIT 100
";

$stmt = $db->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll();

$success = getFlash('success');
$error = getFlash('error');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Платежи - Админ-панель - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
    <header class="admin-header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-shield-alt"></i>
                <?php echo APP_NAME; ?> - Админ-панель
            </div>
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <span><?php echo escape($user['name']); ?> (Админ)</span>
                </div>
                <a href="/index.php" class="btn-logout">
                    <i class="fas fa-home"></i> На сайт
                </a>
                <a href="/auth/login.php?logout=1" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Выход
                </a>
            </div>
        </div>
        
        <div class="container">
            <div class="admin-nav">
                <a href="/admin/dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Дашборд
                </a>
                <a href="/admin/users.php">
                    <i class="fas fa-users"></i> Пользователи
                </a>
                <a href="/admin/features.php">
                    <i class="fas fa-lightbulb"></i> Пожелания
                </a>
                <a href="/admin/tariffs.php">
                    <i class="fas fa-crown"></i> Тарифы
                </a>
                <a href="/admin/payments.php" class="active">
                    <i class="fas fa-credit-card"></i> Платежи
                </a>
            </div>
        </div>
    </header>
    
    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo escape($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo escape($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="dashboard-header">
            <div class="dashboard-title">
                <h1><i class="fas fa-credit-card"></i> Управление платежами</h1>
                <p>Мониторинг финансовых операций системы</p>
            </div>
        </div>
        
        <div class="admin-stats">
            <div class="admin-stat-card">
                <div class="admin-stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Всего платежей</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-value"><?php echo number_format($stats['total_amount'] ?? 0, 0, ',', ' '); ?> ₽</div>
                <div class="stat-label">Общая сумма</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-value"><?php echo $stats['succeeded']; ?></div>
                <div class="stat-label">Успешных</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-value"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Ожидают оплаты</div>
            </div>
        </div>
        
        <div class="filters">
            <form method="GET" id="filterForm">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Статус платежа</label>
                        <select name="status" class="form-control">
                            <option value="">Все статусы</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Ожидает оплаты</option>
                            <option value="succeeded" <?php echo $status === 'succeeded' ? 'selected' : ''; ?>>Успешно</option>
                            <option value="canceled" <?php echo $status === 'canceled' ? 'selected' : ''; ?>>Отменен</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Дата с</label>
                        <input type="date" name="date_from" class="form-control" value="<?php echo $dateFrom; ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>Дата по</label>
                        <input type="date" name="date_to" class="form-control" value="<?php echo $dateTo; ?>">
                    </div>
                </div>
                
                <div class="filter-row">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-filter"></i> Применить фильтры
                    </button>
                    <button type="button" class="btn-reset" onclick="resetFilters()">
                        <i class="fas fa-times"></i> Сбросить
                    </button>
                </div>
            </form>
        </div>
        
        <div class="admin-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Пользователь</th>
                        <th>Тариф</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                        <th>ID платежа</th>
                        <th>Дата создания</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?php echo $payment['id']; ?></td>
                            <td>
                                <div><?php echo escape($payment['user_name']); ?></div>
                                <div style="font-size: 12px; color: #7f8c8d;"><?php echo escape($payment['email']); ?></div>
                            </td>
                            <td><?php echo escape($payment['tariff_name']); ?></td>
                            <td class="payment-amount">
                                <?php echo number_format($payment['amount'], 0, ',', ' '); ?> ₽
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $payment['status']; ?>">
                                    <?php echo $payment['status'] == 'pending' ? 'Ожидает' : 
                                           ($payment['status'] == 'succeeded' ? 'Успешно' : 'Отменен'); ?>
                                </span>
                            </td>
                            <td>
                                <code style="font-size: 11px;"><?php echo $payment['payment_id']; ?></code>
                            </td>
                            <td>
                                <div><?php echo date('d.m.Y', strtotime($payment['created_at'])); ?></div>
                                <div style="font-size: 11px; color: #95a5a6;"><?php echo date('H:i', strtotime($payment['created_at'])); ?></div>
                            </td>
                            <td>
                                <?php if ($payment['status'] === 'pending'): ?>
                                    <button class="btn-action" 
                                            onclick="updatePaymentStatus(<?php echo $payment['id']; ?>, 'succeeded')"
                                            style="padding: 4px 8px; background: #e8f5e8; color: #388e3c; border: none; border-radius: 4px; cursor: pointer;">
                                        <i class="fas fa-check"></i> Подтвердить
                                    </button>
                                    <button class="btn-action" 
                                            onclick="updatePaymentStatus(<?php echo $payment['id']; ?>, 'canceled')"
                                            style="padding: 4px 8px; background: #ffebee; color: #d32f2f; border: none; border-radius: 4px; cursor: pointer;">
                                        <i class="fas fa-times"></i> Отменить
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        const csrfToken = '<?php echo generateCsrfToken(); ?>';
        
        function resetFilters() {
            window.location.href = '/admin/payments.php';
        }
        
        async function updatePaymentStatus(paymentId, status) {
            const statusNames = {
                'succeeded': 'Подтвердить',
                'canceled': 'Отменить'
            };
            
            if (!confirm(`${statusNames[status]} платеж?`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('payment_id', paymentId);
            formData.append('status', status);
            formData.append('csrf_token', csrfToken);
            
            try {
                const response = await fetch('/api.php?action=admin_update_payment', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    location.reload();
                } else {
                    alert('Ошибка: ' + (data.error || 'Не удалось обновить статус платежа'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ошибка соединения с сервером');
            }
        }
    </script>
</body>
</html>