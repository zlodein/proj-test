<?php
// lk-functions/header.php
function renderDashboardHeader() {
    $user = $GLOBALS['user'] ?? getCurrentUser();
    $userTariff = $GLOBALS['userTariff'] ?? null;
    $remainingPresentations = $GLOBALS['remainingPresentations'] ?? 0;
    $tariffModel = $GLOBALS['tariffModel'] ?? new Tariff();
    
    ob_start();
    ?>
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
        <?php 
        if (isset($GLOBALS['success']) && $GLOBALS['success']): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo escape($GLOBALS['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($GLOBALS['error']) && $GLOBALS['error']): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo escape($GLOBALS['error']); ?>
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
    <?php
    return ob_get_clean();
}
?>