<?php
// lk-functions/footer.php
function renderDashboardFooter() {
    ob_start();
    ?>
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
    <?php
    return ob_get_clean();
}
?>