<?php
// Включим отладку для поиска проблем
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

spl_autoload_register(function ($class) {
    $paths = [__DIR__ . '/src/Models/', __DIR__ . '/src/Controllers/'];
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

session_start();
requireAuth();

$user = getCurrentUser();
$presentationModel = new Presentation();
$tariffModel = new Tariff();
$featureRequestModel = new FeatureRequest();

// Проверяем роль пользователя
$isAdmin = false;
if (function_exists('isAdmin')) {
    $isAdmin = isAdmin($user['id']);
}

// Если админ, перенаправляем в админку
if ($isAdmin && basename($_SERVER['PHP_SELF']) == 'index.php') {
    redirect('/admin/dashboard.php');
}

// Получаем презентации с исправленным методом
$presentations = $presentationModel->getByUser(null, 50, 0);
$totalPresentations = $presentationModel->countByUser();

// Получаем информацию о тарифе пользователя
$userTariff = $tariffModel->getUserTariff($user['id']);
$remainingPresentations = $tariffModel->getRemainingPresentations($user['id']);

// Получаем пожелания пользователя
$userFeatures = $featureRequestModel->getByUser($user['id']);

$success = getFlash('success');
$error = getFlash('error');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои презентации - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/main.css?v=1">
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-presentation"></i>
                <?php echo APP_NAME; ?>
            </div>
            <div class="user-menu">
                <div class="user-info" id="userProfileBtn" style="cursor: pointer;">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <span><?php echo escape($user['name']); ?></span>
                </div>
                <a href="/auth/login.php?logout=1" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Выход
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
                <h1>Мои презентации</h1>
                <p>Управляйте своими презентациями</p>
            </div>
            <?php if ($tariffModel->canCreatePresentation($user['id'])): ?>
                <button class="btn-create" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i> Создать презентацию
                </button>
            <?php else: ?>
                <button class="btn-create" onclick="openUpgradeModal()" style="background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);">
                    <i class="fas fa-crown"></i> Обновить тариф для создания
                </button>
            <?php endif; ?>
        </div>
        
        <!-- Блок информации о тарифе -->
        <div class="tariff-info">
            <h3>Ваш тариф: <?php echo escape($userTariff['name']); ?></h3>
            <p><?php echo escape($userTariff['description']); ?></p>
            
            <div class="tariff-stats">
                <div class="tariff-stat">
                    <div class="stat-value"><?php echo $remainingPresentations; ?></div>
                    <div class="stat-label">Осталось презентаций</div>
                </div>
                <div class="tariff-stat">
                    <div class="stat-value"><?php echo $userTariff['max_presentations']; ?></div>
                    <div class="stat-label">Всего доступно</div>
                </div>
                <div class="tariff-stat">
                    <div class="stat-value">
                        <?php echo $userTariff['duration_days'] == 0 ? '∞' : $userTariff['duration_days']; ?> дней
                    </div>
                    <div class="stat-label">Длительность</div>
                </div>
            </div>
            
            <?php if ($remainingPresentations !== '∞' && $remainingPresentations < 5 && $remainingPresentations > 0): ?>
                <div class="alert alert-warning" style="margin-top: 15px; background: #fff3e0; color: #f57c00; border-color: #ffb74d;">
                    <i class="fas fa-exclamation-triangle"></i>
                    У вас осталось мало презентаций. Рассмотрите возможность обновления тарифа.
                </div>
            <?php endif; ?>
            
            <?php if ($remainingPresentations !== '∞' && $remainingPresentations == 0): ?>
                <div class="alert alert-error" style="margin-top: 15px;">
                    <i class="fas fa-ban"></i>
                    Вы исчерпали лимит презентаций по текущему тарифу.
                </div>
            <?php endif; ?>
            
            <button class="btn btn-upgrade" onclick="openUpgradeModal()">
                <i class="fas fa-crown"></i> Обновить тариф
            </button>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-file-powerpoint"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $totalPresentations; ?></h3>
                    <p>Всего презентаций</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo count(array_filter($presentations, function($p) { return $p['status'] === 'published'; })); ?></h3>
                    <p>Опубликовано</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-edit"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo count(array_filter($presentations, function($p) { return $p['status'] === 'draft'; })); ?></h3>
                    <p>Черновики</p>
                </div>
            </div>
        </div>
        
        <div class="search-bar">
            <div class="search-input-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" class="search-input" id="searchInput" placeholder="Поиск презентаций...">
            </div>
        </div>
        
        <div class="presentations-grid" id="presentationsGrid">
            <?php if (empty($presentations)): ?>
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <i class="fas fa-file-powerpoint"></i>
                    <h3>У вас пока нет презентаций</h3>
                    <p>Создайте свою первую презентацию прямо сейчас</p>
                    <?php if ($tariffModel->canCreatePresentation($user['id'])): ?>
                        <button class="btn-create" onclick="openCreateModal()">
                            <i class="fas fa-plus"></i> Создать презентацию
                        </button>
                    <?php else: ?>
                        <button class="btn-create" onclick="openUpgradeModal()" style="background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);">
                            <i class="fas fa-crown"></i> Обновить тариф для создания
                        </button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($presentations as $presentation): ?>
                    <div class="presentation-card" data-id="<?php echo $presentation['id']; ?>" data-title="<?php echo escape($presentation['title']); ?>">
                        <div class="presentation-thumbnail">
                            <?php if (!empty($presentation['cover_image'])): ?>
                                <?php 
                                $coverUrl = $presentation['cover_image'];
                                if (!preg_match('/^https?:\/\//', $coverUrl)) {
                                    $coverUrl = APP_URL . $coverUrl;
                                }
                                ?>
                                <img src="<?php echo $coverUrl; ?>" 
                                     alt="<?php echo escape($presentation['title']); ?>" 
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <i class="fas fa-image" style="display: none;"></i>
                            <?php else: ?>
                                <i class="fas fa-image"></i>
                            <?php endif; ?>
                            <span class="presentation-status <?php echo $presentation['status'] === 'published' ? 'status-published' : 'status-draft'; ?>">
                                <?php echo $presentation['status'] === 'published' ? 'Опубликовано' : 'Черновик'; ?>
                            </span>
                        </div>
                        <div class="presentation-content">
                            <h3 class="presentation-title"><?php echo escape($presentation['title']); ?></h3>
                            <p class="presentation-description-card"><?php echo escape($presentation['description']); ?></p>
                            <div class="presentation-meta">
                                <span><i class="far fa-clock"></i> <?php echo date('d.m.Y', strtotime($presentation['updated_at'])); ?></span>
                                <span><i class="fas fa-file-alt"></i> <?php echo $presentation['slides_count'] ?? 0; ?> слайдов</span>
                            </div>
                            <div class="presentation-actions">
                                <button class="btn-action btn-view" onclick="viewPresentation(<?php echo $presentation['id']; ?>)">
                                    <i class="fas fa-eye"></i> Просмотр
                                </button>
                                <button class="btn-action btn-edit" onclick="editPresentation(<?php echo $presentation['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-action btn-delete" onclick="deletePresentation(<?php echo $presentation['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Блок пожеланий для сайта -->
        <div class="feature-requests">
            <div class="dashboard-title">
                <h2><i class="fas fa-lightbulb"></i> Пожелания для сайта</h2>
                <p>Предложите новые функции или улучшения</p>
            </div>
            
            <form id="featureRequestForm" style="margin-bottom: 20px;">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <div class="form-group">
                    <input type="text" name="title" class="form-control" placeholder="Краткое описание пожелания" required>
                </div>
                <div class="form-group">
                    <textarea name="description" class="form-control" placeholder="Подробное описание (опционально)" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-plus"></i> Добавить пожелание
                </button>
            </form>
            
            <div id="userFeaturesList">
                <?php foreach ($userFeatures as $feature): ?>
                    <div class="feature-item <?php echo $feature['is_implemented'] ? 'completed' : ''; ?>" 
                         data-id="<?php echo $feature['id']; ?>">
                        <div class="feature-content">
                            <h4><?php echo escape($feature['title']); ?></h4>
                            <?php if ($feature['description']): ?>
                                <p><?php echo escape($feature['description']); ?></p>
                            <?php endif; ?>
                            <small class="feature-date">
                                <?php echo date('d.m.Y H:i', strtotime($feature['created_at'])); ?>
                                <?php if ($feature['status'] != 'new'): ?>
                                    <span class="feature-status status-<?php echo $feature['status']; ?>">
                                        <?php echo $feature['status'] == 'in_progress' ? 'В работе' : 
                                               ($feature['status'] == 'completed' ? 'Реализовано' : 'Отклонено'); ?>
                                    </span>
                                <?php endif; ?>
                            </small>
                        </div>
                        <div class="feature-vote">
                            <button class="vote-btn <?php echo $feature['user_voted'] ? 'voted' : ''; ?>" 
                                    onclick="voteFeature(<?php echo $feature['id']; ?>, this)">
                                <i class="fas fa-thumbs-up"></i>
                            </button>
                            <span class="vote-count"><?php echo $feature['votes']; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Подвал сайта -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <div class="footer-logo">
                    <i class="fas fa-presentation"></i>
                    <?php echo APP_NAME; ?>
                </div>
                <p>Профессиональный конструктор презентаций для недвижимости</p>
            </div>
            
            <div class="footer-section">
                <h4>Контакты</h4>
                <div class="footer-info">
                    <p><i class="fas fa-map-marker-alt"></i> <?php echo COMPANY_ADDRESS; ?></p>
                    <p><i class="fas fa-phone"></i> <?php echo COMPANY_PHONE; ?></p>
                    <p><i class="fas fa-envelope"></i> <?php echo SUPPORT_EMAIL; ?></p>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>Реквизиты</h4>
                <div class="footer-info">
                    <p>ИНН: <?php echo COMPANY_INN; ?></p>
                    <p>ОГРН: <?php echo COMPANY_OGRN; ?></p>
                    <p><?php echo COMPANY_NAME; ?></p>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>Ссылки</h4>
                <div class="footer-links">
                    <a href="/about.php">О компании</a>
                    <a href="/tariffs.php">Тарифы</a>
                    <a href="/support.php">Поддержка</a>
                    <a href="/privacy.php">Политика конфиденциальности</a>
                </div>
            </div>
        </div>
        
        <div class="copyright">
            © <?php echo date('Y'); ?> <?php echo COMPANY_NAME; ?>. Все права защищены.
        </div>
    </footer>
    
    <!-- Модальное окно создания презентации -->
    <div class="modal" id="createModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Создать презентацию</h2>
            </div>
            <form id="createForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <div class="form-group">
                    <label for="title">Название презентации</label>
                    <input type="text" id="title" name="title" class="form-control" placeholder="Введите название" required autofocus>
                </div>
                <div class="form-group">
                    <label for="description">Описание (опционально)</label>
                    <textarea id="description" name="description" class="form-control" placeholder="Краткое описание презентации"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeCreateModal()">Отмена</button>
                    <button type="submit" class="btn-submit"><i class="fas fa-plus"></i> Создать</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Модальное окно профиля -->
    <div class="profile-modal" id="profileModal">
        <div class="profile-modal-content">
            <div class="modal-header" style="padding: 20px; border-bottom: 1px solid var(--border-color);">
                <h2><i class="fas fa-user-circle"></i> Профиль пользователя</h2>
                <button class="btn-back" onclick="closeProfileModal()" style="position: absolute; right: 20px; top: 20px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="profileForm" style="padding: 20px;">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <div class="form-group">
                    <label>Электронная почта</label>
                    <input type="email" class="form-control" value="<?php echo escape($user['email']); ?>" disabled>
                    <small class="form-text">Email нельзя изменить</small>
                </div>
                
                <div class="form-group">
                    <label for="profileName">Имя</label>
                    <input type="text" id="profileName" name="name" class="form-control" 
                           value="<?php echo escape($user['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="currentPassword">Текущий пароль (для подтверждения изменений)</label>
                    <input type="password" id="currentPassword" name="current_password" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="newPassword">Новый пароль (оставьте пустым, если не хотите менять)</label>
                    <input type="password" id="newPassword" name="new_password" class="form-control">
                    <div class="password-strength" id="passwordStrength"></div>
                </div>
                
                <div class="form-group">
                    <label for="confirmPassword">Подтверждение нового пароля</label>
                    <input type="password" id="confirmPassword" name="confirm_password" class="form-control">
                </div>
                
                <div class="modal-actions" style="margin-top: 20px;">
                    <button type="button" class="btn-cancel" onclick="closeProfileModal()">Отмена</button>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Сохранить изменения
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Модальное окно выбора тарифа -->
    <div class="modal" id="upgradeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-crown"></i> Выбор тарифа</h2>
            </div>
            <div class="tariffs-list" id="tariffsList">
                <!-- Тарифы будут загружены динамически -->
                <div class="loading" style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin"></i> Загрузка тарифов...
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const csrfToken = '<?php echo generateCsrfToken(); ?>';
        
        function openCreateModal() {
            document.getElementById('createModal').classList.add('active');
            document.getElementById('title').focus();
        }
        
        function closeCreateModal() {
            document.getElementById('createModal').classList.remove('active');
            document.getElementById('createForm').reset();
        }
        
        document.getElementById('createModal').addEventListener('click', function(e) {
            if (e.target === this) closeCreateModal();
        });
        
        document.getElementById('createForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Проверяем, можно ли создать презентацию
            const canCreate = await checkCanCreatePresentation();
            if (!canCreate) {
                showNotification('Лимит презентаций исчерпан. Обновите тариф.', 'error');
                closeCreateModal();
                openUpgradeModal();
                return;
            }
            
            const submitBtn = this.querySelector('.btn-submit');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Создание...';
            
            try {
                const response = await fetch('/api.php?action=create_presentation', {
                    method: 'POST',
                    body: new FormData(this)
                });
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = '/editor.php?id=' + data.id;
                } else {
                    alert('Ошибка: ' + (data.error || 'Не удалось создать презентацию'));
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ошибка соединения с сервером');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
        
        async function checkCanCreatePresentation() {
            try {
                const response = await fetch('/api.php?action=can_create_presentation');
                const data = await response.json();
                return data.can_create === true;
            } catch (error) {
                console.error('Error checking presentation limit:', error);
                return false;
            }
        }
        
        function viewPresentation(id) {
            window.open('/api.php?action=generate_presentation&id=' + id, '_blank');
        }
        
        function editPresentation(id) {
            window.location.href = '/editor.php?id=' + id;
        }
        
        async function deletePresentation(id) {
            const card = document.querySelector('[data-id="' + id + '"]');
            const title = card ? card.dataset.title : 'эту презентацию';
            
            if (!confirm('Вы уверены, что хотите удалить "' + title + '"?')) return;
            
            const formData = new FormData();
            formData.append('id', id);
            formData.append('csrf_token', csrfToken);
            
            try {
                const response = await fetch('/api.php?action=delete_presentation', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    if (card) {
                        card.style.animation = 'fadeOut 0.3s ease';
                        setTimeout(() => {
                            card.remove();
                            if (document.getElementById('presentationsGrid').children.length === 0) {
                                location.reload();
                            }
                        }, 300);
                    }
                } else {
                    alert('Ошибка: ' + (data.error || 'Не удалось удалить презентацию'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ошибка соединения с сервером');
            }
        }
        
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const query = e.target.value.toLowerCase();
            
            searchTimeout = setTimeout(() => {
                document.querySelectorAll('.presentation-card').forEach(card => {
                    const title = card.querySelector('.presentation-title').textContent.toLowerCase();
                    card.style.display = title.includes(query) ? 'block' : 'none';
                });
            }, 300);
        });
        
        // Открытие модального окна профиля
        document.getElementById('userProfileBtn').addEventListener('click', function(e) {
            if (!e.target.closest('.btn-logout')) {
                openProfileModal();
            }
        });
        
        function openProfileModal() {
            document.getElementById('profileModal').classList.add('active');
        }
        
        function closeProfileModal() {
            document.getElementById('profileModal').classList.remove('active');
            document.getElementById('profileForm').reset();
        }
        
        // Обработка формы профиля
        document.getElementById('profileForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('.btn-submit');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';
            
            try {
                const response = await fetch('/api.php?action=update_profile', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('Профиль успешно обновлен', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(data.error || 'Ошибка обновления профиля', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Ошибка соединения с сервером', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
        
        // Проверка сложности пароля
        document.getElementById('newPassword').addEventListener('input', function(e) {
            const password = e.target.value;
            const strengthDiv = document.getElementById('passwordStrength');
            
            if (!password) {
                strengthDiv.textContent = '';
                return;
            }
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            let message = '';
            let className = '';
            
            if (strength <= 2) {
                message = 'Слабый пароль';
                className = 'strength-weak';
            } else if (strength <= 4) {
                message = 'Средний пароль';
                className = 'strength-medium';
            } else {
                message = 'Сильный пароль';
                className = 'strength-strong';
            }
            
            strengthDiv.textContent = message;
            strengthDiv.className = 'password-strength ' + className;
        });
        
        // Обработка пожеланий
        document.getElementById('featureRequestForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('.btn-secondary');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Добавление...';
            
            try {
                const response = await fetch('/api.php?action=add_feature_request', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Добавляем новое пожелание в список
                    const featureList = document.getElementById('userFeaturesList');
                    const featureHtml = `
                        <div class="feature-item" data-id="${data.feature.id}">
                            <div class="feature-content">
                                <h4>${escapeHtml(data.feature.title)}</h4>
                                ${data.feature.description ? `<p>${escapeHtml(data.feature.description)}</p>` : ''}
                                <small class="feature-date">
                                    ${new Date().toLocaleDateString('ru-RU')}
                                    <span class="feature-status status-new">Новое</span>
                                </small>
                            </div>
                            <div class="feature-vote">
                                <button class="vote-btn" onclick="voteFeature(${data.feature.id}, this)">
                                    <i class="fas fa-thumbs-up"></i>
                                </button>
                                <span class="vote-count">1</span>
                            </div>
                        </div>
                    `;
                    
                    featureList.insertAdjacentHTML('afterbegin', featureHtml);
                    this.reset();
                    showNotification('Пожелание успешно добавлено', 'success');
                } else {
                    showNotification(data.error || 'Ошибка добавления пожелания', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Ошибка соединения с сервером', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
        
        // Голосование за пожелание
        async function voteFeature(featureId, button) {
            if (button.classList.contains('voted')) return;
            
            const formData = new FormData();
            formData.append('feature_id', featureId);
            formData.append('csrf_token', csrfToken);
            
            try {
                const response = await fetch('/api.php?action=vote_feature', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    button.classList.add('voted');
                    const voteCount = button.nextElementSibling;
                    voteCount.textContent = parseInt(voteCount.textContent) + 1;
                    showNotification('Ваш голос учтен', 'success');
                } else {
                    showNotification(data.error || 'Ошибка голосования', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Ошибка соединения с сервером', 'error');
            }
        }
        
        // Открытие модального окна с тарифами
        async function openUpgradeModal() {
            document.getElementById('upgradeModal').classList.add('active');
            
            try {
                const response = await fetch('/api.php?action=get_tariffs');
                const data = await response.json();
                
                if (data.success) {
                    const tariffsHtml = data.tariffs.map(tariff => `
                        <div class="tariff-card ${tariff.id == <?php echo $userTariff['id']; ?> ? 'current' : ''}" 
                             style="background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 2px solid ${tariff.id == <?php echo $userTariff['id']; ?> ? 'var(--secondary-color)' : 'transparent'};">
                            <div class="tariff-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <h3 style="margin: 0; color: #2c3e50;">${escapeHtml(tariff.name)}</h3>
                                <div class="tariff-price" style="font-size: 24px; font-weight: bold; color: var(--secondary-color);">
                                    ${tariff.price == 0 ? 'Бесплатно' : `${tariff.price} ₽`}
                                </div>
                            </div>
                            <div class="tariff-description" style="margin-bottom: 20px;">
                                <p style="color: #7f8c8d; margin: 0;">${escapeHtml(tariff.description)}</p>
                            </div>
                            <div class="tariff-features" style="margin-bottom: 25px;">
                                <ul style="list-style: none; padding: 0; margin: 0;">
                                    <li style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                        <i class="fas fa-check" style="color: var(--success-color);"></i>
                                        <span>До ${tariff.max_presentations} презентаций</span>
                                    </li>
                                    <li style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                        <i class="fas fa-check" style="color: var(--success-color);"></i>
                                        <span>${tariff.duration_days == 0 ? 'Навсегда' : `${tariff.duration_days} дней`}</span>
                                    </li>
                                    ${tariff.features ? JSON.parse(tariff.features).map(feature => 
                                        `<li style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                            <i class="fas fa-check" style="color: var(--success-color);"></i>
                                            <span>${escapeHtml(feature)}</span>
                                        </li>`
                                    ).join('') : ''}
                                </ul>
                            </div>
                            <button class="btn ${tariff.id == <?php echo $userTariff['id']; ?> ? 'btn-secondary' : 'btn-primary'}" 
                                    style="width: 100%;"
                                    ${tariff.id == <?php echo $userTariff['id']; ?> ? 'disabled' : ''}
                                    onclick="selectTariff(${tariff.id})">
                                ${tariff.id == <?php echo $userTariff['id']; ?> ? 'Текущий тариф' : 
                                  (tariff.price == 0 ? 'Выбрать бесплатно' : 'Оплатить')}
                            </button>
                        </div>
                    `).join('');
                    
                    document.getElementById('tariffsList').innerHTML = tariffsHtml;
                } else {
                    document.getElementById('tariffsList').innerHTML = '<div class="alert alert-error">Ошибка загрузки тарифов</div>';
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('tariffsList').innerHTML = '<div class="alert alert-error">Ошибка соединения с сервером</div>';
            }
        }
        
        // Закрытие модального окна тарифов
        document.getElementById('upgradeModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
        
        // Выбор тарифа
        async function selectTariff(tariffId) {
            try {
                const formData = new FormData();
                formData.append('tariff_id', tariffId);
                formData.append('csrf_token', csrfToken);
                
                const response = await fetch('/api.php?action=create_payment', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    if (data.payment_url) {
                        // Перенаправляем на страницу оплаты
                        window.location.href = data.payment_url;
                    } else {
                        // Бесплатный тариф
                        showNotification('Тариф успешно изменен', 'success');
                        document.getElementById('upgradeModal').classList.remove('active');
                        setTimeout(() => location.reload(), 1500);
                    }
                } else {
                    showNotification(data.error || 'Ошибка выбора тарифа', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Ошибка соединения с сервером', 'error');
            }
        }
        
        // Утилита для экранирования HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Функция показа уведомлений
        function showNotification(message, type = 'success') {
            // Удаляем существующие уведомления
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(notification => notification.remove());
            
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                color: white;
                font-weight: 600;
                z-index: 10000;
                animation: slideIn 0.3s ease;
                display: flex;
                align-items: center;
                gap: 10px;
                background: ${type === 'success' ? '#27ae60' : '#e74c3c'};
            `;
            
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${escapeHtml(message)}
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideIn 0.3s ease reverse';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>