<?php
// admin/users.php

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

// Обработка действий
$action = $_GET['action'] ?? '';
$userId = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = $_POST['id'] ?? 0;
    
    if ($action === 'toggle_active') {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT is_active FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $current = $stmt->fetch();
        
        if ($current) {
            $newStatus = $current['is_active'] ? 0 : 1;
            $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->execute([$newStatus, $userId]);
            
            setFlash('success', 'Статус пользователя обновлен');
        }
    } elseif ($action === 'change_role') {
        $roleId = $_POST['role_id'] ?? 1;
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE users SET role_id = ? WHERE id = ?");
        $stmt->execute([$roleId, $userId]);
        
        setFlash('success', 'Роль пользователя обновлена');
    } elseif ($action === 'change_tariff') {
        $tariffId = $_POST['tariff_id'] ?? 1;
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE users SET tariff_id = ? WHERE id = ?");
        $stmt->execute([$tariffId, $userId]);
        
        setFlash('success', 'Тариф пользователя обновлен');
    }
    
    header('Location: /admin/users.php');
    exit;
}

// Получаем список пользователей
$users = $userModel->getAllWithStats();

// Получаем список ролей
$db = Database::getInstance()->getConnection();
$roles = $db->query("SELECT * FROM user_roles ORDER BY id")->fetchAll();

// Получаем список тарифов
$tariffModel = new Tariff();
$tariffs = $tariffModel->getAll();

$success = getFlash('success');
$error = getFlash('error');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Пользователи - Админ-панель - <?php echo APP_NAME; ?></title>
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
                <a href="/admin/users.php" class="active">
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
                <h1><i class="fas fa-users"></i> Управление пользователями</h1>
                <p>Всего пользователей: <?php echo count($users); ?></p>
            </div>
        </div>
        
        <div class="admin-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Имя</th>
                        <th>Email</th>
                        <th>Дата регистрации</th>
                        <th>Презентаций</th>
                        <th>Роль</th>
                        <th>Тариф</th>
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
                                    <?php echo escape($userItem['role_name'] ?? 'Пользователь'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge">
                                    <?php echo escape($userItem['tariff_name'] ?? 'Не указан'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $userItem['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $userItem['is_active'] ? 'Активен' : 'Неактивен'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="user-actions">
                                    <button class="btn-action btn-toggle" 
                                            onclick="toggleUserStatus(<?php echo $userItem['id']; ?>, <?php echo $userItem['is_active']; ?>)">
                                        <?php echo $userItem['is_active'] ? 'Деактивировать' : 'Активировать'; ?>
                                    </button>
                                    
                                    <button class="btn-action btn-role" 
                                            onclick="changeUserRole(<?php echo $userItem['id']; ?>, '<?php echo escape($userItem['role_name'] ?? 'Пользователь'); ?>')">
                                        Роль
                                    </button>
                                    
                                    <button class="btn-action btn-tariff" 
                                            onclick="changeUserTariff(<?php echo $userItem['id']; ?>, '<?php echo escape($userItem['tariff_name'] ?? 'Не указан'); ?>')">
                                        Тариф
                                    </button>
                                    
                                    <button class="btn-action btn-impersonate" 
                                            onclick="impersonateUser(<?php echo $userItem['id']; ?>)">
                                        <i class="fas fa-user-secret"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Модальное окно для смены роли -->
    <div class="modal-overlay" id="roleModal">
        <div class="modal-content">
            <h2>Изменение роли пользователя</h2>
            <p id="roleModalText"></p>
            
            <form id="roleForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="change_role">
                <input type="hidden" name="id" id="roleUserId">
                
                <div class="form-group">
                    <label for="role_id">Выберите роль:</label>
                    <select name="role_id" id="role_id" class="form-control" required>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>">
                                <?php echo escape($role['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeRoleModal()">Отмена</button>
                    <button type="submit" class="btn-submit">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Модальное окно для смены тарифа -->
    <div class="modal-overlay" id="tariffModal">
        <div class="modal-content">
            <h2>Изменение тарифа пользователя</h2>
            <p id="tariffModalText"></p>
            
            <form id="tariffForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="change_tariff">
                <input type="hidden" name="id" id="tariffUserId">
                
                <div class="form-group">
                    <label for="tariff_id">Выберите тариф:</label>
                    <select name="tariff_id" id="tariff_id" class="form-control" required>
                        <?php foreach ($tariffs as $tariff): ?>
                            <option value="<?php echo $tariff['id']; ?>">
                                <?php echo escape($tariff['name']); ?> 
                                (<?php echo $tariff['price'] == 0 ? 'Бесплатно' : $tariff['price'] . ' ₽'; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeTariffModal()">Отмена</button>
                    <button type="submit" class="btn-submit">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const csrfToken = '<?php echo generateCsrfToken(); ?>';
        
        // Смена статуса пользователя
        function toggleUserStatus(userId, currentStatus) {
            if (confirm(currentStatus ? 'Деактивировать пользователя?' : 'Активировать пользователя?')) {
                const formData = new FormData();
                formData.append('action', 'toggle_active');
                formData.append('id', userId);
                formData.append('csrf_token', csrfToken);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (response.ok) {
                        location.reload();
                    } else {
                        alert('Ошибка при изменении статуса');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ошибка соединения с сервером');
                });
            }
        }
        
        // Смена роли пользователя
        function changeUserRole(userId, currentRole) {
            document.getElementById('roleUserId').value = userId;
            document.getElementById('roleModalText').textContent = 
                `Текущая роль: ${currentRole}. Выберите новую роль:`;
            document.getElementById('roleModal').classList.add('active');
        }
        
        function closeRoleModal() {
            document.getElementById('roleModal').classList.remove('active');
        }
        
        // Смена тарифа пользователя
        function changeUserTariff(userId, currentTariff) {
            document.getElementById('tariffUserId').value = userId;
            document.getElementById('tariffModalText').textContent = 
                `Текущий тариф: ${currentTariff}. Выберите новый тариф:`;
            document.getElementById('tariffModal').classList.add('active');
        }
        
        function closeTariffModal() {
            document.getElementById('tariffModal').classList.remove('active');
        }
        
        // Войти под пользователем
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
        
        // Закрытие модальных окон при клике на оверлей
        document.getElementById('roleModal').addEventListener('click', function(e) {
            if (e.target === this) closeRoleModal();
        });
        
        document.getElementById('tariffModal').addEventListener('click', function(e) {
            if (e.target === this) closeTariffModal();
        });
    </script>
</body>
</html>