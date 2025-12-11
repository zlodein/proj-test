<?php
// admin/tariffs.php

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
$tariffModel = new Tariff();

// Обработка действий
$action = $_GET['action'] ?? '';
$tariffId = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $tariffId = $_POST['id'] ?? 0;
    
    if ($action === 'add_tariff') {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $maxPresentations = intval($_POST['max_presentations']);
        $durationDays = intval($_POST['duration_days']);
        $features = isset($_POST['features']) ? json_encode($_POST['features']) : '[]';
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $isDefault = isset($_POST['is_default']) ? 1 : 0;
        
        $db = Database::getInstance()->getConnection();
        
        // Если устанавливаем тариф по умолчанию, снимаем флаг с других тарифов
        if ($isDefault) {
            $db->query("UPDATE tariffs SET is_default = 0");
        }
        
        $stmt = $db->prepare("
            INSERT INTO tariffs 
            (name, description, price, max_presentations, duration_days, features, is_active, is_default, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $name, $description, $price, $maxPresentations, 
            $durationDays, $features, $isActive, $isDefault
        ]);
        
        setFlash('success', 'Тариф успешно добавлен');
        
    } elseif ($action === 'edit_tariff') {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $maxPresentations = intval($_POST['max_presentations']);
        $durationDays = intval($_POST['duration_days']);
        $features = isset($_POST['features']) ? json_encode($_POST['features']) : '[]';
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $isDefault = isset($_POST['is_default']) ? 1 : 0;
        
        $db = Database::getInstance()->getConnection();
        
        // Если устанавливаем тариф по умолчанию, снимаем флаг с других тарифов
        if ($isDefault) {
            $db->query("UPDATE tariffs SET is_default = 0 WHERE id != ?");
        }
        
        $stmt = $db->prepare("
            UPDATE tariffs SET 
            name = ?, description = ?, price = ?, max_presentations = ?, 
            duration_days = ?, features = ?, is_active = ?, is_default = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $name, $description, $price, $maxPresentations, 
            $durationDays, $features, $isActive, $isDefault, $tariffId
        ]);
        
        setFlash('success', 'Тариф успешно обновлен');
        
    } elseif ($action === 'delete_tariff') {
        $db = Database::getInstance()->getConnection();
        
        // Проверяем, не используется ли тариф
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE tariff_id = ?");
        $stmt->execute([$tariffId]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            setFlash('error', 'Нельзя удалить тариф, который используется пользователями');
        } else {
            $stmt = $db->prepare("DELETE FROM tariffs WHERE id = ?");
            $stmt->execute([$tariffId]);
            setFlash('success', 'Тариф успешно удален');
        }
    }
    
    header('Location: /admin/tariffs.php');
    exit;
}

// Получаем список тарифов
$tariffs = $tariffModel->getAll();

// Если нужно редактировать тариф
$editTariff = null;
if ($action === 'edit' && $tariffId) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM tariffs WHERE id = ?");
    $stmt->execute([$tariffId]);
    $editTariff = $stmt->fetch();
    if ($editTariff && !empty($editTariff['features'])) {
        $editTariff['features'] = json_decode($editTariff['features'], true);
    }
}

$success = getFlash('success');
$error = getFlash('error');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тарифы - Админ-панель - <?php echo APP_NAME; ?></title>
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
                <a href="/admin/tariffs.php" class="active">
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
                <h1><i class="fas fa-crown"></i> Управление тарифами</h1>
                <p>Настройка тарифных планов системы</p>
            </div>
            <button class="btn-add-tariff" onclick="openTariffModal()">
                <i class="fas fa-plus"></i> Добавить тариф
            </button>
        </div>
        
        <div class="admin-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Описание</th>
                        <th>Цена</th>
                        <th>Презентаций</th>
                        <th>Дней</th>
                        <th>Особенности</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tariffs as $tariff): 
                        $features = !empty($tariff['features']) ? json_decode($tariff['features'], true) : [];
                    ?>
                        <tr>
                            <td><?php echo $tariff['id']; ?></td>
                            <td>
                                <strong><?php echo escape($tariff['name']); ?></strong>
                                <?php if ($tariff['is_default']): ?>
                                    <span class="status-badge status-default" style="margin-left: 5px;">По умолчанию</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo escape($tariff['description']); ?></td>
                            <td>
                                <?php if ($tariff['price'] == 0): ?>
                                    <span style="color: #388e3c; font-weight: bold;">Бесплатно</span>
                                <?php else: ?>
                                    <?php echo number_format($tariff['price'], 0, ',', ' '); ?> ₽
                                <?php endif; ?>
                            </td>
                            <td><?php echo $tariff['max_presentations'] == 0 ? '∞' : $tariff['max_presentations']; ?></td>
                            <td><?php echo $tariff['duration_days'] == 0 ? '∞' : $tariff['duration_days']; ?> дней</td>
                            <td>
                                <?php if (!empty($features)): ?>
                                    <ul style="margin: 0; padding-left: 20px; font-size: 12px;">
                                        <?php foreach (array_slice($features, 0, 3) as $feature): ?>
                                            <li><?php echo escape($feature); ?></li>
                                        <?php endforeach; ?>
                                        <?php if (count($features) > 3): ?>
                                            <li>... и ещё <?php echo count($features) - 3; ?></li>
                                        <?php endif; ?>
                                    </ul>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $tariff['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $tariff['is_active'] ? 'Активен' : 'Неактивен'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="tariff-actions">
                                    <button class="btn-action btn-edit" 
                                            onclick="editTariff(<?php echo $tariff['id']; ?>)">
                                        <i class="fas fa-edit"></i> Редактировать
                                    </button>
                                    <button class="btn-action btn-delete" 
                                            onclick="deleteTariff(<?php echo $tariff['id']; ?>)">
                                        <i class="fas fa-trash"></i> Удалить
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Модальное окно для добавления/редактирования тарифа -->
    <div class="modal-overlay" id="tariffModal">
        <div class="modal-content">
            <h2 id="modalTitle"><?php echo $editTariff ? 'Редактирование тарифа' : 'Добавление тарифа'; ?></h2>
            
            <form id="tariffForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="<?php echo $editTariff ? 'edit_tariff' : 'add_tariff'; ?>">
                <?php if ($editTariff): ?>
                    <input type="hidden" name="id" value="<?php echo $editTariff['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="name">Название тарифа *</label>
                    <input type="text" id="name" name="name" class="form-control" 
                           value="<?php echo $editTariff ? escape($editTariff['name']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Описание</label>
                    <textarea id="description" name="description" class="form-control" rows="3"><?php echo $editTariff ? escape($editTariff['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="price">Цена (₽) *</label>
                    <input type="number" id="price" name="price" class="form-control" 
                           value="<?php echo $editTariff ? $editTariff['price'] : '0'; ?>" 
                           min="0" step="0.01" required>
                    <small class="form-text">0 для бесплатного тарифа</small>
                </div>
                
                <div class="form-group">
                    <label for="max_presentations">Максимум презентаций *</label>
                    <input type="number" id="max_presentations" name="max_presentations" class="form-control" 
                           value="<?php echo $editTariff ? $editTariff['max_presentations'] : '5'; ?>" 
                           min="0" required>
                    <small class="form-text">0 для неограниченного количества</small>
                </div>
                
                <div class="form-group">
                    <label for="duration_days">Срок действия (дней) *</label>
                    <input type="number" id="duration_days" name="duration_days" class="form-control" 
                           value="<?php echo $editTariff ? $editTariff['duration_days'] : '30'; ?>" 
                           min="0" required>
                    <small class="form-text">0 для бессрочного тарифа</small>
                </div>
                
                <div class="form-group">
                    <label>Особенности тарифа</label>
                    <div id="featuresList" class="features-list">
                        <?php 
                        $features = $editTariff['features'] ?? ['Первая особенность', 'Вторая особенность'];
                        foreach ($features as $index => $feature): 
                        ?>
                            <div class="feature-item">
                                <input type="text" name="features[]" class="form-control" 
                                       value="<?php echo escape($feature); ?>" 
                                       placeholder="Особенность тарифа">
                                <?php if ($index > 0): ?>
                                    <button type="button" class="btn-remove-feature" onclick="removeFeature(this)">×</button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn-add-feature" onclick="addFeature()">
                        <i class="fas fa-plus"></i> Добавить особенность
                    </button>
                </div>
                
                <div class="form-check">
                    <input type="checkbox" id="is_active" name="is_active" 
                           <?php echo ($editTariff && $editTariff['is_active']) || !$editTariff ? 'checked' : ''; ?>>
                    <label for="is_active">Активен (отображается для пользователей)</label>
                </div>
                
                <div class="form-check">
                    <input type="checkbox" id="is_default" name="is_default" 
                           <?php echo $editTariff && $editTariff['is_default'] ? 'checked' : ''; ?>>
                    <label for="is_default">Тариф по умолчанию (для новых пользователей)</label>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeTariffModal()">Отмена</button>
                    <button type="submit" class="btn-submit">
                        <?php echo $editTariff ? 'Сохранить изменения' : 'Добавить тариф'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const csrfToken = '<?php echo generateCsrfToken(); ?>';
        
        function openTariffModal() {
            document.getElementById('modalTitle').textContent = 'Добавление тарифа';
            document.getElementById('tariffForm').action = '';
            document.getElementById('tariffForm').reset();
            
            // Сбрасываем скрытые поля
            const hiddenId = document.querySelector('input[name="id"]');
            if (hiddenId) hiddenId.remove();
            
            const hiddenAction = document.querySelector('input[name="action"]');
            if (hiddenAction) hiddenAction.value = 'add_tariff';
            
            // Сбрасываем особенности
            const featuresList = document.getElementById('featuresList');
            featuresList.innerHTML = `
                <div class="feature-item">
                    <input type="text" name="features[]" class="form-control" 
                           value="Первая особенность" placeholder="Особенность тарифа">
                </div>
                <div class="feature-item">
                    <input type="text" name="features[]" class="form-control" 
                           value="Вторая особенность" placeholder="Особенность тарифа">
                    <button type="button" class="btn-remove-feature" onclick="removeFeature(this)">×</button>
                </div>
            `;
            
            document.getElementById('tariffModal').classList.add('active');
        }
        
        function closeTariffModal() {
            document.getElementById('tariffModal').classList.remove('active');
        }
        
        function editTariff(tariffId) {
            window.location.href = '/admin/tariffs.php?action=edit&id=' + tariffId;
        }
        
        function deleteTariff(tariffId) {
            if (!confirm('Удалить тариф? Пользователи, использующие этот тариф, будут переведены на тариф по умолчанию.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_tariff');
            formData.append('id', tariffId);
            formData.append('csrf_token', csrfToken);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    location.reload();
                } else {
                    alert('Ошибка при удалении тарифа');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ошибка соединения с сервером');
            });
        }
        
        function addFeature() {
            const featuresList = document.getElementById('featuresList');
            const featureItem = document.createElement('div');
            featureItem.className = 'feature-item';
            featureItem.innerHTML = `
                <input type="text" name="features[]" class="form-control" 
                       placeholder="Особенность тарифа">
                <button type="button" class="btn-remove-feature" onclick="removeFeature(this)">×</button>
            `;
            featuresList.appendChild(featureItem);
        }
        
        function removeFeature(button) {
            button.parentElement.remove();
        }
        
        // Закрытие модального окна при клике на оверлей
        document.getElementById('tariffModal').addEventListener('click', function(e) {
            if (e.target === this) closeTariffModal();
        });
        
        // Если открыто редактирование, показываем модальное окно
        <?php if ($editTariff): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('tariffModal').classList.add('active');
        });
        <?php endif; ?>
    </script>
</body>
</html>