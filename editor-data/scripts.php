<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>

<script>
    // Конфигурация и инициализация
    const presentationId = <?php echo $id; ?>;
    const csrfToken = '<?php echo generateCsrfToken(); ?>';
    let slides = <?php echo json_encode($slides); ?>;
    let swiper = null;
    let autoSaveTimer = null;
    let idleTimer = null;
    let currentThemeColor = '<?php echo $themeColor; ?>';
    let hasUnsavedChanges = false;
    let lastActivityTime = Date.now();
    const AUTOSAVE_DELAY = 5000;
    const IDLE_SAVE_DELAY = 300000;
    
    // Данные для валют
    let currencyRates = null;
    let currencySymbols = {
        'RUB': '₽',
        'USD': '$',
        'EUR': '€',
        'CNY': '¥',
        'KZT': '₸'
    };

    // Генераторы слайдов
    const slideGenerators = {};

    // Шаблоны слайдов по умолчанию
    const defaultSlides = {
        'cover': { 
            type: 'cover', 
            title: 'ЭКСКЛЮЗИВНОЕ<br>ПРЕДЛОЖЕНИЕ', 
            subtitle: 'АБСОЛЮТНО НОВЫЙ ТАУНХАУС<br>НА ПЕРВОЙ ЛИНИИ', 
            deal_type: 'Аренда', 
            currency: 'RUB',
            price_value: 1000000,
            price: '1 000 000 ₽ / месяц', 
            background_image: '', 
            hidden: false 
        },
        'description': { 
            type: 'description', 
            title: 'ОПИСАНИЕ', 
            content: 'Подробно опишите о своем объекте...', 
            images: [], 
            hidden: false 
        },
        'infrastructure': { 
            type: 'infrastructure', 
            title: 'ИНФРАСТРУКТУРА', 
            content: 'Подробно опишите, что находится вблизи...', 
            images: [], 
            hidden: false 
        },
        'features': { 
            type: 'features', 
            title: 'ОСОБЕННОСТИ', 
            items: [{ text: 'ПАНОРАМНЫЙ ВИД' }], 
            images: [], 
            hidden: false 
        },
        'location': { 
            type: 'location', 
            title: 'МЕСТОПОЛОЖЕНИЕ', 
            location_name: 'ЖК "Новый"', 
            location_address: 'Адрес объекта', 
            hidden: false 
        },
        'contacts': { 
            type: 'contacts', 
            contact_title: 'Контакты', 
            contact_name: 'Имя Фамилия', 
            contact_role: 'Риелтор', 
            contact_phone: '+7 (900) 000-00-00', 
            contact_messengers: 'Telegram | WhatsApp', 
            images: [], 
            avatar: '', 
            hidden: false 
        },
        'image': { 
            type: 'image', 
            image: '', 
            hidden: false 
        },
        'gallery': { 
            type: 'gallery', 
            images: [], 
            hidden: false 
        },
        'grid': { 
            type: 'grid', 
            images: [], 
            hidden: false 
        },
        'characteristics': { 
            type: 'characteristics', 
            title: 'ХАРАКТЕРИСТИКИ КВАРТИРЫ', 
            items: [
                { label: 'Площадь квартиры:', value: '350 кв.м.' },
                { label: 'Количество комнат:', value: '5' }
            ], 
            image: '', 
            hidden: false 
        }
    };
    
    // Инициализация функций-генераторов
    function initSlideGenerators() {
        slideGenerators['cover'] = generateCoverSlide;
        slideGenerators['image'] = generateImageSlide;
        slideGenerators['gallery'] = generateGallerySlide;
        slideGenerators['characteristics'] = generateCharacteristicsSlide;
        slideGenerators['grid'] = generateGridSlide;
        slideGenerators['description'] = generateDescriptionSlide;
        slideGenerators['infrastructure'] = generateInfrastructureSlide;
        slideGenerators['features'] = generateFeaturesSlide;
        slideGenerators['location'] = generateLocationSlide;
        slideGenerators['contacts'] = generateContactsSlide;
    }

    // ===========================
    // БАЗОВЫЕ ФУНКЦИИ ДЛЯ РАБОТЫ С ДАННЫМИ
    // ===========================

    // Форматирование чисел с разделителями
    function formatNumber(num) {
        if (!num && num !== 0) return '';
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }

    // Парсинг отформатированного числа
    function parseFormattedNumber(str) {
        if (!str) return 0;
        return parseInt(str.replace(/\s/g, '')) || 0;
    }

    // Получение символа валюты
    function getCurrencySymbol(currency) {
        return currencySymbols[currency] || currency;
    }

    // Форматирование поля ввода цены
    function formatPriceInput(input) {
        let value = input.value.replace(/\s/g, '');
        value = value.replace(/\D/g, '');
        
        if (value) {
            input.value = formatNumber(parseInt(value));
        } else {
            input.value = '';
        }
    }

    // Обновление значения цены
    async function updatePriceValue(slideIndex, formattedValue) {
        const slide = slides[slideIndex];
        const rawValue = parseFormattedNumber(formattedValue);
        
        slide.price_value = rawValue;
        if (typeof updateCurrencyConversions === 'function') {
            await updateCurrencyConversions(slideIndex);
        }
        hasUnsavedChanges = true;
        triggerAutoSave();
    }

    // Триггер автосохранения
    function triggerAutoSave() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(() => {
            if (hasUnsavedChanges) {
                savePresentation(true, 'draft');
            }
        }, AUTOSAVE_DELAY);
    }

    // Функция сохранения презентации
    async function savePresentation(isAuto = false, status = 'draft') {
        const btnSave = document.getElementById('btnSave');
        const isPublishing = status === 'published' && !isAuto;
        
        if (isPublishing && btnSave) {
            btnSave.disabled = true;
            btnSave.innerHTML = '<div class="spinner"></div> Публикация...';
        }
        
        // Показываем статус сохранения
        const saveIndicator = document.getElementById('saveIndicator');
        if (saveIndicator) {
            saveIndicator.style.display = 'block';
            saveIndicator.className = 'save-indicator saving';
            saveIndicator.querySelector('#saveText').innerHTML = 'Сохранение...';
        }
        
        // Форматируем цену для слайда обложки
        slides.forEach(slide => {
            if (slide.type === 'cover' && slide.price_value !== undefined) {
                const formatted = formatNumber(slide.price_value);
                const symbol = getCurrencySymbol(slide.currency || 'RUB');
                const isRent = slide.deal_type === 'Аренда';
                
                slide.price = formatted + ' ' + symbol + (isRent ? ' / месяц' : '');
            }
        });
        
        const formData = new FormData();
        formData.append('id', presentationId);
        formData.append('title', document.getElementById('presentationTitle')?.value || '');
        formData.append('slides_data', JSON.stringify(slides));
        formData.append('theme_color', currentThemeColor);
        formData.append('status', status);
        formData.append('csrf_token', csrfToken);
        formData.append('show_all_currencies', document.getElementById('showAllCurrencies')?.checked || false);
        
        const coverSlide = slides.find(s => s.type === 'cover');
        if (coverSlide && coverSlide.background_image) {
            formData.append('cover_image', coverSlide.background_image);
        }
        
        try {
            const response = await fetch('/api.php?action=update_presentation', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                hasUnsavedChanges = false;
                
                // Показываем статус сохранено
                if (saveIndicator) {
                    saveIndicator.className = 'save-indicator saved';
                    saveIndicator.querySelector('#saveText').innerHTML = '<i class="fas fa-check"></i> Сохранено';
                    setTimeout(() => saveIndicator.style.display = 'none', 2500);
                }
                
                if (isPublishing) {
                    showNotification('Презентация успешно опубликована!', 'success');
                    if (btnSave) {
                        setTimeout(() => {
                            btnSave.disabled = false;
                            btnSave.innerHTML = '<i class="fas fa-save"></i> Опубликовать';
                        }, 500);
                    }
                }
            } else {
                // Показываем ошибку
                if (saveIndicator) {
                    saveIndicator.className = 'save-indicator error';
                    saveIndicator.querySelector('#saveText').innerHTML = '<i class="fas fa-exclamation-triangle"></i> Ошибка';
                    setTimeout(() => saveIndicator.style.display = 'none', 3000);
                }
                
                if (!isAuto) {
                    showNotification('Ошибка сохранения: ' + (data.message || 'Unknown error'), 'error');
                    if (isPublishing && btnSave) {
                        btnSave.disabled = false;
                        btnSave.innerHTML = '<i class="fas fa-save"></i> Опубликовать';
                    }
                }
            }
        } catch (error) {
            console.error('Save error:', error);
            
            // Показываем ошибку
            if (saveIndicator) {
                saveIndicator.className = 'save-indicator error';
                saveIndicator.querySelector('#saveText').innerHTML = '<i class="fas fa-exclamation-triangle"></i> Ошибка';
                setTimeout(() => saveIndicator.style.display = 'none', 3000);
            }
            
            if (!isAuto) {
                showNotification('Ошибка соединения с сервером', 'error');
                if (isPublishing && btnSave) {
                    btnSave.disabled = false;
                    btnSave.innerHTML = '<i class="fas fa-save"></i> Опубликовать';
                }
            }
        }
    }

    // Показ уведомления
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

    // Просмотр презентации
    function previewPresentation() {
        savePresentation(false, 'draft').then(() => {
            window.open(`/api.php?action=generate_presentation&id=${presentationId}`, '_blank');
        });
    }

    // Экспорт в PDF
    function exportToPDF() {
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

    // Копирование публичной ссылки
    function copyPublicLink(url) {
        navigator.clipboard.writeText(url).then(() => {
            showNotification('Ссылка скопирована в буфер обмена!', 'success');
        });
    }

    // Добавление характеристики
    function addCharacteristic(slideIndex) {
        if (!slides[slideIndex].items) slides[slideIndex].items = [];
        
        if (slides[slideIndex].items.length >= 12) {
            showNotification('Максимальное количество характеристик - 12', 'error');
            return;
        }
        
        slides[slideIndex].items.push({ label: 'Название', value: 'Значение' });
        hasUnsavedChanges = true;
        triggerAutoSave();
        showNotification('Характеристика добавлена');
    }

    // Удаление характеристики
    function removeCharacteristic(slideIndex, itemIndex) {
        if (confirm('Удалить эту характеристику?')) {
            slides[slideIndex].items.splice(itemIndex, 1);
            hasUnsavedChanges = true;
            triggerAutoSave();
            showNotification('Характеристика удалена');
        }
    }

    // Добавление особенности
    function addFeature(slideIndex) {
        if (!slides[slideIndex].items) slides[slideIndex].items = [];
        
        if (slides[slideIndex].items.length >= 9) {
            showNotification('Максимальное количество особенностей - 9', 'error');
            return;
        }
        
        slides[slideIndex].items.push({ text: 'НОВАЯ ОСОБЕННОСТЬ' });
        hasUnsavedChanges = true;
        triggerAutoSave();
        showNotification('Особенность добавлена');
    }

    // Удаление особенности
    function removeFeature(slideIndex, itemIndex) {
        if (confirm('Удалить эту особенность?')) {
            slides[slideIndex].items.splice(itemIndex, 1);
            hasUnsavedChanges = true;
            triggerAutoSave();
            showNotification('Особенность удалена');
        }
    }

    // Применение цвета темы
    function applyThemeColor(color) {
        currentThemeColor = color;
        
        // Применяем цвет ко всем элементам с классом theme-color
        document.querySelectorAll('.theme-color').forEach(el => {
            el.style.backgroundColor = color;
        });
        
        // Обновляем в данных презентации
        slides.forEach(slide => {
            if (slide.theme_color !== undefined) {
                slide.theme_color = color;
            }
        });
        
        hasUnsavedChanges = true;
    }

    // Инициализация при загрузке DOM
    document.addEventListener('DOMContentLoaded', function() {
        // Обработчик изменения цвета темы в реальном времени
        const themeColorPicker = document.getElementById('themeColorPicker');
        if (themeColorPicker) {
            themeColorPicker.addEventListener('input', function(e) {
                const color = e.target.value;
                applyThemeColor(color);
                triggerAutoSave();
            });
        }
        
        // Обработчик чекбокса валют
        const showAllCurrencies = document.getElementById('showAllCurrencies');
        if (showAllCurrencies) {
            showAllCurrencies.addEventListener('change', function() {
                hasUnsavedChanges = true;
                triggerAutoSave();
            });
        }
        
        // Обработчик изменения названия
        const titleInput = document.getElementById('presentationTitle');
        if (titleInput) {
            titleInput.addEventListener('input', function() {
                hasUnsavedChanges = true;
                triggerAutoSave();
            });
        }
    });
</script>

<!-- Загружаем все необходимые скрипты -->
<?php if (isMobileDevice()): ?>
    <!-- Мобильная версия -->
    <script src="/editor-data/slide-management.js?v=<?php echo time(); ?>"></script>
    <script src="/editor-data/currency-functions.js?v=<?php echo time(); ?>"></script>
    <script src="/editor-data/mobile-menu-functions.js?v=<?php echo time(); ?>"></script>
    <script src="/editor-data/mobile-editor.js?v=<?php echo time(); ?>"></script>
    <script src="/editor-data/mobile-slide-controls.js?v=<?php echo time(); ?>"></script>
    <script src="/editor-data/mobile-slide-functions.js?v=<?php echo time(); ?>"></script>
<?php else: ?>
    <!-- Десктопная версия -->
    <script src="/editor-data/slide-generators.js?v=<?php echo time(); ?>"></script>
    <script src="/editor-data/editor-functions.js?v=<?php echo time(); ?>"></script>
    <script src="/editor-data/currency-functions.js?v=<?php echo time(); ?>"></script>
    <script src="/editor-data/slide-management.js?v=<?php echo time(); ?>"></script>
    <script src="/editor-data/init.js?v=<?php echo time(); ?>"></script>
<?php endif; ?>