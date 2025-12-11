<?php
// admin/features.php

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
$featureModel = new FeatureRequest();

// Получаем статистику
$stats = $featureModel->getStats();

// Получаем список пожеланий
$status = $_GET['status'] ?? null;
$features = $featureModel->getAll($status);

// Фильтры для статусов
$statusFilters = [
    '' => 'Все пожелания',
    'new' => 'Новые',
    'in_progress' => 'В работе',
    'completed' => 'Реализованные',
    'rejected' => 'Отклоненные'
];

$success = getFlash('success');
$error = getFlash('error');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Пожелания - Админ-панель - <?php echo APP_NAME; ?></title>
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
                <a href="/admin/features.php" class="active">
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
                <h1><i class="fas fa-lightbulb"></i> Управление пожеланиями</h1>
                <p>Сбор и управление предложениями пользователей</p>
            </div>
        </div>
        
        <div class="admin-stats">
            <div class="admin-stat-card">
                <div class="admin-stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Всего пожеланий</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-value"><?php echo $stats['new']; ?></div>
                <div class="stat-label">Новые</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-value"><?php echo $stats['in_progress']; ?></div>
                <div class="stat-label">В работе</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-value"><?php echo $stats['completed']; ?></div>
                <div class="stat-label">Реализовано</div>
            </div>
        </div>
        
        <div class="filters">
            <?php foreach ($statusFilters as $filterStatus => $filterName): ?>
                <button class="filter-btn <?php echo ($status === $filterStatus) ? 'active' : ''; ?>" 
                        onclick="window.location.href='/admin/features.php<?php echo $filterStatus ? '?status=' . $filterStatus : ''; ?>'">
                    <?php echo $filterName; ?>
                </button>
            <?php endforeach; ?>
        </div>
        
        <div class="admin-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Пожелание</th>
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
                                    <div class="feature-description">
                                        <?php echo escape(substr($feature['description'], 0, 150)); ?>
                                        <?php if (strlen($feature['description']) > 150): ?>...<?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($feature['admin_notes']): ?>
                                    <div class="feature-description" style="color: #f57c00;">
                                        <strong>Заметки администратора:</strong> 
                                        <?php echo escape($feature['admin_notes']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="feature-user"><?php echo escape($feature['user_name']); ?></div>
                                <div class="feature-user"><?php echo escape($feature['email']); ?></div>
                            </td>
                            <td>
                                <span class="vote-count">
                                    <i class="fas fa-thumbs-up"></i>
                                    <?php echo $feature['votes']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $feature['status']; ?>">
                                    <?php echo $feature['status'] == 'new' ? 'Новое' : 
                                           ($feature['status'] == 'in_progress' ? 'В работе' : 
                                           ($feature['status'] == 'completed' ? 'Реализовано' : 'Отклонено')); ?>
                                </span>
                            </td>
                            <td>
                                <div class="feature-date"><?php echo date('d.m.Y', strtotime($feature['created_at'])); ?></div>
                                <?php if ($feature['updated_at'] != $feature['created_at']): ?>
                                    <div class="feature-date">изм: <?php echo date('d.m.Y', strtotime($feature['updated_at'])); ?></div>
                                <?php endif; ?>
                            </td>
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
        
        async function updateFeatureStatus(featureId, status) {
            const statusNames = {
                'in_progress': 'В работу',
                'completed': 'Реализовано',
                'rejected': 'Отклонить'
            };
            
            if (!confirm('Изменить статус на "' + statusNames[status] + '"?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('feature_id', featureId);
            formData.append('status', status);
            formData.append('csrf_token', csrfToken);
            
            // Для статуса "rejected" запросим заметку
            if (status === 'rejected') {
                const adminNotes = prompt('Укажите причину отклонения (опционально):');
                if (adminNotes !== null) {
                    formData.append('admin_notes', adminNotes);
                }
            }
            
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