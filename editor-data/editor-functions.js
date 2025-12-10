// Основные функции редактора

// Форматирование чисел с разделителями
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

// Парсинг отформатированного числа
function parseFormattedNumber(str) {
    return parseInt(str.replace(/\s/g, '')) || 0;
}

// Получение символа валюты
function getCurrencySymbol(currency) {
    return currencySymbols[currency] || currency;
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
    await updateCurrencyConversions(slideIndex);
    hasUnsavedChanges = true;
    triggerAutoSave();
}

// Обновление типа сделки (аренда/продажа)
async function updateDealType(slideIndex, dealType) {
    const slide = slides[slideIndex];
    slide.deal_type = dealType;
    
    const priceInput = document.querySelector(`[data-slide="${slideIndex}"][data-field="price_value"]`);
    if (priceInput) {
        priceInput.placeholder = dealType === 'Аренда' ? '0 000 000 / месяц' : '0 000 000';
    }
    
    await updateCurrencyConversions(slideIndex);
    hasUnsavedChanges = true;
    triggerAutoSave();
}

// Обновление валюты
async function updateCurrency(slideIndex, currency) {
    const slide = slides[slideIndex];
    const oldCurrency = slide.currency || 'RUB';
    slide.currency = currency;
    
    if (oldCurrency !== currency && slide.price_value) {
        await convertPrice(slideIndex, oldCurrency, currency);
    }
    
    await updateCurrencyConversions(slideIndex);
    hasUnsavedChanges = true;
    triggerAutoSave();
}

// Рендеринг всех слайдов
function renderSlides() {
    const wrapper = document.getElementById('swiperWrapper');
    if (!wrapper) return;
    
    wrapper.innerHTML = '';
    slides.forEach((slide, index) => {
        const slideHtml = generateSlideHTML(slide, index);
        wrapper.insertAdjacentHTML('beforeend', slideHtml);
    });
    
    setTimeout(() => attachSlideEvents(), 100);
}

// Привязка событий к элементам слайдов
function attachSlideEvents() {
    // Обработка полей с contenteditable
    document.querySelectorAll('[contenteditable="true"][data-field]').forEach(field => {
        field.addEventListener('input', function() {
            const slideIndex = parseInt(this.dataset.slide);
            const fieldName = this.dataset.field;
            slides[slideIndex][fieldName] = this.innerHTML;
            hasUnsavedChanges = true;
            triggerAutoSave();
        });
        
        field.addEventListener('focus', function() {
            this.style.whiteSpace = 'pre-wrap';
        });
        
        field.addEventListener('blur', function() {
            this.innerHTML = this.innerHTML
                .replace(/ style="[^"]*"/g, '')
                .replace(/ class="[^"]*"/g, '')
                .replace(/<br\s*\/?>/g, '<br>')
                .replace(/\n/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();
        });
    });
    
    // Обработка полей характеристик
    document.querySelectorAll('[data-char-field]').forEach(field => {
        field.addEventListener('input', function() {
            const slideIndex = parseInt(this.dataset.slide);
            const itemIndex = parseInt(this.dataset.item);
            const fieldName = this.dataset.charField;
            
            if (!slides[slideIndex].items) slides[slideIndex].items = [];
            if (!slides[slideIndex].items[itemIndex]) slides[slideIndex].items[itemIndex] = {};
            
            slides[slideIndex].items[itemIndex][fieldName] = this.textContent;
            hasUnsavedChanges = true;
            triggerAutoSave();
        });
        
        field.addEventListener('focus', function() {
            this.style.whiteSpace = 'pre-wrap';
        });
        
        field.addEventListener('blur', function() {
            this.textContent = this.textContent.trim();
        });
    });
    
    // Обработка полей особенностей
    document.querySelectorAll('[data-feature-field]').forEach(field => {
        field.addEventListener('input', function() {
            const slideIndex = parseInt(this.dataset.slide);
            const itemIndex = parseInt(this.dataset.item);
            const fieldName = this.dataset.featureField;
            
            if (!slides[slideIndex].items) slides[slideIndex].items = [];
            if (!slides[slideIndex].items[itemIndex]) slides[slideIndex].items[itemIndex] = {};
            
            slides[slideIndex].items[itemIndex][fieldName] = this.textContent;
            hasUnsavedChanges = true;
            triggerAutoSave();
        });
        
        field.addEventListener('focus', function() {
            this.style.whiteSpace = 'pre-wrap';
        });
        
        field.addEventListener('blur', function() {
            this.textContent = this.textContent.trim();
        });
    });
    
    // Обработка загрузки файлов
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', async function(e) {
            if (e.target.files && e.target.files[0]) {
                const result = await handleFileUpload(e.target.files[0], this);
                
                // ИСПРАВЛЕНИЕ: сбрасываем значение input после загрузки
                this.value = '';
                
                // Если загрузка успешна, обновляем слайд
                if (result && result.success) {
                    refreshSlide(result.slideIndex);
                }
            }
        });
    });
    
    // Обработка полей цены
    document.querySelectorAll('.price-input').forEach(input => {
        input.addEventListener('input', function() {
            formatPriceInput(this);
            
            // ИСПРАВЛЕНИЕ: обновляем валюты в реальном времени
            const slideIndex = parseInt(this.dataset.slide);
            updatePriceValue(slideIndex, this.value);
        });
        
        input.addEventListener('blur', function() {
            const slideIndex = parseInt(this.dataset.slide);
            updatePriceValue(slideIndex, this.value);
        });
    });
    
    // Обработка выбора типа сделки и валюты
    document.querySelectorAll('.deal-type-select, .currency-select').forEach(select => {
        select.addEventListener('change', function() {
            const slideIndex = parseInt(this.dataset.slide);
            const fieldName = this.dataset.field;
            const value = this.value;
            
            slides[slideIndex][fieldName] = value;
            
            if (fieldName === 'deal_type') {
                updateDealType(slideIndex, value);
            } else if (fieldName === 'currency') {
                updateCurrency(slideIndex, value);
            }
        });
    });
}

// Инициализация Swiper
function initSwiper() {
    renderSlides();
    
    try {
        swiper = new Swiper('#editorSwiper', {
            slidesPerView: 1,
            spaceBetween: 30,
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            on: {
                slideChange: function() {
                    if (hasUnsavedChanges) {
                        savePresentation(true, 'draft');
                    }
                }
            }
        });
    } catch (error) {
        console.error('Failed to initialize Swiper:', error);
    }
}

// Добавление особенности
function addFeature(slideIndex) {
    if (!slides[slideIndex].items) slides[slideIndex].items = [];
    
    if (slides[slideIndex].items.length >= 9) {
        alert('Максимальное количество особенностей - 9');
        return;
    }
    
    slides[slideIndex].items.push({ text: 'НОВАЯ ОСОБЕННОСТЬ' });
    refreshSlide(slideIndex);
    hasUnsavedChanges = true;
    triggerAutoSave();
}

// Удаление особенности
function removeFeature(slideIndex, itemIndex) {
    if (confirm('Удалить эту особенность?')) {
        slides[slideIndex].items.splice(itemIndex, 1);
        refreshSlide(slideIndex);
        hasUnsavedChanges = true;
        triggerAutoSave();
    }
}

// Добавление характеристики
function addCharacteristic(slideIndex) {
    if (!slides[slideIndex].items) slides[slideIndex].items = [];
    
    if (slides[slideIndex].items.length >= 12) {
        alert('Максимальное количество характеристик - 12');
        return;
    }
    
    slides[slideIndex].items.push({ label: 'Название характеристики', value: 'Значение' });
    refreshSlide(slideIndex);
    hasUnsavedChanges = true;
    triggerAutoSave();
}

// Удаление характеристики
function removeCharacteristic(slideIndex, itemIndex) {
    if (confirm('Удалить эту характеристику?')) {
        slides[slideIndex].items.splice(itemIndex, 1);
        refreshSlide(slideIndex);
        hasUnsavedChanges = true;
        triggerAutoSave();
    }
}

// Обновление слайда
function refreshSlide(index) {
    const slideElement = document.querySelector(`[data-slide-index="${index}"]`);
    if (slideElement && swiper) {
        const newHtml = generateSlideHTML(slides[index], index);
        const temp = document.createElement('div');
        temp.innerHTML = newHtml;
        
        slideElement.parentNode.replaceChild(temp.firstElementChild, slideElement);
        swiper.update();
        swiper.updateSlides();
        swiper.updateSlidesClasses();
        attachSlideEvents();
    }
}