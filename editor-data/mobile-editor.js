// Мобильный редактор презентаций
let mobNavSwiper = null;
let mobEditorSwiper = null;
let currentSlideIndex = 0;

// Синхронизация названия презентации
document.getElementById('mobilePresentationTitle')?.addEventListener('input', function(e) {
    window.presentationTitle = e.target.value;
    markAsModified();
});

// Синхронизация цвета темы
document.getElementById('mobileThemeColorPicker')?.addEventListener('change', function(e) {
    const newColor = e.target.value;
    document.documentElement.style.setProperty('--theme-main-color', newColor);
    window.currentThemeColor = newColor;
    markAsModified();
});

// Синхронизация переключателя валют
document.getElementById('mobileShowAllCurrencies')?.addEventListener('change', function(e) {
    window.showAllCurrencies = e.target.checked;
    markAsModified();
});


// Инициализация мобильного редактора
function initMobileEditor() {
    initMobileNavigation();
    initMobileEditorSwiper();
    renderMobileSlides();
    setupMobileEventListeners();
}

// Инициализация навигации слайдов
function initMobileNavigation() {
    mobNavSwiper = new Swiper('#mobNavSwiper', {
        slidesPerView: 'auto',
        spaceBetween: 20,
        freeMode: true,
        watchSlidesProgress: true,
        slideToClickedSlide: true
    });
}

// Инициализация основного слайдера
function initMobileEditorSwiper() {
    mobEditorSwiper = new Swiper('#mobEditorSwiper', {
        slidesPerView: 1,
        spaceBetween: 0,
        allowTouchMove: true,
        on: {
            slideChange: function() {
                currentSlideIndex = this.activeIndex;
                updateNavigation();
                updateButtons();
            }
        }
    });
}

// Рендеринг мобильных слайдов
function renderMobileSlides() {
    const wrapper = document.getElementById('mobSwiperWrapper');
    wrapper.innerHTML = '';
    
    slides.forEach((slide, index) => {
        const slideHtml = generateMobileSlideHTML(slide, index);
        wrapper.insertAdjacentHTML('beforeend', slideHtml);
    });
    
    setTimeout(() => {
        setupMobileSlideEvents();
        // Загружаем курсы валют для конвертера
        if (typeof loadCurrencyRates === 'function') {
            loadCurrencyRates();
        }
    }, 100);
}

// Генерация HTML для мобильного слайда
function generateMobileSlideHTML(slide, index) {
    switch (slide.type) {
        case 'cover':
            return generateMobileCoverSlide(slide, index);
        case 'image':
            return generateMobileImageSlide(slide, index);
        case 'characteristics':
            return generateMobileCharacteristicsSlide(slide, index);
        case 'gallery':
            return generateMobileGallerySlide(slide, index);
        case 'features':
            return generateMobileFeaturesSlide(slide, index);
        case 'grid':
            return generateMobileGridSlide(slide, index);
        case 'description':
            return generateMobileDescriptionSlide(slide, index);
        case 'infrastructure':
            return generateMobileInfrastructureSlide(slide, index);
        case 'location':
            return generateMobileLocationSlide(slide, index);
        case 'contacts':
            return generateMobileContactsSlide(slide, index);
        default:
            return `<div class="swiper-slide mob-editor__slide">Неизвестный тип слайда: ${slide.type}</div>`;
    }
}

// Генерация слайда "Обложка" для мобильной версии
function generateMobileCoverSlide(slide, index) {
    const dealType = slide.deal_type || 'Аренда';
    const currency = slide.currency || 'RUB';
    const priceValue = slide.price_value || 1000000;
    const formattedPrice = formatNumber(priceValue);
    
    // Убираем <br> из текста для textarea
    const title = (slide.title || '').replace(/<br\s*\/?>/gi, '\n');
    const subtitle = (slide.subtitle || '').replace(/<br\s*\/?>/gi, '\n');
    
    return `
        <div class="swiper-slide mob-editor__slide">
            <div class="mob-editor__block">
                <label class="mob-editor__label">Заголовок</label>
                <textarea class="mob-editor__textarea" 
                          data-slide="${index}" 
                          data-field="title"
                          placeholder="ЭКСКЛЮЗИВНОЕ ПРЕДЛОЖЕНИЕ">${title}</textarea>
            </div>
            
            <div class="mob-editor__block">
                <label class="mob-editor__label">Подзаголовок</label>
                <textarea class="mob-editor__textarea" 
                          data-slide="${index}" 
                          data-field="subtitle"
                          placeholder="АБСОЛЮТНО НОВЫЙ ТАУНХАУС НА ПЕРВОЙ ЛИНИИ">${subtitle}</textarea>
            </div>
            
            <div class="mob-editor__block">
                <label class="mob-editor__label">Фоновое изображение</label>
                <div class="mob-editor__images">
                    ${slide.background_image ? `
                        <div class="mob-editor__image">
                            <img src="${slide.background_image}" alt="Фон">
                            <div class="mob-editor__image-remove" onclick="removeMobileImage(${index}, 'background_image')">×</div>
                        </div>
                    ` : `
                        <div class="mob-editor__image add-image" onclick="uploadMobileImage(${index}, 'background')">
                        </div>
                    `}
                </div>
            </div>
            
            <div class="mob-editor__block">
                <label class="mob-editor__label">Цена</label>
                <div class="price-mobile-input">
                    <input type="text" 
                           class="price-mobile-input__field"
                           value="${formattedPrice}"
                           data-slide="${index}"
                           data-field="price_value"
                           oninput="formatPriceInput(this); updateMobilePrice(${index}, this.value)">
                    <div class="price-mobile-input__type" onclick="showChoiceModal(${index}, 'deal_type')">
                        ${dealType === 'Аренда' ? 'Аренда' : 'Продажа'}
                    </div>
                    <div class="price-mobile-input__currency" onclick="showChoiceModal(${index}, 'currency')">
                        ${getCurrencySymbol(currency)}
                    </div>
                </div>
                
                <!-- Конвертер валют -->
                <div id="currency-converter-${index}" class="mobile-currency-converter">
                    <div class="converted-prices"></div>
                </div>
            </div>
        </div>
    `;
}

// Генерация слайда "Изображение" для мобильной версии
function generateMobileImageSlide(slide, index) {
    return `
        <div class="swiper-slide mob-editor__slide">
            <div class="mob-editor__block">
                <label class="mob-editor__label">Основное изображение</label>
                <div class="mob-editor__images">
                    ${slide.image ? `
                        <div class="mob-editor__image">
                            <img src="${slide.image}" alt="Изображение">
                            <div class="mob-editor__image-remove" onclick="removeMobileImage(${index}, 'image')">×</div>
                        </div>
                    ` : `
                        <div class="mob-editor__image add-image" onclick="uploadMobileImage(${index}, 'single')">
                        </div>
                    `}
                </div>
            </div>
        </div>
    `;
}

// Генерация слайда "Характеристики" для мобильной версии
function generateMobileCharacteristicsSlide(slide, index) {
    const items = slide.items || [];
    
    return `
        <div class="swiper-slide mob-editor__slide">
            <div class="mob-editor__block">
                <label class="mob-editor__label">Заголовок</label>
                <input type="text" 
                       class="mob-editor__input"
                       data-slide="${index}"
                       data-field="title"
                       value="${slide.title || 'ХАРАКТЕРИСТИКИ КВАРТИРЫ'}"
                       placeholder="ХАРАКТЕРИСТИКИ КВАРТИРЫ">
            </div>
            
            <div class="mob-editor__block">
                <label class="mob-editor__label">Изображение</label>
                <div class="mob-editor__images">
                    ${slide.image ? `
                        <div class="mob-editor__image">
                            <img src="${slide.image}" alt="Характеристики">
                            <div class="mob-editor__image-remove" onclick="removeMobileImage(${index}, 'image')">×</div>
                        </div>
                    ` : `
                        <div class="mob-editor__image add-image" onclick="uploadMobileImage(${index}, 'char-image')">
                        </div>
                    `}
                </div>
            </div>
            
            <div class="mob-editor__block">
                <label class="mob-editor__label">Характеристики (макс. 12)</label>
                ${items.map((item, i) => `
                    <div class="mob-editor__param">
                        <input type="text" 
                               placeholder="Название"
                               data-slide="${index}"
                               data-item="${i}"
                               data-field="label"
                               value="${item.label || ''}">
                        <input type="text" 
                               placeholder="Значение"
                               data-slide="${index}"
                               data-item="${i}"
                               data-field="value"
                               value="${item.value || ''}">
                        <button class="mob-editor__param-remove" onclick="removeCharacteristic(${index}, ${i})">×</button>
                    </div>
                `).join('')}
                
                ${items.length < 12 ? `
                    <button class="mob-editor__add-param" onclick="addCharacteristic(${index})">
                        + Добавить характеристику
                    </button>
                ` : '<div class="limit-warning">Достигнут лимит 12 характеристик</div>'}
            </div>
        </div>
    `;
}

// Генерация слайда "Галерея" для мобильной версии
function generateMobileGallerySlide(slide, index) {
    const images = slide.images || [];
    
    return `
        <div class="swiper-slide mob-editor__slide">
            <div class="mob-editor__block">
                <label class="mob-editor__label">Галерея (3 изображения)</label>
                <div class="mob-editor__images">
                    ${[0, 1, 2].map(i => {
                        if (images[i]) {
                            return `
                                <div class="mob-editor__image">
                                    <img src="${images[i].url || images[i]}" alt="Изображение ${i + 1}">
                                    <div class="mob-editor__image-remove" onclick="removeGalleryImage(${index}, ${i})">×</div>
                                </div>
                            `;
                        } else {
                            return `
                                <div class="mob-editor__image add-image" onclick="uploadGalleryImage(${index}, ${i})">
                                </div>
                            `;
                        }
                    }).join('')}
                </div>
            </div>
        </div>
    `;
}

// Генерация слайда "Сетка" для мобильной версии
function generateMobileGridSlide(slide, index) {
    const images = slide.images || [];
    
    return `
        <div class="swiper-slide mob-editor__slide">
            <div class="mob-editor__block">
                <label class="mob-editor__label">Сетка (4 изображения)</label>
                <div class="mob-editor__images">
                    ${[0, 1, 2, 3].map(i => {
                        if (images[i]) {
                            return `
                                <div class="mob-editor__image">
                                    <img src="${images[i].url || images[i]}" alt="Изображение ${i + 1}">
                                    <div class="mob-editor__image-remove" onclick="removeGridImage(${index}, ${i})">×</div>
                                </div>
                            `;
                        } else {
                            return `
                                <div class="mob-editor__image add-image" onclick="uploadGridImage(${index}, ${i})">
                                </div>
                            `;
                        }
                    }).join('')}
                </div>
            </div>
        </div>
    `;
}

// Генерация слайда "Описание" для мобильной версии
function generateMobileDescriptionSlide(slide, index) {
    const images = slide.images || [];
    const content = (slide.content || '').replace(/<br\s*\/?>/gi, '\n');
    
    return `
        <div class="swiper-slide mob-editor__slide">
            <div class="mob-editor__block">
                <label class="mob-editor__label">Заголовок</label>
                <input type="text" 
                       class="mob-editor__input"
                       data-slide="${index}"
                       data-field="title"
                       value="${slide.title || 'ОПИСАНИЕ'}"
                       placeholder="ОПИСАНИЕ">
            </div>
            
            <div class="mob-editor__block">
                <label class="mob-editor__label">Текст описания</label>
                <textarea class="mob-editor__textarea" 
                          data-slide="${index}" 
                          data-field="content"
                          placeholder="Подробно опишите о своем объекте...">${content}</textarea>
            </div>
            
            <div class="mob-editor__block">
                <label class="mob-editor__label">Изображения</label>
                <div class="mob-editor__images">
                    ${[0, 1].map(i => {
                        if (images[i]) {
                            return `
                                <div class="mob-editor__image">
                                    <img src="${images[i].url || images[i]}" alt="Изображение ${i + 1}">
                                    <div class="mob-editor__image-remove" onclick="removeDescriptionImage(${index}, ${i})">×</div>
                                </div>
                            `;
                        } else {
                            return `
                                <div class="mob-editor__image add-image" onclick="uploadDescriptionImage(${index}, ${i})">
                                </div>
                            `;
                        }
                    }).join('')}
                </div>
            </div>
        </div>
    `;
}

// Генерация слайда "Инфраструктура" для мобильной версии
function generateMobileInfrastructureSlide(slide, index) {
    const images = slide.images || [];
    const content = (slide.content || '').replace(/<br\s*\/?>/gi, '\n');
    
    return `
        <div class="swiper-slide mob-editor__slide">
            <div class="mob-editor__block">
                <label class="mob-editor__label">Заголовок</label>
                <input type="text" 
                       class="mob-editor__input"
                       data-slide="${index}"
                       data-field="title"
                       value="${slide.title || 'ИНФРАСТРУКТУРА'}"
                       placeholder="ИНФРАСТРУКТУРА">
            </div>
            
            <div class="mob-editor__block">
                <label class="mob-editor__label">Текст инфраструктуры</label>
                <textarea class="mob-editor__textarea" 
                          data-slide="${index}" 
                          data-field="content"
                          placeholder="Подробно опишите, что находится вблизи...">${content}</textarea>
            </div>
            
            <div class="mob-editor__block">
                <label class="mob-editor__label">Изображения</label>
                <div class="mob-editor__images">
                    ${[0, 1].map(i => {
                        if (images[i]) {
                            return `
                                <div class="mob-editor__image">
                                    <img src="${images[i].url || images[i]}" alt="Изображение ${i + 1}">
                                    <div class="mob-editor__image-remove" onclick="removeInfrastructureImage(${index}, ${i})">×</div>
                                </div>
                            `;
                        } else {
                            return `
                                <div class="mob-editor__image add-image" onclick="uploadInfrastructureImage(${index}, ${i})">
                                </div>
                            `;
                        }
                    }).join('')}
                </div>
            </div>
        </div>
    `;
}

// Генерация слайда "Особенности" для мобильной версии
function generateMobileFeaturesSlide(slide, index) {
    const items = slide.items || [];
    const images = slide.images || [];
    
    return `
        <div class="swiper-slide mob-editor__slide">
            <div class="mob-editor__block">
                <label class="mob-editor__label">Заголовок</label>
                <input type="text" 
                       class="mob-editor__input"
                       data-slide="${index}"
                       data-field="title"
                       value="${slide.title || 'ОСОБЕННОСТИ'}"
                       placeholder="ОСОБЕННОСТИ">
            </div>
            
            <div class="mob-editor__block">
                <label class="mob-editor__label">Изображения</label>
                <div class="mob-editor__images">
                    ${[0, 1].map(i => {
                        if (images[i]) {
                            return `
                                <div class="mob-editor__image">
                                    <img src="${images[i].url || images[i]}" alt="Особенность ${i + 1}">
                                    <div class="mob-editor__image-remove" onclick="removeFeatureImage(${index}, ${i})">×</div>
                                </div>
                            `;
                        } else {
                            return `
                                <div class="mob-editor__image add-image" onclick="uploadFeatureImage(${index}, ${i})">
                                </div>
                            `;
                        }
                    }).join('')}
                </div>
            </div>
            
            <div class="mob-editor__block">
                <label class="mob-editor__label">Особенности (макс. 9)</label>
                ${items.map((item, i) => `
                    <div class="mob-editor__param">
                        <input type="text" 
                               placeholder="Особенность"
                               data-slide="${index}"
                               data-item="${i}"
                               data-field="text"
                               value="${item.text || ''}">
                        <button class="mob-editor__param-remove" onclick="removeFeature(${index}, ${i})">×</button>
                    </div>
                `).join('')}
                
                ${items.length < 9 ? `
                    <button class="mob-editor__add-param" onclick="addFeature(${index})">
                        + Добавить особенность
                    </button>
                ` : '<div class="limit-warning">Достигнут лимит 9 особенностей</div>'}
            </div>
        </div>
    `;
}

// Генерация слайда "Местоположение" для мобильной версии
function generateMobileLocationSlide(slide, index) {
    const address = (slide.location_address || '').replace(/<br\s*\/?>/gi, '\n');
    
    return `
        <div class="swiper-slide mob-editor__slide">
            <div class="mob-editor__block">
                <label class="mob-editor__label">Заголовок</label>
                <input type="text" 
                       class="mob-editor__input"
                       data-slide="${index}"
                       data-field="title"
                       value="${slide.title || 'МЕСТОПОЛОЖЕНИЕ'}"
                       placeholder="МЕСТОПОЛОЖЕНИЕ">
            </div>
            
            <div class="mob-editor__block">
                <label class="mob-editor__label">Название объекта</label>
                <input type="text" 
                       class="mob-editor__input"
                       data-slide="${index}"
                       data-field="location_name"
                       value="${slide.location_name || 'ЖК "Успешная продажа"'}"
                       placeholder="Название объекта">
            </div>
            
            <div class="mob-editor__block">
                <label class="mob-editor__label">Адрес</label>
                <textarea class="mob-editor__textarea small" 
                          data-slide="${index}" 
                          data-field="location_address"
                          placeholder="Адрес объекта">${address}</textarea>
            </div>
        </div>
    `;
}

// Генерация слайда "Контакты" для мобильной версии
function generateMobileContactsSlide(slide, index) {
    const images = slide.images || [];
    
    return `
        <div class="swiper-slide mob-editor__slide">
            <div class="mob-editor__block">
                <label class="mob-editor__label">Заголовок</label>
                <input type="text" 
                       class="mob-editor__input"
                       data-slide="${index}"
                       data-field="contact_title"
                       value="${slide.contact_title || 'Контакты'}"
                       placeholder="Контакты">
            </div>
            
            <div class="mob-editor__block">
                <label class="mob-editor__label">Аватар</label>
                <div class="mob-editor__avatar-wrapper">
                    ${slide.avatar ? `
                        <div class="mob-editor__avatar has-image" onclick="uploadMobileImage(${index}, 'avatar')">
                            <img src="${slide.avatar}" alt="Аватар">
                            <div class="mob-editor__image-remove" onclick="event.stopPropagation(); removeMobileImage(${index}, 'avatar')">×</div>
                        </div>
                    ` : `
                        <div class="mob-editor__avatar" onclick="uploadMobileImage(${index}, 'avatar')">
                        </div>
                    `}
                </div>
            </div>
            
            <div class="mob-editor__block">
                <label class="mob-editor__label">Имя</label>
                <input type="text" 
                       class="mob-editor__input"
                       data-slide="${index}"
                       data-field="contact_name"
                       value="${slide.contact_name || 'Slide Estate'}"
                       placeholder="Имя">
            </div>
            
            <div class="mob-editor__block">
                <label class="mob-editor__label">Должность/Роль</label>
                <input type="text" 
                       class="mob-editor__input"
                       data-slide="${index}"
                       data-field="contact_role"
                       value="${slide.contact_role || 'Онлайн-сервис для риелторов'}"
                       placeholder="Должность">
            </div>
            
            <div class="mob-editor__block">
                <label class="mob-editor__label">Телефон</label>
                <input type="text" 
                       class="mob-editor__input"
                       data-slide="${index}"
                       data-field="contact_phone"
                       value="${slide.contact_phone || '+7 (900) 000-00-00'}"
                       placeholder="Телефон">
            </div>
            
            <div class="mob-editor__block">
                <label class="mob-editor__label">Мессенджеры</label>
                <input type="text" 
                       class="mob-editor__input"
                       data-slide="${index}"
                       data-field="contact_messengers"
                       value="${slide.contact_messengers || 'Telegram | WhatsApp'}"
                       placeholder="Мессенджеры">
            </div>
            
            <div class="mob-editor__block">
                <label class="mob-editor__label">Изображения</label>
                <div class="mob-editor__images">
                    ${[0, 1].map(i => {
                        if (images[i]) {
                            return `
                                <div class="mob-editor__image">
                                    <img src="${images[i].url || images[i]}" alt="Изображение ${i + 1}">
                                    <div class="mob-editor__image-remove" onclick="removeContactImage(${index}, ${i})">×</div>
                                </div>
                            `;
                        } else {
                            return `
                                <div class="mob-editor__image add-image" onclick="uploadContactImage(${index}, ${i})">
                                </div>
                            `;
                        }
                    }).join('')}
                </div>
            </div>
        </div>
    `;
}

// Настройка событий для мобильного редактора
function setupMobileSlideEvents() {
    // Обработка ввода в текстовые поля
    document.querySelectorAll('.mob-editor__textarea, .mob-editor__input').forEach(element => {
        element.addEventListener('input', function() {
            const slideIndex = parseInt(this.dataset.slide);
            const field = this.dataset.field;
            
            if (this.dataset.item !== undefined) {
                const itemIndex = parseInt(this.dataset.item);
                if (!slides[slideIndex].items) slides[slideIndex].items = [];
                if (!slides[slideIndex].items[itemIndex]) slides[slideIndex].items[itemIndex] = {};
                slides[slideIndex].items[itemIndex][field] = this.value;
            } else {
                // Для textarea с заголовками заменяем \n на <br>
                if (this.classList.contains('mob-editor__textarea') && (field === 'title' || field === 'subtitle')) {
                    slides[slideIndex][field] = this.value.replace(/\n/g, '<br>');
                } else {
                    slides[slideIndex][field] = this.value;
                }
            }
            
            hasUnsavedChanges = true;
            triggerAutoSave();
        });
    });
    
    // Обновляем конвертер валют для слайдов обложки
    slides.forEach((slide, index) => {
        if (slide.type === 'cover' && typeof updateCurrencyConversions === 'function') {
            updateCurrencyConversions(index);
        }
    });
}

// Удаление изображения в мобильной версии
function removeMobileImage(slideIndex, field) {
    if (confirm('Удалить изображение?')) {
        delete slides[slideIndex][field];
        renderMobileSlides();
        mobEditorSwiper.update();
        mobEditorSwiper.slideTo(slideIndex);
        hasUnsavedChanges = true;
        showMobileNotification('Изображение удалено');
        triggerAutoSave();
    }
}

// Удаление изображения из галереи
function removeGalleryImage(slideIndex, position) {
    if (confirm('Удалить изображение из галереи?')) {
        if (slides[slideIndex].images && slides[slideIndex].images[position]) {
            slides[slideIndex].images.splice(position, 1);
            renderMobileSlides();
            mobEditorSwiper.update();
            mobEditorSwiper.slideTo(slideIndex);
            hasUnsavedChanges = true;
            showMobileNotification('Изображение удалено');
            triggerAutoSave();
        }
    }
}

// Удаление изображения из сетки
function removeGridImage(slideIndex, position) {
    if (confirm('Удалить изображение из сетки?')) {
        if (slides[slideIndex].images && slides[slideIndex].images[position]) {
            slides[slideIndex].images.splice(position, 1);
            renderMobileSlides();
            mobEditorSwiper.update();
            mobEditorSwiper.slideTo(slideIndex);
            hasUnsavedChanges = true;
            showMobileNotification('Изображение удалено');
            triggerAutoSave();
        }
    }
}

// Удаление изображения описания
function removeDescriptionImage(slideIndex, position) {
    if (confirm('Удалить изображение?')) {
        if (slides[slideIndex].images && slides[slideIndex].images[position]) {
            slides[slideIndex].images.splice(position, 1);
            renderMobileSlides();
            mobEditorSwiper.update();
            mobEditorSwiper.slideTo(slideIndex);
            hasUnsavedChanges = true;
            showMobileNotification('Изображение удалено');
            triggerAutoSave();
        }
    }
}

// Удаление изображения инфраструктуры
function removeInfrastructureImage(slideIndex, position) {
    if (confirm('Удалить изображение?')) {
        if (slides[slideIndex].images && slides[slideIndex].images[position]) {
            slides[slideIndex].images.splice(position, 1);
            renderMobileSlides();
            mobEditorSwiper.update();
            mobEditorSwiper.slideTo(slideIndex);
            hasUnsavedChanges = true;
            showMobileNotification('Изображение удалено');
            triggerAutoSave();
        }
    }
}

// Удаление изображения особенности
function removeFeatureImage(slideIndex, position) {
    if (confirm('Удалить изображение?')) {
        if (slides[slideIndex].images && slides[slideIndex].images[position]) {
            slides[slideIndex].images.splice(position, 1);
            renderMobileSlides();
            mobEditorSwiper.update();
            mobEditorSwiper.slideTo(slideIndex);
            hasUnsavedChanges = true;
            showMobileNotification('Изображение удалено');
            triggerAutoSave();
        }
    }
}

// Удаление изображения контактов
function removeContactImage(slideIndex, position) {
    if (confirm('Удалить изображение?')) {
        if (slides[slideIndex].images && slides[slideIndex].images[position]) {
            slides[slideIndex].images.splice(position, 1);
            renderMobileSlides();
            mobEditorSwiper.update();
            mobEditorSwiper.slideTo(slideIndex);
            hasUnsavedChanges = true;
            showMobileNotification('Изображение удалено');
            triggerAutoSave();
        }
    }
}

// Загрузка изображения в мобильной версии
async function uploadMobileImage(slideIndex, type) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.dataset.slide = slideIndex;
    input.dataset.uploadType = type;
    
    input.onchange = async (e) => {
        if (e.target.files && e.target.files[0]) {
            const result = await handleFileUpload(e.target.files[0], input);
            if (result && result.success) {
                renderMobileSlides();
                mobEditorSwiper.update();
                mobEditorSwiper.slideTo(slideIndex);
            }
        }
    };
    
    input.click();
}

// Загрузка изображения в галерею
async function uploadGalleryImage(slideIndex, position) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.dataset.slide = slideIndex;
    input.dataset.uploadType = 'gallery';
    input.dataset.position = position;
    
    input.onchange = async (e) => {
        if (e.target.files && e.target.files[0]) {
            const result = await handleFileUpload(e.target.files[0], input);
            if (result && result.success) {
                renderMobileSlides();
                mobEditorSwiper.update();
                mobEditorSwiper.slideTo(slideIndex);
            }
        }
    };
    
    input.click();
}

// Загрузка изображения в сетку
async function uploadGridImage(slideIndex, position) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.dataset.slide = slideIndex;
    input.dataset.uploadType = 'grid';
    input.dataset.position = position;
    
    input.onchange = async (e) => {
        if (e.target.files && e.target.files[0]) {
            const result = await handleFileUpload(e.target.files[0], input);
            if (result && result.success) {
                renderMobileSlides();
                mobEditorSwiper.update();
                mobEditorSwiper.slideTo(slideIndex);
            }
        }
    };
    
    input.click();
}

// Загрузка изображения для описания
async function uploadDescriptionImage(slideIndex, position) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.dataset.slide = slideIndex;
    input.dataset.uploadType = 'gallery';
    input.dataset.position = position;
    
    input.onchange = async (e) => {
        if (e.target.files && e.target.files[0]) {
            const result = await handleFileUpload(e.target.files[0], input);
            if (result && result.success) {
                renderMobileSlides();
                mobEditorSwiper.update();
                mobEditorSwiper.slideTo(slideIndex);
            }
        }
    };
    
    input.click();
}

// Загрузка изображения для инфраструктуры
async function uploadInfrastructureImage(slideIndex, position) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.dataset.slide = slideIndex;
    input.dataset.uploadType = 'gallery';
    input.dataset.position = position;
    
    input.onchange = async (e) => {
        if (e.target.files && e.target.files[0]) {
            const result = await handleFileUpload(e.target.files[0], input);
            if (result && result.success) {
                renderMobileSlides();
                mobEditorSwiper.update();
                mobEditorSwiper.slideTo(slideIndex);
            }
        }
    };
    
    input.click();
}

// Загрузка изображения для особенности
async function uploadFeatureImage(slideIndex, position) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.dataset.slide = slideIndex;
    input.dataset.uploadType = 'gallery';
    input.dataset.position = position;
    
    input.onchange = async (e) => {
        if (e.target.files && e.target.files[0]) {
            const result = await handleFileUpload(e.target.files[0], input);
            if (result && result.success) {
                renderMobileSlides();
                mobEditorSwiper.update();
                mobEditorSwiper.slideTo(slideIndex);
            }
        }
    };
    
    input.click();
}

// Загрузка изображения для контактов
async function uploadContactImage(slideIndex, position) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.dataset.slide = slideIndex;
    input.dataset.uploadType = 'gallery';
    input.dataset.position = position;
    
    input.onchange = async (e) => {
        if (e.target.files && e.target.files[0]) {
            const result = await handleFileUpload(e.target.files[0], input);
            if (result && result.success) {
                renderMobileSlides();
                mobEditorSwiper.update();
                mobEditorSwiper.slideTo(slideIndex);
            }
        }
    };
    
    input.click();
}

// Добавление характеристики
function addCharacteristic(slideIndex) {
    if (!slides[slideIndex].items) slides[slideIndex].items = [];
    
    if (slides[slideIndex].items.length >= 12) {
        showMobileNotification('Максимальное количество характеристик - 12', 'error');
        return;
    }
    
    slides[slideIndex].items.push({ label: 'Название', value: 'Значение' });
    renderMobileSlides();
    mobEditorSwiper.update();
    mobEditorSwiper.slideTo(slideIndex);
    hasUnsavedChanges = true;
    showMobileNotification('Характеристика добавлена');
    triggerAutoSave();
}

// Удаление характеристики
function removeCharacteristic(slideIndex, itemIndex) {
    if (confirm('Удалить эту характеристику?')) {
        slides[slideIndex].items.splice(itemIndex, 1);
        renderMobileSlides();
        mobEditorSwiper.update();
        mobEditorSwiper.slideTo(slideIndex);
        hasUnsavedChanges = true;
        showMobileNotification('Характеристика удалена');
        triggerAutoSave();
    }
}

// Добавление особенности
function addFeature(slideIndex) {
    if (!slides[slideIndex].items) slides[slideIndex].items = [];
    
    if (slides[slideIndex].items.length >= 9) {
        showMobileNotification('Максимальное количество особенностей - 9', 'error');
        return;
    }
    
    slides[slideIndex].items.push({ text: 'НОВАЯ ОСОБЕННОСТЬ' });
    renderMobileSlides();
    mobEditorSwiper.update();
    mobEditorSwiper.slideTo(slideIndex);
    hasUnsavedChanges = true;
    showMobileNotification('Особенность добавлена');
    triggerAutoSave();
}

// Удаление особенности
function removeFeature(slideIndex, itemIndex) {
    if (confirm('Удалить эту особенность?')) {
        slides[slideIndex].items.splice(itemIndex, 1);
        renderMobileSlides();
        mobEditorSwiper.update();
        mobEditorSwiper.slideTo(slideIndex);
        hasUnsavedChanges = true;
        showMobileNotification('Особенность удалена');
        triggerAutoSave();
    }
}

// Обновление цены в мобильной версии
async function updateMobilePrice(slideIndex, formattedValue) {
    const rawValue = parseFormattedNumber(formattedValue);
    slides[slideIndex].price_value = rawValue;
    
    // Обновляем конвертер валют
    if (typeof updateCurrencyConversions === 'function') {
        await updateCurrencyConversions(slideIndex);
    }
    
    hasUnsavedChanges = true;
    triggerAutoSave();
}

// Показ модального окна выбора
function showChoiceModal(slideIndex, type) {
    const modal = document.getElementById('choiceModal');
    const list = document.getElementById('choiceList');
    
    let items = [];
    if (type === 'deal_type') {
        items = ['Аренда', 'Продажа'];
    } else if (type === 'currency') {
        items = [
            { value: 'RUB', label: '₽ Рубль' },
            { value: 'USD', label: '$ Доллар' },
            { value: 'EUR', label: '€ Евро' },
            { value: 'CNY', label: '¥ Юань' },
            { value: 'KZT', label: '₸ Тенге' }
        ];
    }
    
    list.innerHTML = items.map(item => {
        const value = typeof item === 'object' ? item.value : item;
        const label = typeof item === 'object' ? item.label : item;
        const isActive = slides[slideIndex][type] === value;
        
        return `<div class="mob-editor__list-item ${isActive ? 'active' : ''}" 
                     onclick="selectChoice(${slideIndex}, '${type}', '${value}')">
                    ${label}
                </div>`;
    }).join('');
    
    modal.classList.add('open');
    
    // Закрытие по клику вне списка
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.remove('open');
        }
    });
}

// Выбор в модальном окне
async function selectChoice(slideIndex, type, value) {
    slides[slideIndex][type] = value;
    
    const modal = document.getElementById('choiceModal');
    modal.classList.remove('open');
    
    // Обновляем конвертер валют при смене валюты или типа сделки
    if ((type === 'currency' || type === 'deal_type') && typeof updateCurrencyConversions === 'function') {
        await updateCurrencyConversions(slideIndex);
    }
    
    renderMobileSlides();
    mobEditorSwiper.update();
    mobEditorSwiper.slideTo(slideIndex);
    hasUnsavedChanges = true;
    triggerAutoSave();
}

// Обновление навигации
function updateNavigation() {
    if (mobNavSwiper) {
        const navSlides = document.querySelectorAll('.mob-editor-nav__slide');
        navSlides.forEach((slide, index) => {
            slide.classList.toggle('swiper-slide-thumb-active', index === currentSlideIndex);
        });
        mobNavSwiper.slideTo(currentSlideIndex);
    }
}

// Обновление кнопок навигации
function updateButtons() {
    const prevBtn = document.querySelector('.mob-editor-buttons__prev');
    const nextBtn = document.querySelector('.mob-editor-buttons__next');
    
    if (prevBtn) {
        prevBtn.style.display = currentSlideIndex === 0 ? 'none' : 'block';
    }
    
    if (nextBtn) {
        nextBtn.style.display = currentSlideIndex === slides.length - 1 ? 'none' : 'block';
    }
}

// Переключение на слайд
function switchToSlide(index) {
    if (mobEditorSwiper && index >= 0 && index < slides.length) {
        mobEditorSwiper.slideTo(index);
        currentSlideIndex = index;
        updateNavigation();
        updateButtons();
    }
}

// Следующий слайд
function nextSlide() {
    if (currentSlideIndex < slides.length - 1) {
        switchToSlide(currentSlideIndex + 1);
    }
}

// Предыдущий слайд
function prevSlide() {
    if (currentSlideIndex > 0) {
        switchToSlide(currentSlideIndex - 1);
    }
}

// Очистка текущего слайда
function clearCurrentSlide() {
    if (confirm('Очистить все поля текущего слайда?')) {
        const slide = slides[currentSlideIndex];
        const type = slide.type;
        
        // Сохраняем только тип слайда
        slides[currentSlideIndex] = { type: type, hidden: false };
        
        renderMobileSlides();
        mobEditorSwiper.update();
        mobEditorSwiper.slideTo(currentSlideIndex);
        hasUnsavedChanges = true;
        showMobileNotification('Поля очищены');
        triggerAutoSave();
    }
}

function addMobileSlide() {
    // можно открыть модальное, как в десктопе, или добавить по умолчанию, напр. image
    addSlide(); // если есть глобальная функция
    renderMobileSlides();
    mobEditorSwiper.update();
    switchToSlide(slides.length - 1);
}

function removeMobileSlide() {
    if (!confirm('Удалить текущий слайд?')) return;
    deleteSlide(currentSlideIndex); // твоя десктопная функция
    renderMobileSlides();
    mobEditorSwiper.update();
    if (currentSlideIndex >= slides.length) currentSlideIndex = slides.length - 1;
    switchToSlide(currentSlideIndex);
}

function moveMobileSlideUp() {
    if (currentSlideIndex <= 0) return;
    moveSlide(currentSlideIndex, currentSlideIndex - 1); // твоя функция
    renderMobileSlides();
    mobEditorSwiper.update();
    switchToSlide(currentSlideIndex - 1);
}

function moveMobileSlideDown() {
    if (currentSlideIndex >= slides.length - 1) return;
    moveSlide(currentSlideIndex, currentSlideIndex + 1);
    renderMobileSlides();
    mobEditorSwiper.update();
    switchToSlide(currentSlideIndex + 1);
}


// Сохранение в мобильной версии
async function saveMobilePresentation() {
    try {
        await savePresentation(false, 'draft');
        showMobileNotification('Сохранено успешно!');
    } catch (error) {
        showMobileNotification('Ошибка сохранения', 'error');
    }
}

// Предпросмотр в мобильной версии
function previewMobilePresentation() {
    saveMobilePresentation().then(() => {
        const url = `/api.php?action=generate_presentation&id=${presentationId}`;
        window.open(url, '_blank');
    });
}

// Экспорт в PDF в мобильной версии
function exportMobileToPDF() {
    saveMobilePresentation().then(() => {
        const url = `/api.php?action=export_pdf&id=${presentationId}`;
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.open(data.print_url, '_blank');
                } else {
                    showMobileNotification('Ошибка экспорта: ' + (data.error || 'Неизвестная ошибка'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMobileNotification('Ошибка соединения с сервером', 'error');
            });
    });
}

// Скачивание презентации
function downloadMobilePresentation() {
    saveMobilePresentation().then(() => {
        const url = `/api.php?action=download_presentation&id=${presentationId}`;
        window.open(url, '_blank');
    });
}

// Поделиться презентацией
function shareMobilePresentation() {
    saveMobilePresentation().then(() => {
        if (navigator.share) {
            navigator.share({
                title: document.getElementById('presentationTitle')?.value || 'Презентация',
                text: 'Посмотрите мою презентацию',
                url: window.location.href
            })
            .catch(error => console.log('Ошибка шеринга:', error));
        } else {
            showMobileNotification('Функция шеринга недоступна в вашем браузере', 'error');
        }
    });
}

// Показ уведомления в мобильной версии
function showMobileNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `mobile-notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Настройка обработчиков событий
function setupMobileEventListeners() {
    // Автосохранение при изменении полей
    document.addEventListener('input', function(e) {
        if (e.target.closest('.mob-editor__slide')) {
            hasUnsavedChanges = true;
            triggerAutoSave();
        }
    });
    
    // Предотвращение закрытия страницы с несохраненными изменениями
    window.addEventListener('beforeunload', (e) => {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = 'У вас есть несохраненные изменения. Вы уверены, что хотите уйти?';
        }
    });
}

// Инициализация при загрузке DOM
document.addEventListener('DOMContentLoaded', function() {
    if (window.innerWidth <= 767) {
        initMobileEditor();
    }
});