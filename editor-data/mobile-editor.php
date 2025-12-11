<?php
$slideTypeNames = [
    'cover' => 'Титульный слайд',
    'image' => 'Слайд на 1 фото',
    'characteristics' => 'Характеристики объекта',
    'gallery' => 'Слайд на 3 фото',
    'features' => 'Особенности',
    'grid' => 'Слайд на 4 фото',
    'description' => 'Описание',
    'infrastructure' => 'Инфраструктура',
    'location' => 'Местоположение',
    'contacts' => 'Контакты'
];
?>

<div class="mobile-editor">
    <!-- Шапка редактора для мобильной версии -->
    <div class="editor-header mobile-header">
        <div class="header-content">
            <div class="editor-title">
                <a href="/index.php" class="btn-back" title="Вернуться к списку">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <input type="text" class="title-input" id="presentationTitle" value="<?php echo htmlspecialchars($presentation['title'] ?? ''); ?>" placeholder="Название презентации">
                <div class="auto-save-badge" id="autoSaveIndicator">
                    <i class="fas fa-check-circle"></i>
                    <span>Сохранено</span>
                </div>
            </div>
            <div class="editor-actions mobile-actions">
                <div class="theme-picker">
                    <label>Цвет темы:</label>
                    <input type="color" id="themeColorPicker" value="<?php echo $themeColor ?? '#000000'; ?>" title="Выберите цвет темы">
                </div>
                
                <div class="currency-display-toggle">
                    <label>
                        <input type="checkbox" id="showAllCurrencies" <?php echo $showAllCurrencies ? 'checked' : ''; ?>>
                        <span>Показывать все валюты в презентации</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Верхняя навигация слайдов -->
    <div class="mob-editor-nav">
        <div class="swiper-container" id="mobNavSwiper">
            <div class="swiper-wrapper">
                <?php foreach ($slides as $index => $slide): ?>
                <div class="swiper-slide mob-editor-nav__slide" 
                     data-slide-index="<?php echo $index; ?>"
                     onclick="switchToSlide(<?php echo $index; ?>)">
                    <?php echo $slideTypeNames[$slide['type']] ?? 'Слайд ' . ($index + 1); ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Основной контейнер редактора -->
    <div class="mob-editor">
        <div class="swiper-container" id="mobEditorSwiper">
            <div class="swiper-wrapper" id="mobSwiperWrapper">
                <!-- Содержимое слайдов будет генерироваться JavaScript -->
            </div>
        </div>
    </div>

    <!-- Кнопки навигации между слайдами с выпадающим меню -->
    <div class="mob-editor-buttons">
        <button class="mob-editor-buttons__prev" onclick="prevSlide()">
            <i class="fas fa-chevron-left"></i>
            Назад
        </button>
        <button class="mob-editor-buttons__next" onclick="nextSlide()">
            Вперед
            <i class="fas fa-chevron-right"></i>
        </button>
        <button class="mob-editor-buttons__menu" onclick="toggleMobileMenu()">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <!-- Выпадающее меню управления -->
    <div class="mob-menu-dropdown" id="mobileMenuDropdown">
        <div class="mob-menu-dropdown__overlay" onclick="closeMobileMenu()"></div>
        <div class="mob-menu-dropdown__content">
            <div class="mob-menu-dropdown__header">
                <h3>Управление презентацией</h3>
                <button class="mob-menu-dropdown__close" onclick="closeMobileMenu()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="mob-menu-dropdown__items">
                <button class="mob-menu-item" onclick="openAddSlideModal()">
                    <i class="fas fa-plus-circle"></i>
                    <span>Добавить слайды</span>
                </button>
                <button class="mob-menu-item mob-menu-item--danger" onclick="deleteCurrentSlide()">
                    <i class="fas fa-trash-alt"></i>
                    <span>Удалить слайд</span>
                </button>
                <button class="mob-menu-item" onclick="moveSlideBackward()">
                    <i class="fas fa-arrow-left"></i>
                    <span>Переместить слайд назад</span>
                </button>
                <button class="mob-menu-item" onclick="moveSlideForward()">
                    <i class="fas fa-arrow-right"></i>
                    <span>Переместить слайд вперед</span>
                </button>
                <button class="mob-menu-item" onclick="toggleCurrencyDisplay()">
                    <i class="fas fa-dollar-sign"></i>
                    <span id="currencyToggleText">
                        <?php echo $showAllCurrencies ? 'Скрыть валюты' : 'Показывать валюты в презентации'; ?>
                    </span>
                </button>
                <button class="mob-menu-item" onclick="openThemeColorPicker()">
                    <i class="fas fa-palette"></i>
                    <span>Цвет темы</span>
                </button>
                <button class="mob-menu-item mob-menu-item--warning" onclick="clearCurrentSlide()">
                    <i class="fas fa-eraser"></i>
                    <span>Очистить поля</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Модальное окно выбора типа слайда -->
    <div class="mob-add-slide-modal" id="mobAddSlideModal">
        <div class="mob-add-slide-modal__overlay" onclick="closeAddSlideModal()"></div>
        <div class="mob-add-slide-modal__content">
            <div class="mob-add-slide-modal__header">
                <h3>Выберите тип слайда</h3>
                <button class="mob-add-slide-modal__close" onclick="closeAddSlideModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="mob-add-slide-modal__types">
                <div class="slide-type-card" onclick="addSlideOfType('cover')">
                    <div class="slide-type-card__icon">📄</div>
                    <div class="slide-type-card__name">Титульный слайд</div>
                </div>
                <div class="slide-type-card" onclick="addSlideOfType('image')">
                    <div class="slide-type-card__icon">🖼️</div>
                    <div class="slide-type-card__name">Слайд на 1 фото</div>
                </div>
                <div class="slide-type-card" onclick="addSlideOfType('characteristics')">
                    <div class="slide-type-card__icon">📋</div>
                    <div class="slide-type-card__name">Характеристики</div>
                </div>
                <div class="slide-type-card" onclick="addSlideOfType('gallery')">
                    <div class="slide-type-card__icon">🖼️</div>
                    <div class="slide-type-card__name">Слайд на 3 фото</div>
                </div>
                <div class="slide-type-card" onclick="addSlideOfType('features')">
                    <div class="slide-type-card__icon">⭐</div>
                    <div class="slide-type-card__name">Особенности</div>
                </div>
                <div class="slide-type-card" onclick="addSlideOfType('grid')">
                    <div class="slide-type-card__icon">🖼️</div>
                    <div class="slide-type-card__name">Слайд на 4 фото</div>
                </div>
                <div class="slide-type-card" onclick="addSlideOfType('description')">
                    <div class="slide-type-card__icon">📝</div>
                    <div class="slide-type-card__name">Описание</div>
                </div>
                <div class="slide-type-card" onclick="addSlideOfType('infrastructure')">
                    <div class="slide-type-card__icon">🏢</div>
                    <div class="slide-type-card__name">Инфраструктура</div>
                </div>
                <div class="slide-type-card" onclick="addSlideOfType('location')">
                    <div class="slide-type-card__icon">📍</div>
                    <div class="slide-type-card__name">Местоположение</div>
                </div>
                <div class="slide-type-card" onclick="addSlideOfType('contacts')">
                    <div class="slide-type-card__icon">📞</div>
                    <div class="slide-type-card__name">Контакты</div>
                </div>
            </div>
        </div>
    </div>

    <!-- В нижней панели исправляем вызовы функций -->
<div class="mob-editor-bottom">
    <div class="mob-editor-bottom__row">
        <div class="mob-editor-bottom__col">
            <a href="javascript:void(0)" onclick="previewMobilePresentation()" class="mob-editor-bottom__watch">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 5C7 5 2.73 8.11 1 12.5C2.73 16.89 7 20 12 20C17 20 21.27 16.89 23 12.5C21.27 8.11 17 5 12 5ZM12 17C9.24 17 7 14.76 7 12C7 9.24 9.24 7 12 7C14.76 7 17 9.24 17 12C17 14.76 14.76 17 12 17ZM12 9C10.34 9 9 10.34 9 12C9 13.66 10.34 15 12 15C13.66 15 15 13.66 15 12C15 10.34 13.66 9 12 9Z" fill="currentColor"/>
                </svg>
                Просмотр
            </a>
        </div>
        <div class="mob-editor-bottom__col">
            <a href="javascript:void(0)" onclick="saveMobilePresentation()" class="mob-editor-bottom__watch">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17 3H5C3.89 3 3 3.9 3 5V19C3 20.1 3.89 21 5 21H19C20.1 21 21 20.1 21 19V7L17 3ZM12 19C10.34 19 9 17.66 9 16C9 14.34 10.34 13 12 13C13.66 13 15 14.34 15 16C15 17.66 13.66 19 12 19ZM15 9H5V5H15V9Z" fill="currentColor"/>
                </svg>
                Сохранить
            </a>
        </div>
        <div class="mob-editor-bottom__col">
            <a href="javascript:void(0)" onclick="exportMobileToPDF()" class="mob-editor-bottom__watch">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M14 2H6C4.9 2 4 2.9 4 4V20C4 21.1 4.89 22 5.99 22H18C19.1 22 20 21.1 20 20V8L14 2ZM18 20H6V4H13V9H18V20Z" fill="currentColor"/>
                </svg>
                Экспорт
            </a>
        </div>
        <div class="mob-editor-bottom__col">
            <a href="javascript:void(0)" onclick="downloadMobilePresentation()">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 12V19H5V12H3V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V12H19ZM13 12.67L15.59 10.09L17 11.5L12 16.5L7 11.5L8.41 10.09L11 12.67V3H13V12.67Z" fill="currentColor"/>
                </svg>
                Скачать
            </a>
        </div>
        <div class="mob-editor-bottom__col">
            <a href="javascript:void(0)" onclick="shareMobilePresentation()">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 16.08C17.24 16.08 16.56 16.38 16.04 16.85L8.91 12.7C8.96 12.47 9 12.24 9 12C9 11.76 8.96 11.53 8.91 11.3L15.96 7.19C16.5 7.69 17.21 8 18 8C19.66 8 21 6.66 21 5C21 3.34 19.66 2 18 2C16.34 2 15 3.34 15 5C15 5.24 15.04 5.47 15.09 5.7L8.04 9.81C7.5 9.31 6.79 9 6 9C4.34 9 3 10.34 3 12C3 13.66 4.34 15 6 15C6.79 15 7.5 14.69 8.04 14.19L15.16 18.35C15.11 18.56 15.08 18.78 15.08 19C15.08 20.61 16.39 21.92 18 21.92C19.61 21.92 20.92 20.61 20.92 19C20.92 17.39 19.61 16.08 18 16.08Z" fill="currentColor"/>
                </svg>
                Поделиться
            </a>
        </div>
    </div>
</div>
</div>

<!-- Модальное окно для выбора типа сделки/валюты -->
<div class="mob-editor__choice" id="choiceModal">
<div class="mob-editor__list" id="choiceList"></div>
</div>