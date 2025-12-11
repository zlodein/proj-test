// Инициализация цветовой темы
function initThemeColor() {
    applyThemeColor(currentThemeColor);
    
    const colorPicker = document.getElementById('themeColorPicker');
    if (colorPicker) {
        colorPicker.value = currentThemeColor;
        colorPicker.addEventListener('change', function(e) {
            applyThemeColor(e.target.value);
        });
    }
}

// Применение цвета темы
function applyThemeColor(color) {
    currentThemeColor = color;
    document.documentElement.style.setProperty('--theme-main-color', color);
    
    document.querySelectorAll('.booklet-main__img, .booklet-char__top-square, .booklet-char__bottom-square, .booklet-img__top-square, .booklet-img__bottom-square, .booklet-galery__top-square, .booklet-galery__bottom-square, .booklet-grid__top-square, .booklet-grid__bottom-square').forEach(el => {
        el.style.backgroundColor = color;
    });
    
    hasUnsavedChanges = true;
    triggerAutoSave();
}

// Отслеживание активности пользователя
function initActivityTracking() {
    ['mousedown', 'keydown', 'touchstart', 'scroll'].forEach(event => {
        document.addEventListener(event, () => {
            lastActivityTime = Date.now();
            clearTimeout(idleTimer);
            
            idleTimer = setTimeout(() => {
                const idleTime = Date.now() - lastActivityTime;
                if (idleTime >= IDLE_SAVE_DELAY && hasUnsavedChanges) {
                    savePresentation(true, 'draft');
                    showNotification('Автосохранение после бездействия', 'success');
                }
            }, IDLE_SAVE_DELAY);
        }, { passive: true });
    });
    
    setInterval(() => {
        const idleTime = Date.now() - lastActivityTime;
        if (idleTime >= IDLE_SAVE_DELAY && hasUnsavedChanges) {
            savePresentation(true, 'draft');
            showNotification('Автосохранение после бездействия', 'success');
        }
    }, 60000);
}

// Инициализация автосохранения
function initAutoSave() {
    document.addEventListener('input', function(e) {
        if (e.target.closest('[contenteditable="true"]')) {
            hasUnsavedChanges = true;
            triggerAutoSave();
        }
    });
    
    const titleInput = document.getElementById('presentationTitle');
    if (titleInput) {
        titleInput.addEventListener('input', function() {
            hasUnsavedChanges = true;
            triggerAutoSave();
        });
    }
    
    window.addEventListener('beforeunload', (e) => {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = 'У вас есть несохраненные изменения. Вы уверены, что хотите уйти?';
            return 'У вас есть несохраненные изменения. Вы уверены, что хотите уйти?';
        }
    });
}

// Обработчики вставки текста
function initPasteHandlers() {
    document.addEventListener('paste', function(e) {
        const target = e.target;
        if (target.classList.contains('editor-field')) {
            e.preventDefault();
            const text = e.clipboardData.getData('text/plain');
            document.execCommand('insertText', false, text);
            const event = new Event('input', { bubbles: true });
            target.dispatchEvent(event);
        }
    });
}

// Инициализация полей цен
function initPriceFields() {
    slides.forEach((slide, index) => {
        if (slide.type === 'cover') {
            if (!slide.price_value && slide.price) {
                const priceMatch = slide.price.match(/([\d\s]+)/);
                if (priceMatch) {
                    slide.price_value = parseFormattedNumber(priceMatch[1]);
                } else {
                    slide.price_value = 1000000;
                }
            }
            
            if (!slide.currency) {
                slide.currency = 'RUB';
            }
            
            if (!slide.deal_type) {
                slide.deal_type = 'Аренда';
            }
        }
    });
}

// Просмотр презентации
function previewPresentation() {
    savePresentation(false, 'draft').then(() => {
        window.open(`/api.php?action=generate_presentation&id=${presentationId}`, '_blank');
    });
}

// Основная инициализация
document.addEventListener('DOMContentLoaded', function() {
    initSlideGenerators();
    initSwiper();
    initThemeColor();
    initAutoSave();
    initActivityTracking();
    initPasteHandlers();
    initPriceFields();
    loadCurrencyRates();
    
    document.getElementById('showAllCurrencies').addEventListener('change', function() {
        slides.forEach((slide, index) => {
            if (slide.type === 'cover') {
                updateCurrencyConversions(index);
            }
        });
        
        hasUnsavedChanges = true;
        triggerAutoSave();
    });
});