<?php
// Получаем права пользователя по тарифу
$permissions = getUserTariffPermissions();
$canPrint = $permissions['can_print'];
$canShare = $permissions['can_share'];
$canCreatePublicLink = $permissions['can_public_link'];
$remainingLinks = getRemainingPublicLinks();

// Проверяем, есть ли уже публичная ссылка
$publicUrl = $presentation['public_url'] ?? null;
$isPublic = $presentation['is_public'] ?? 0;
$publicHash = $presentation['public_hash'] ?? null;
?>
<div class="editor-header">
    <div class="header-content">
        <div class="editor-title">
            <a href="/index.php" class="btn-back" title="Вернуться к списку">
                <i class="fas fa-arrow-left"></i>
            </a>
            <input type="text" class="title-input" id="presentationTitle" 
                value="<?php echo escape($presentation['title']); ?>" 
                placeholder="Название презентации">
            <div class="auto-save-badge" id="autoSaveIndicator">
                <i class="fas fa-check-circle"></i>
                <span>Сохранено</span>
            </div>
            
            <?php if ($isPublic && $publicUrl): ?>
            <div class="public-link-badge">
                <i class="fas fa-globe"></i>
                <span>Публичная ссылка активна</span>
                <button class="btn-copy-link" onclick="copyPublicLink('<?php echo $publicUrl; ?>')" 
                    title="Копировать ссылку">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
            <?php endif; ?>
        </div>
        <div class="editor-actions">
            <!-- Основная группа кнопок действий -->
            <div class="action-group-main">
                <button class="btn btn-preview" onclick="previewPresentation()">
                    <i class="fas fa-eye"></i> Просмотр
                </button>
                
                <?php if ($canPrint): ?>
                <button class="btn btn-export" onclick="exportToPDF()">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
                <?php else: ?>
                <button class="btn btn-export disabled" title="Экспорт в PDF доступен в платных тарифах" 
                    onclick="showUpgradeModal()">
                    <i class="fas fa-file-pdf"></i> PDF
                    <span class="pro-badge">PRO</span>
                </button>
                <?php endif; ?>
                
                <button class="btn btn-save" id="btnSave" onclick="savePresentation(false, 'published')">
                    <i class="fas fa-save"></i> Опубликовать
                </button>
            </div>
            
            <!-- Кнопка поделиться (упрощенная) -->
            <?php if ($canShare): ?>
            <button class="btn btn-share" onclick="sharePresentation()">
                <i class="fas fa-share-alt"></i> Поделиться
            </button>
            <?php else: ?>
            <button class="btn btn-share disabled" title="Функция 'Поделиться' доступна в платных тарифах" 
                onclick="showUpgradeModal()">
                <i class="fas fa-share-alt"></i> Поделиться
                <span class="pro-badge">PRO</span>
            </button>
            <?php endif; ?>
            
            <!-- Меню настроек -->
            <div class="settings-menu">
                <button class="btn btn-settings" title="Настройки">
                    <i class="fas fa-cog"></i>
                </button>
                <div class="settings-menu-content">
                    <div class="settings-group">
                        <label for="themeColorPicker" class="settings-label">Цвет темы</label>
                        <input type="color" id="themeColorPicker" value="<?php echo $themeColor; ?>" 
                            class="color-picker" title="Выберите цвет темы">
                    </div>
                    
                    <div class="settings-divider"></div>
                    
                    <div class="settings-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="showAllCurrencies" <?php echo $showAllCurrencies ? 'checked' : ''; ?>>
                            <span>Все валюты</span>
                        </label>
                    </div>
                    
                    <div class="settings-divider"></div>
                    
                    <div class="settings-group tariff-group">
                        <div class="tariff-label">Тариф: <?php echo $permissions['tariff_name']; ?></div>
                        <?php if (!$canPrint || !$canShare): ?>
                        <button class="btn-upgrade-settings" onclick="showUpgradeModal()">
                            <i class="fas fa-rocket"></i> Обновить
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.editor-header {background: #2c3e50;color: white;padding: 10px 20px;box-shadow: 0 2px 10px rgba(0,0,0,0.1);position: sticky;top: 0;z-index: 1000;}
.header-content {max-width: 1400px;margin: 0 auto;display: flex;justify-content: space-between;align-items: center;flex-wrap: wrap;gap: 15px;}
.editor-title {display: flex;align-items: center;gap: 15px;flex: 1;min-width: 300px;}
.btn-back {color: white;font-size: 18px;text-decoration: none;padding: 5px;border-radius: 4px;transition: background 0.3s;flex-shrink: 0;}
.btn-back:hover {background: rgba(255,255,255,0.1);}
.title-input {background: transparent;border: 1px solid rgba(255,255,255,0.3);color: white;padding: 8px 15px;border-radius: 4px;font-size: 16px;font-weight: 500;flex: 1;min-width: 200px;}
.title-input:focus {outline: none;border-color: #3498db;box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.3);}
.auto-save-badge {background: #27ae60;padding: 5px 10px;border-radius: 4px;font-size: 12px;display: flex;align-items: center;gap: 5px;white-space: nowrap;flex-shrink: 0;}
.public-link-badge {background: #3498db;padding: 5px 10px;border-radius: 4px;font-size: 12px;display: flex;align-items: center;gap: 5px;flex-shrink: 0;}
.btn-copy-link {background: transparent;border: none;color: white;cursor: pointer;padding: 2px 5px;border-radius: 3px;}
.btn-copy-link:hover {background: rgba(255,255,255,0.2);}
.editor-actions {display: flex;align-items: center;gap: 8px;flex-wrap: wrap;justify-content: flex-end;}
.action-group-main {display: flex;gap: 6px;background: rgba(0,0,0,0.2);padding: 4px;border-radius: 6px;flex-wrap: wrap;}
.btn {background: #3498db;color: white;border: none;padding: 8px 12px;border-radius: 4px;cursor: pointer;font-size: 13px;display: flex;align-items: center;gap: 6px;transition: all 0.3s;font-weight: 500;white-space: nowrap;}
.btn:hover {background: #2980b9;transform: translateY(-1px);}
.btn.disabled {background: #7f8c8d;cursor: not-allowed;opacity: 0.7;}
.btn.disabled:hover {background: #7f8c8d;transform: none;}
.btn-preview {background: #9b59b6;}
.btn-preview:hover {background: #8e44ad;}
.btn-export {background: #e74c3c;}
.btn-export:hover {background: #c0392b;}
.btn-save {background: #2ecc71;}
.btn-save:hover {background: #27ae60;}
.btn-share {background: #f39c12;}
.btn-share:hover {background: #d35400;}
.btn-settings {background: #34495e;padding: 8px 12px;flex-shrink: 0;}
.btn-settings:hover {background: #2c3e50;}
.pro-badge {background: #e74c3c;color: white;font-size: 10px;padding: 2px 6px;border-radius: 10px;margin-left: 3px;}

/* Меню настроек */
.settings-menu {position: relative;display: inline-block;flex-shrink: 0;}
.settings-menu-content {display: none;position: absolute;top: calc(100% + 5px);right: 0;background: white;min-width: 260px;border-radius: 6px;box-shadow: 0 8px 20px rgba(0,0,0,0.15);z-index: 1001;padding: 12px;color: #333;}
.settings-menu:hover .settings-menu-content {display: block;}
.settings-group {margin-bottom: 10px;}
.settings-group:last-child {margin-bottom: 0;}
.settings-label {display: block;font-size: 12px;font-weight: 600;margin-bottom: 6px;color: #666;text-transform: uppercase;letter-spacing: 0.5px;}
.color-picker {width: 100%;height: 36px;border: 2px solid #ddd;border-radius: 4px;cursor: pointer;transition: border-color 0.3s;}
.color-picker:hover {border-color: #3498db;}
.settings-divider {height: 1px;background: #eee;margin: 10px 0;}
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    cursor: pointer;
    user-select: none;
}

.checkbox-label input[type="checkbox"] {
    cursor: pointer;
    width: 16px;
    height: 16px;
}

.checkbox-label span {
    color: #333;
}

.tariff-group {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    margin: 0 -12px -12px -12px;
}

.tariff-label {
    font-size: 12px;
    color: #666;
    font-weight: 500;
}

.btn-upgrade-settings {
    background: #e74c3c;
    color: white;
    border: none;
    padding: 4px 10px;
    border-radius: 3px;
    font-size: 11px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
    margin-top: 6px;
    width: 100%;
    justify-content: center;
    transition: background 0.3s;
}

.btn-upgrade-settings:hover {
    background: #c0392b;
}

/* Уведомления */
.notification {
    position: fixed;
    top: 80px;
    right: 20px;
    background: #27ae60;
    color: white;
    padding: 12px 20px;
    border-radius: 5px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.2);
    z-index: 10000;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: slideIn 0.3s ease;
    font-size: 14px;
}

.notification.error {
    background: #e74c3c;
}

@keyframes slideIn {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Модальное окно апгрейда */
.upgrade-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 2000;
    justify-content: center;
    align-items: center;
}

.upgrade-modal-content {
    background: white;
    border-radius: 10px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.upgrade-modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px 10px 0 0;
    text-align: center;
}

.upgrade-modal-body {
    padding: 30px;
}

.upgrade-features {
    margin: 20px 0;
}

.upgrade-feature {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    padding: 10px;
    border-radius: 5px;
    background: #f8f9fa;
}

.upgrade-feature i {
    color: #e74c3c;
}

.upgrade-modal-footer {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-top: 20px;
}

.btn-modal-close {
    background: #6c757d;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.btn-modal-upgrade {
    background: #28a745;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

/* Responsive */
@media (max-width: 1024px) {
    .editor-actions {
        width: 100%;
    }
    
    .action-group-main {
        flex: 1;
    }
}

@media (max-width: 768px) {
    .header-content {
        gap: 10px;
    }
    
    .editor-title {
        min-width: 100%;
        width: 100%;
    }
    
    .editor-actions {
        width: 100%;
        justify-content: space-between;
    }
    
    .action-group-main {
        flex: 1;
    }
    
    .btn {
        font-size: 12px;
        padding: 6px 10px;
    }
}
</style>

<script>
// Поделиться презентацией - упрощенная версия
function sharePresentation() {
    const presentationId = <?php echo $id; ?>;
    
    // Если уже есть публичная ссылка - копируем её
    <?php if ($isPublic && $publicUrl): ?>
    copyToClipboard('<?php echo $publicUrl; ?>');
    showNotification('Ссылка скопирована в буфер обмена');
    return;
    <?php endif; ?>
    
    // Если можем создавать ссылки - создаем и копируем
    <?php if ($canCreatePublicLink): ?>
    createAndCopyPublicLink(presentationId);
    <?php else: ?>
    showUpgradeModal();
    <?php endif; ?>
}

function createAndCopyPublicLink(presentationId) {
    const formData = new FormData();
    formData.append('presentation_id', presentationId);
    formData.append('enable', '1');
    formData.append('csrf_token', '<?php echo generateCsrfToken(); ?>');
    
    fetch('/api.php?action=toggle_public_link', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.public_url) {
            copyToClipboard(data.public_url);
            showNotification('Публичная ссылка создана и скопирована');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification('Ошибка: ' + (data.error || 'Неизвестная ошибка'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Ошибка при выполнении запроса', 'error');
    });
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text);
}

function copyPublicLink(url) {
    copyToClipboard(url);
    showNotification('Ссылка скопирована в буфер обмена');
}

function showNotification(message, type = 'success') {
    const existing = document.querySelector('.notification');
    if (existing) existing.remove();
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${message}
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideIn 0.3s ease reverse';
            setTimeout(() => notification.remove(), 300);
        }
    }, 3000);
}

function exportToPDF() {
    const presentationId = <?php echo $id; ?>;
    const url = `/api.php?action=export_pdf&id=${presentationId}`;
    
    fetch(url)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.open(data.print_url, '_blank');
        } else {
            showNotification('Ошибка: ' + (data.error || 'Неизвестная ошибка'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Ошибка при выполнении запроса', 'error');
    });
}

function showUpgradeModal() {
    const modal = document.createElement('div');
    modal.className = 'upgrade-modal';
    modal.innerHTML = `
        <div class="upgrade-modal-content">
            <div class="upgrade-modal-header">
                <h2><i class="fas fa-rocket"></i> Обновите тариф</h2>
                <p>Откройте новые возможности для ваших презентаций</p>
            </div>
            <div class="upgrade-modal-body">
                <p>Ваш текущий тариф "<?php echo $permissions['tariff_name']; ?>" имеет ограничения:</p>
                
                <div class="upgrade-features">
                    <?php if (!$canPrint): ?>
                    <div class="upgrade-feature">
                        <i class="fas fa-times-circle"></i>
                        <span>Экспорт в PDF недоступен</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!$canShare): ?>
                    <div class="upgrade-feature">
                        <i class="fas fa-times-circle"></i>
                        <span>Поделиться презентацией</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!$canCreatePublicLink): ?>
                    <div class="upgrade-feature">
                        <i class="fas fa-times-circle"></i>
                        <span>Публичные ссылки</span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <p>Обновите тариф, чтобы получить доступ ко всем функциям!</p>
            </div>
            <div class="upgrade-modal-footer">
                <button class="btn btn-modal-close" onclick="this.closest('.upgrade-modal').remove()">
                    <i class="fas fa-times"></i> Закрыть
                </button>
                <button class="btn btn-modal-upgrade" onclick="window.open('/tariffs.php', '_blank')">
                    <i class="fas fa-arrow-right"></i> Выбрать тариф
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    modal.style.display = 'flex';
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

function previewPresentation() {
    const presentationId = <?php echo $id; ?>;
    const url = `/api.php?action=generate_presentation&id=${presentationId}`;
    window.open(url, '_blank');
}
</script>