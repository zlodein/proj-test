<?php
// admin/dashboard.php

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
$userModel = new User();
$presentationModel = new Presentation();
$featureRequestModel = new FeatureRequest();

// Получаем статистику
$stats = [
    'total_users' => $userModel->countAll(),
    'active_users' => $userModel->countActive(),
    'total_presentations' => $presentationModel->countAll(),
    'feature_requests' => $featureRequestModel->getStats()
];

// Получаем список пользователей с их презентациями
$users = $userModel->getAllWithStats();

// Получаем список пожеланий
$features = $featureRequestModel->getAll();

$success = getFlash('success');
$error = getFlash('error');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель - <?php echo APP_NAME; ?></title>
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
                <a href="/admin/dashboard.php" class="active">
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
                <a href="/admin/payments.php">
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
                <h1><i class="fas fa-tachometer-alt"></i> Административная панель</h1>
                <p>Управление системой и пользователями</p>
            </div>
        </div>
        
        <div class="admin-stats">
            <div class="admin-stat-card">
                <div class="admin-stat-value"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Всего пользователей</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-value"><?php echo $stats['active_users']; ?></div>
                <div class="stat-label">Активных пользователей</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-value"><?php echo $stats['total_presentations']; ?></div>
                <div class="stat-label">Всего презентаций</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-value"><?php echo $stats['feature_requests']['total']; ?></div>
                <div class="stat-label">Пожеланий от пользователей</div>
            </div>
        </div>
        
        <!-- Список пользователей -->
        <div class="admin-table">
            <h2 style="padding: 20px 20px 0; margin: 0;">Пользователи системы</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Имя</th>
                        <th>Email</th>
                        <th>Дата регистрации</th>
                        <th>Презентаций</th>
                        <th>Тариф</th>
                        <th>Роль</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $userItem): ?>
                        <tr>
                            <td><?php echo $userItem['id']; ?></td>
                            <td><?php echo escape($userItem['name']); ?></td>
                            <td><?php echo escape($userItem['email']); ?></td>
                            <td><?php echo date('d.m.Y', strtotime($userItem['created_at'])); ?></td>
                            <td><?php echo $userItem['presentation_count']; ?></td>
                            <td>
                                <span class="status-badge">
                                    <?php echo escape($userItem['tariff_name'] ?? 'Не указан'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge">
                                    <?php echo escape($userItem['role_name'] ?? 'Пользователь'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $userItem['is_active'] ? 'status-completed' : 'status-rejected'; ?>">
                                    <?php echo $userItem['is_active'] ? 'Активен' : 'Неактивен'; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn-impersonate" onclick="impersonateUser(<?php echo $userItem['id']; ?>)">
                                    <i class="fas fa-user-secret"></i> Войти как
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Список пожеланий -->
        <div class="admin-table">
            <h2 style="padding: 20px 20px 0; margin: 0;">Пожелания пользователей</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Заголовок</th>
                        <th>Пользователь</th>
                        <th>Голоса</th>
                        <th>Статус</th>
                        <th>Дата</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($features as $feature): ?>
                        <tr>
                            <td><?php echo $feature['id']; ?></td>
                            <td>
                                <strong><?php echo escape($feature['title']); ?></strong>
                                <?php if ($feature['description']): ?>
                                    <p style="margin: 5px 0 0; font-size: 12px; color: #666;">
                                        <?php echo escape(substr($feature['description'], 0, 100)); ?>...
                                    </p>
                                <?php endif; ?>
                            </td>
                            <td><?php echo escape($feature['user_name']); ?></td>
                            <td><?php echo $feature['votes']; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $feature['status']; ?>">
                                    <?php echo $feature['status'] == 'new' ? 'Новое' : 
                                           ($feature['status'] == 'in_progress' ? 'В работе' : 
                                           ($feature['status'] == 'completed' ? 'Реализовано' : 'Отклонено')); ?>
                                </span>
                            </td>
                            <td><?php echo date('d.m.Y', strtotime($feature['created_at'])); ?></td>
                            <td>
                                <div class="feature-actions">
                                    <?php if ($feature['status'] != 'in_progress'): ?>
                                        <button class="btn-status in_progress" 
                                                onclick="updateFeatureStatus(<?php echo $feature['id']; ?>, 'in_progress')">
                                            В работу
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($feature['status'] != 'completed'): ?>
                                        <button class="btn-status completed" 
                                                onclick="updateFeatureStatus(<?php echo $feature['id']; ?>, 'completed')">
                                            Реализовано
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($feature['status'] != 'rejected'): ?>
                                        <button class="btn-status rejected" 
                                                onclick="updateFeatureStatus(<?php echo $feature['id']; ?>, 'rejected')">
                                            Отклонить
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        const csrfToken = '<?php echo generateCsrfToken(); ?>';
        
        async function impersonateUser(userId) {
            if (!confirm('Войти под выбранным пользователем? Ваша текущая сессия будет завершена.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('csrf_token', csrfToken);
            
            try {
                const response = await fetch('/api.php?action=admin_impersonate', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = '/index.php';
                } else {
                    alert('Ошибка: ' + (data.error || 'Не удалось войти под пользователем'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ошибка соединения с сервером');
            }
        }
        
        async function updateFeatureStatus(featureId, status) {
            const formData = new FormData();
            formData.append('feature_id', featureId);
            formData.append('status', status);
            formData.append('csrf_token', csrfToken);
            
            try {
                const response = await fetch('/api.php?action=update_feature_status', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    location.reload();
                } else {
                    alert('Ошибка: ' + (data.error || 'Не удалось обновить статус'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ошибка соединения с сервером');
            }
        }
    </script>
</body>
</html>