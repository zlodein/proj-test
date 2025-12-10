// Проверка, что все функции существуют
console.log('generateCoverSlide exists:', typeof generateCoverSlide === 'function');
console.log('generateDescriptionSlide exists:', typeof generateDescriptionSlide === 'function');
console.log('generateInfrastructureSlide exists:', typeof generateInfrastructureSlide === 'function');
console.log('generateFeaturesSlide exists:', typeof generateFeaturesSlide === 'function');
console.log('generateLocationSlide exists:', typeof generateLocationSlide === 'function');
console.log('generateContactsSlide exists:', typeof generateContactsSlide === 'function');

const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const presentationId = parseInt(document.querySelector('meta[name="presentation-id"]').content);
let slides = JSON.parse(document.querySelector('#slides-data').textContent);
let swiper = null;
let autoSaveTimer = null;
let themeColor = '#2c7f8d';
let hasUnsavedChanges = false;

const AUTOSAVE_DELAY = 3000;

document.addEventListener('DOMContentLoaded', () => {
    initThemePicker();
    initSwiper();
    initAutoSave();
    preventDataLoss();
});

function initThemePicker() {
    const themeData = document.querySelector('#theme-data');
    if (themeData) {
        themeColor = themeData.textContent || '#2c7f8d';
        applyTheme(themeColor);
    }
    
    document.querySelectorAll('.theme-color').forEach(el => {
        el.addEventListener('click', function() {
            const color = this.dataset.color;
            selectTheme(color);
        });
    });
    
    const customPicker = document.getElementById('customColorPicker');
    if (customPicker) {
        customPicker.addEventListener('change', function() {
            selectTheme(this.value);
        });
    }
}

function selectTheme(color) {
    themeColor = color;
    applyTheme(color);
    document.querySelectorAll('.theme-color').forEach(el => {
        el.classList.toggle('active', el.dataset.color === color);
    });
    hasUnsavedChanges = true;
    triggerAutoSave();
}

function applyTheme(color) {
    document.documentElement.style.setProperty('--theme-main-color', color);
}

function initSwiper() {
    renderSlides();
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
                    savePresentation(true);
                }
            }
        }
    });
}

function renderSlides() {
    const wrapper = document.getElementById('swiperWrapper');
    wrapper.innerHTML = '';
    slides.forEach((slide, index) => {
        wrapper.insertAdjacentHTML('beforeend', generateSlideHTML(slide, index));
    });
    setTimeout(() => attachSlideEvents(), 100);
}

function generateSlideHTML(slide, index) {
    const slideTypes = {
        'cover': generateCoverSlide,
        'image': generateImageSlide,
        'gallery': generateGallerySlide,
        'characteristics': generateCharacteristicsSlide,
        'grid': generateGridSlide,
        'description': generateDescriptionSlide,
        'infrastructure': generateInfrastructureSlide,
        'features': generateFeaturesSlide,
        'location': generateLocationSlide,
        'contacts': generateContactsSlide
    };
    const generator = slideTypes[slide.type] || generateCoverSlide;
    const content = generator(slide, index);
    return `
        <div class="swiper-slide editor__slide" data-slide-index="${index}">
            <div class="editor__inner">
                <div class="editor__inner-toolbar">
                    <div class="editor__control-list">
                        <div class="editor__control-item">
                            <button type="button" class="editor-btn" onclick="moveSlide(${index}, 1)" ${index >= slides.length - 1 ? 'disabled' : ''}>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                        <div class="editor__control-item">
                            <button type="button" class="editor-btn" onclick="moveSlide(${index}, -1)" ${index === 0 ? 'disabled' : ''}>
                                <i class="fas fa-chevron-up"></i>
                            </button>
                        </div>
                        <div class="editor__control-item">
                            <button type="button" class="editor-btn editor-btn-hide ${slide.hidden ? '' : 'active'}" onclick="toggleSlideVisibility(${index})"></button>
                        </div>
                        <div class="editor__control-item">
                            <button type="button" class="editor-btn" onclick="duplicateSlide(${index})">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <div class="editor__control-item">
                            <button type="button" class="editor-btn" onclick="deleteSlide(${index})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="editor__inner-content">${content}</div>
            </div>
        </div>
    `;
}

function generateCoverSlide(slide, index) {
    const bgImage = slide.background_image || '';
    return `
        <div class="booklet-page page-cover">
            <div class="booklet-page__inner">
                <div class="booklet-content booklet-main">
                    <div class="booklet-main__wrap">
                        <div class="booklet-main__img">
                            <input type="file" accept="image/*" class="hide-input" id="bg-upload-${index}" data-slide="${index}" data-upload-type="background">
                            <button class="btn-edit-bg btn-edit-bg--center" onclick="document.getElementById('bg-upload-${index}').click()"></button>
                            ${bgImage ? `<img src="${bgImage}" alt="">` : ''}
                        </div>
                        <div class="booklet-main__content">
                            <div class="booklet-main__top">
                                <div contenteditable="true" class="editor-field editor-textarea" data-field="title" data-slide="${index}">${slide.title || 'ЭКСКЛЮЗИВНОЕ<br>ПРЕДЛОЖЕНИЕ'}</div>
                            </div>
                            <div class="booklet-main__center">
                                <div contenteditable="true" class="editor-field editor-textarea" data-field="subtitle" data-slide="${index}">${slide.subtitle || 'АБСОЛЮТНО НОВЫЙ ТАУНХАУС<br>НА ПЕРВОЙ ЛИНИИ'}</div>
                            </div>
                            <div class="booklet-main__bottom">
                                <div contenteditable="true" class="booklet-main__type editor-field" data-field="deal_type" data-slide="${index}">${slide.deal_type || 'Аренда'}</div>
                                <div contenteditable="true" class="booklet-main__price editor-field" data-field="price" data-slide="${index}">${slide.price || '11 000 000 ₽'}</div>
                            </div>
                            <div class="all-currencies" style="display:none;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function generateDescriptionSlide(slide, index) {
    const images = slide.images || [];
    const img1 = images[0] ? (images[0].url || images[0]) : '';
    const img2 = images[1] ? (images[1].url || images[1]) : '';
    
    return `
        <div class="booklet-page page-cover">
            <div class="booklet-page__inner">
                <div class="booklet-content booklet-info">
                    <div class="booklet-info__top-square"></div>
                    <div class="booklet-info__bottom-square"></div>
                    <div class="booklet-info__wrap">
                        <div class="booklet-info__block booklet-info__content">
                            <div contenteditable="true" class="booklet-info__title editor-field" data-field="title" data-slide="${index}">${slide.title || 'ОПИСАНИЕ'}</div>
                            <div class="booklet-info__text">
                                <span contenteditable="true" class="editor-field editor-textarea" data-field="content" data-slide="${index}">${slide.content || 'Подробно опишите о своем объекте - транспортная доступность, местоположение, подробная планировка, особенности.'}</span>
                            </div>
                        </div>
                        <div class="booklet-info__block booklet-info__img">
                            <input type="file" accept="image/*" class="hide-input" id="desc-img1-${index}" data-slide="${index}" data-position="0" data-upload-type="gallery">
                            <button class="btn-edit-bg btn-edit-bg--center" onclick="document.getElementById('desc-img1-${index}').click()"></button>
                            ${img1 ? `<img src="${img1}" alt="">` : ''}
                        </div>
                        <div class="booklet-info__block booklet-info__img">
                            <input type="file" accept="image/*" class="hide-input" id="desc-img2-${index}" data-slide="${index}" data-position="1" data-upload-type="gallery">
                            <button class="btn-edit-bg btn-edit-bg--center" onclick="document.getElementById('desc-img2-${index}').click()"></button>
                            ${img2 ? `<img src="${img2}" alt="">` : ''}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function generateInfrastructureSlide(slide, index) {
    const images = slide.images || [];
    const img1 = images[0] ? (images[0].url || images[0]) : '';
    const img2 = images[1] ? (images[1].url || images[1]) : '';
    
    return `
        <div class="booklet-page page-cover">
            <div class="booklet-page__inner">
                <div class="booklet-content booklet-stroen">
                    <div class="booklet-stroen__top-square"></div>
                    <div class="booklet-stroen__bottom-square"></div>
                    <div class="booklet-stroen__wrap">
                        <div class="booklet-stroen__block booklet-stroen__img">
                            <input type="file" accept="image/*" class="hide-input" id="infra-img1-${index}" data-slide="${index}" data-position="0" data-upload-type="gallery">
                            <button class="btn-edit-bg btn-edit-bg--center" onclick="document.getElementById('infra-img1-${index}').click()"></button>
                            ${img1 ? `<img src="${img1}" alt="">` : ''}
                        </div>
                        <div class="booklet-stroen__block booklet-stroen__content">
                            <div contenteditable="true" class="booklet-stroen__title editor-field" data-field="title" data-slide="${index}">${slide.title || 'ИНФРАСТРУКТУРА'}</div>
                            <div class="booklet-stroen__text">
                                <span contenteditable="true" class="editor-field editor-textarea" data-field="content" data-slide="${index}">${slide.content || 'Подробно опишите, что находится вблизи вашего объекта - детский сад, школа, магазины, торговые центры...'}</span>
                            </div>
                        </div>
                        <div class="booklet-stroen__block booklet-stroen__img">
                            <input type="file" accept="image/*" class="hide-input" id="infra-img2-${index}" data-slide="${index}" data-position="1" data-upload-type="gallery">
                            <button class="btn-edit-bg btn-edit-bg--center" onclick="document.getElementById('infra-img2-${index}').click()"></button>
                            ${img2 ? `<img src="${img2}" alt="">` : ''}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function generateFeaturesSlide(slide, index) {
    const items = slide.items || [];
    const itemsHtml = items.map((item, itemIndex) => `
        <div class="booklet-osobenn__item">
            <div contenteditable="true" class="booklet-osobenn__text editor-field" data-feature-field="text" data-slide="${index}" data-item="${itemIndex}">${item.text || 'ОСОБЕННОСТЬ'}</div>
            <div class="remove-row" onclick="removeFeature(${index}, ${itemIndex})">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                    <path d="M7.95834 15.4167C12.0314 15.4167 15.3333 12.1148 15.3333 8.04169C15.3333 3.96859 12.0314 0.666687 7.95834 0.666687C3.88524 0.666687 0.583344 3.96859 0.583344 8.04169C0.583344 12.1148 3.88524 15.4167 7.95834 15.4167Z"></path>
                    <path d="M10.7949 5.20514L5.12186 10.8782" stroke="white" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="M5.12186 5.20514L10.7949 10.8782" stroke="white" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </div>
        </div>
    `).join('');
    
    const images = slide.images || [];
    const img1 = images[0] ? (images[0].url || images[0]) : '';
    const img2 = images[1] ? (images[1].url || images[1]) : '';
    
    return `
        <div class="booklet-page page-cover">
            <div class="booklet-page__inner">
                <div class="booklet-content booklet-osobenn">
                    <div class="booklet-osobenn__wrap">
                        <div class="booklet-osobenn__left">
                            <div contenteditable="true" class="booklet-osobenn__title editor-field" data-field="title" data-slide="${index}">${slide.title || 'ОСОБЕННОСТИ'}</div>
                            <div class="booklet-osobenn__list">
                                ${itemsHtml}
                            </div>
                            <div class="add-feature-btn" onclick="addFeature(${index})">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22">
                                    <path d="M16.357 16.416C19.2372 13.5358 19.2372 8.86625 16.357 5.98614C13.4769 3.10602 8.80734 3.10602 5.92722 5.98614C3.04711 8.86625 3.04711 13.5358 5.92722 16.416C8.80734 19.2961 13.4769 19.2961 16.357 16.416Z"></path>
                                    <path d="M11.1422 7.18958V15.2125" stroke="white" stroke-linecap="round" stroke-linejoin="round"></path>
                                    <path d="M7.13071 11.201H15.1536" stroke="white" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                            </div>
                            <div class="booklet-osobenn__bottom-square"></div>
                        </div>
                        <div class="booklet-osobenn__right">
                            <div class="booklet-osobenn__top-square"></div>
                            <div class="booklet-osobenn__img">
                                <input type="file" accept="image/*" class="hide-input" id="feat-img1-${index}" data-slide="${index}" data-position="0" data-upload-type="gallery">
                                <button class="btn-edit-bg btn-edit-bg--center" onclick="document.getElementById('feat-img1-${index}').click()"></button>
                                ${img1 ? `<img src="${img1}" alt="">` : ''}
                            </div>
                            <div class="booklet-osobenn__img">
                                <input type="file" accept="image/*" class="hide-input" id="feat-img2-${index}" data-slide="${index}" data-position="1" data-upload-type="gallery">
                                <button class="btn-edit-bg btn-edit-bg--center" onclick="document.getElementById('feat-img2-${index}').click()"></button>
                                ${img2 ? `<img src="${img2}" alt="">` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function generateLocationSlide(slide, index) {
    return `
        <div class="booklet-page page-cover">
            <div class="booklet-page__inner">
                <div class="booklet-content booklet-map">
                    <div class="booklet-map__wrap">
                        <div contenteditable="true" class="booklet-map__title editor-field" data-field="title" data-slide="${index}">${slide.title || 'МЕСТОПОЛОЖЕНИЕ'}</div>
                        <div class="booklet-map__img" id="map-${index}" style="background: #e0e0e0; display: flex; align-items: center; justify-content: center; color: #999; min-height: 300px;">
                            <div>Карта будет здесь</div>
                        </div>
                        <div class="booklet-map__content">
                            <div class="booklet-map__top-square"></div>
                            <div class="booklet-map__bottom-square"></div>
                            <div class="booklet-map__info">
                                <div class="booklet-map__name">
                                    <span contenteditable="true" class="editor-field editor-textarea" data-field="location_name" data-slide="${index}">${slide.location_name || 'ЖК "Успешная продажа"'}</span>
                                </div>
                                <div class="booklet-map__text">
                                    <span contenteditable="true" class="editor-field editor-textarea" data-field="location_address" data-slide="${index}">${slide.location_address || 'Краснодарский край, Городской округ Сочи'}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function generateContactsSlide(slide, index) {
    const images = slide.images || [];
    const img1 = images[0] ? (images[0].url || images[0]) : '';
    const img2 = images[1] ? (images[1].url || images[1]) : '';
    const avatar = slide.avatar || '';
    
    return `
        <div class="booklet-page page-cover">
            <div class="booklet-page__inner">
                <div class="booklet-content booklet-contacts">
                    <div class="booklet-contacts__top-square"></div>
                    <div class="booklet-contacts__bottom-square"></div>
                    <div class="booklet-contacts__wrap">
                        <div class="booklet-contacts__block booklet-contacts__img">
                            <input type="file" accept="image/*" class="hide-input" id="contact-img1-${index}" data-slide="${index}" data-position="0" data-upload-type="gallery">
                            <button class="btn-edit-bg btn-edit-bg--center" onclick="document.getElementById('contact-img1-${index}').click()"></button>
                            ${img1 ? `<img src="${img1}" alt="">` : ''}
                        </div>
                        <div class="booklet-contacts__block booklet-contacts__img">
                            <input type="file" accept="image/*" class="hide-input" id="contact-img2-${index}" data-slide="${index}" data-position="1" data-upload-type="gallery">
                            <button class="btn-edit-bg btn-edit-bg--center" onclick="document.getElementById('contact-img2-${index}').click()"></button>
                            ${img2 ? `<img src="${img2}" alt="">` : ''}
                        </div>
                        <div class="booklet-contacts__block booklet-contacts__content">
                            <div class="booklet-contacts__header">
                                <div class="booklet-contacts__title">
                                    <div contenteditable="true" class="editor-field" data-field="contact_title" data-slide="${index}">${slide.contact_title || 'Контакты'}</div>
                                </div>
                                <div class="booklet-contacts__avatar">
                                    <div class="booklet-contacts__avatar-wrap">
                                        <input type="file" accept="image/*" class="hide-input" id="avatar-${index}" data-slide="${index}" data-upload-type="avatar">
                                        <button class="btn-edit-bg btn-edit-bg--center" onclick="document.getElementById('avatar-${index}').click()"></button>
                                        ${avatar ? `<img src="${avatar}" alt="">` : ''}
                                    </div>
                                </div>
                            </div>
                            <div class="booklet-contacts__info">
                                <div class="booklet-contacts__name">
                                    <div contenteditable="true" class="editor-field" data-field="contact_name" data-slide="${index}">${slide.contact_name || 'Slide Estate'}</div>
                                </div>
                                <div class="booklet-contacts__text">
                                    <div contenteditable="true" class="editor-field" data-field="contact_role" data-slide="${index}">${slide.contact_role || 'Онлайн-сервис для риелторов'}</div>
                                </div>
                                <div class="booklet-contacts__text">
                                    <div contenteditable="true" class="editor-field" data-field="contact_phone" data-slide="${index}">${slide.contact_phone || '+7 (900) 000-00-00'}</div>
                                </div>
                                <div class="booklet-contacts__text">
                                    <div contenteditable="true" class="editor-field" data-field="contact_messengers" data-slide="${index}">${slide.contact_messengers || 'Telegram | WhatsApp'}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function generateImageSlide(slide, index) {
    const image = slide.image || '';
    return `
        <div class="booklet-page page-cover">
            <div class="booklet-page__inner">
                <div class="booklet-content booklet-img">
                    <div class="booklet-img__top-square"></div>
                    <div class="booklet-img__bottom-square"></div>
                    <div class="booklet-img__wrap">
                        <div class="booklet-img__img">
                            <input type="file" accept="image/*" class="hide-input" id="img-upload-${index}" data-slide="${index}" data-upload-type="single">
                            <button class="btn-edit-bg btn-edit-bg--center" onclick="document.getElementById('img-upload-${index}').click()"></button>
                            ${image ? `<img src="${image}" alt="">` : ''}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function generateGallerySlide(slide, index) {
    const images = slide.images || [];
    let imagesHtml = '';
    for (let i = 0; i < 3; i++) {
        const img = images[i];
        const imgUrl = img ? (img.url || img) : '';
        imagesHtml += `
            <div class="booklet-galery__img" data-gallery-position="${i}">
                <input type="file" accept="image/*" class="hide-input" id="gallery-${index}-${i}" data-slide="${index}" data-position="${i}" data-upload-type="gallery">
                <button class="btn-edit-bg btn-edit-bg--center" onclick="document.getElementById('gallery-${index}-${i}').click()"></button>
                ${imgUrl ? `<img src="${imgUrl}" alt="">` : ''}
            </div>
        `;
    }
    return `
        <div class="booklet-page page-cover">
            <div class="booklet-page__inner">
                <div class="booklet-content booklet-galery">
                    <div class="booklet-galery__top-square"></div>
                    <div class="booklet-galery__bottom-square"></div>
                    <div class="booklet-galery__wrap">${imagesHtml}</div>
                </div>
            </div>
        </div>
    `;
}

function generateCharacteristicsSlide(slide, index) {
    const items = slide.items || [];
    const itemsHtml = items.map((item, itemIndex) => `
        <div class="booklet-char__row">
            <div class="booklet-char__item">
                <span contenteditable="true" class="editor-field editor-textarea" data-char-field="label" data-slide="${index}" data-item="${itemIndex}">${item.label || ''}</span>
            </div>
            <div class="booklet-char__item">
                <span contenteditable="true" class="editor-field editor-textarea" data-char-field="value" data-slide="${index}" data-item="${itemIndex}">${item.value || ''}</span>
            </div>
            <div class="remove-row" onclick="removeCharacteristic(${index}, ${itemIndex})">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                    <path d="M7.95834 15.4167C12.0314 15.4167 15.3333 12.1148 15.3333 8.04169C15.3333 3.96859 12.0314 0.666687 7.95834 0.666687C3.88524 0.666687 0.583344 3.96859 0.583344 8.04169C0.583344 12.1148 3.88524 15.4167 7.95834 15.4167Z"></path>
                    <path d="M10.7949 5.20514L5.12186 10.8782" stroke="white" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="M5.12186 5.20514L10.7949 10.8782" stroke="white" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </div>
        </div>
    `).join('');
    const image = slide.image || '';
    const squareHeight = items.length * 36 || 200;
    return `
        <div class="booklet-page page-cover">
            <div class="booklet-page__inner">
                <div class="booklet-content booklet-char">
                    <div class="booklet-char__wrap">
                        <div contenteditable="true" class="booklet-char__title editor-field" data-field="title" data-slide="${index}">${slide.title || 'ХАРАКТЕРИСТИКИ КВАРТИРЫ'}</div>
                        <div class="booklet-char__img">
                            <input type="file" accept="image/*" class="hide-input" id="char-img-${index}" data-slide="${index}" data-upload-type="char-image">
                            <button class="btn-edit-bg btn-edit-bg--center" onclick="document.getElementById('char-img-${index}').click()"></button>
                            ${image ? `<img src="${image}" alt="">` : ''}
                        </div>
                        <div class="booklet-char__content">
                            <div class="booklet-char__top-square" style="height: ${squareHeight}px;"></div>
                            <div class="booklet-char__bottom-square"></div>
                            <div class="booklet-char__table">
                                ${itemsHtml}
                                <div class="add-row" onclick="addCharacteristic(${index})">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22">
                                        <path d="M16.357 16.416C19.2372 13.5358 19.2372 8.86625 16.357 5.98614C13.4769 3.10602 8.80734 3.10602 5.92722 5.98614C3.04711 8.86625 3.04711 13.5358 5.92722 16.416C8.80734 19.2961 13.4769 19.2961 16.357 16.416Z"></path>
                                        <path d="M11.1422 7.18958V15.2125" stroke="white" stroke-linecap="round" stroke-linejoin="round"></path>
                                        <path d="M7.13071 11.201H15.1536" stroke="white" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function generateGridSlide(slide, index) {
    const images = slide.images || [];
    let imagesHtml = '';
    for (let i = 0; i < 4; i++) {
        const img = images[i];
        const imgUrl = img ? (img.url || img) : '';
        imagesHtml += `
            <div class="booklet-grid__img" data-grid-position="${i}">
                <input type="file" accept="image/*" class="hide-input" id="grid-${index}-${i}" data-slide="${index}" data-position="${i}" data-upload-type="grid">
                <button class="btn-edit-bg btn-edit-bg--center" onclick="document.getElementById('grid-${index}-${i}').click()"></button>
                ${imgUrl ? `<img src="${imgUrl}" alt="">` : ''}
            </div>
        `;
    }
    return `
        <div class="booklet-page page-cover">
            <div class="booklet-page__inner">
                <div class="booklet-content booklet-grid">
                    <div class="booklet-grid__top-square"></div>
                    <div class="booklet-grid__bottom-square"></div>
                    <div class="booklet-grid__wrap">${imagesHtml}</div>
                </div>
            </div>
        </div>
    `;
}

let debounceTimers = new Map();

function debounce(key, callback, delay) {
    if (debounceTimers.has(key)) {
        clearTimeout(debounceTimers.get(key));
    }
    const timer = setTimeout(() => {
        callback();
        debounceTimers.delete(key);
    }, delay);
    debounceTimers.set(key, timer);
}

function attachSlideEvents() {
    // Contenteditable fields
    document.querySelectorAll('[contenteditable="true"][data-field]').forEach(field => {
        field.addEventListener('input', function() {
            const slideIndex = parseInt(this.dataset.slide);
            const fieldName = this.dataset.field;
            slides[slideIndex][fieldName] = this.innerHTML;
            hasUnsavedChanges = true;
            triggerAutoSave();
        });
    });
    
    // Characteristic fields
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
    });
    
    // Feature fields
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
    });
    
    // Location fields
    document.querySelectorAll('[data-field="location_name"], [data-field="location_address"]').forEach(field => {
        field.addEventListener('input', function() {
            const slideIndex = parseInt(this.dataset.slide);
            const fieldName = this.dataset.field;
            slides[slideIndex][fieldName] = this.textContent;
            hasUnsavedChanges = true;
            triggerAutoSave();
        });
    });
    
    // Contact fields
    document.querySelectorAll('[data-field^="contact_"]').forEach(field => {
        field.addEventListener('input', function() {
            const slideIndex = parseInt(this.dataset.slide);
            const fieldName = this.dataset.field;
            slides[slideIndex][fieldName] = this.textContent;
            hasUnsavedChanges = true;
            triggerAutoSave();
        });
    });
    
    // File uploads
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                handleFileUpload(e.target.files[0], this);
            }
        });
    });
    
    // НОВОЕ: Инициализация конвертера валют
    attachCurrencyConverter();
}

// НОВАЯ ФУНКЦИЯ: Обработчик изменений поля цены для real-time конвертации валют
function attachCurrencyConverter() {
    document.querySelectorAll('[data-field="price"]').forEach(priceField => {
        priceField.addEventListener('input', function() {
            const slideIndex = parseInt(this.dataset.slide);
            const priceText = this.textContent;
            
            // Извлекаем числовое значение из текста (убираем все кроме цифр и пробелов)
            const match = priceText.match(/[\d\s]+/);
            if (match) {
                const numericValue = parseFloat(match[0].replace(/\s/g, ''));
                
                if (!isNaN(numericValue) && numericValue > 0) {
                    // Обновляем значение в массиве slides
                    slides[slideIndex].price = this.innerHTML;
                    slides[slideIndex].price_value = numericValue;
                    
                    // Триггерим обновление конвертера валют
                    updateCurrencyDisplay(slideIndex, numericValue);
                    
                    hasUnsavedChanges = true;
                    triggerAutoSave();
                }
            }
        });
    });
}

// НОВАЯ ФУНКЦИЯ: Обновление отображения валют в реальном времени
function updateCurrencyDisplay(slideIndex, amount) {
    const slide = slides[slideIndex];
    if (!slide || slide.type !== 'cover') return;
    
    const currency = slide.currency || 'RUB';
    const dealType = slide.deal_type || 'Аренда';
    const isRent = dealType === 'Аренда';
    
    // Курсы валют (в реальном проекте можно получать через API)
    const rates = {
        'RUB': 1,
        'USD': 0.011,
        'EUR': 0.0092,
        'CNY': 0.078,
        'KZT': 5.2
    };
    
    const symbols = {
        'RUB': '₽',
        'USD': '$',
        'EUR': '€',
        'CNY': '¥',
        'KZT': '₸'
    };
    
    const names = {
        'RUB': 'руб.',
        'USD': 'дол.',
        'EUR': 'евро',
        'CNY': 'юань',
        'KZT': 'тенге'
    };
    
    // Находим контейнер для валют в текущем слайде
    const slideElement = document.querySelector(`[data-slide-index="${slideIndex}"]`);
    if (!slideElement) return;
    
    let currenciesContainer = slideElement.querySelector('.all-currencies');
    if (!currenciesContainer) {
        // Создаём контейнер, если его нет
        const mainContent = slideElement.querySelector('.booklet-main__content');
        if (mainContent) {
            currenciesContainer = document.createElement('div');
            currenciesContainer.className = 'all-currencies';
            mainContent.appendChild(currenciesContainer);
        }
    }
    
    if (currenciesContainer && amount > 0) {
        let html = '';
        for (const [currCode, rate] of Object.entries(rates)) {
            if (currCode === currency) continue;
            
            const converted = amount * (rates[currency] / rate);
            const convertedFormatted = Math.round(converted).toLocaleString('ru-RU');
            
            html += `
                <div class="currency-item">
                    <span class="currency-value">${convertedFormatted}</span>
                    <span class="currency-symbol">${symbols[currCode]}</span>
                    <span class="currency-name">${names[currCode]}</span>
                    ${isRent ? '<span style="color: #999; margin-left: 3px;">/ мес.</span>' : ''}
                </div>
            `;
        }
        currenciesContainer.innerHTML = html;
        currenciesContainer.style.display = 'block';
    }
}

async function handleFileUpload(file, inputElement) {
    if (!file) return;
    
    const slideIndex = parseInt(inputElement.dataset.slide);
    const uploadType = inputElement.dataset.uploadType;
    const position = parseInt(inputElement.dataset.position);
    
    console.log('Upload:', {slideIndex, uploadType, position, file: file.name});
    
    const formData = new FormData();
    formData.append('image', file);
    formData.append('presentation_id', presentationId);
    formData.append('csrf_token', csrfToken);
    
    showSaving();
    
    try {
        const response = await fetch('/api.php?action=upload_image', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success && data.url) {
            console.log('Upload success:', data.url);
            
            // Update slides array based on upload type
            if (uploadType === 'background') {
                slides[slideIndex].background_image = data.url;
            } else if (uploadType === 'single') {
                slides[slideIndex].image = data.url;
            } else if (uploadType === 'char-image') {
                slides[slideIndex].image = data.url;
            } else if (uploadType === 'avatar') {
                slides[slideIndex].avatar = data.url;
            } else if (uploadType === 'gallery') {
                if (!slides[slideIndex].images) slides[slideIndex].images = [];
                slides[slideIndex].images[position] = { url: data.url };
            } else if (uploadType === 'grid') {
                if (!slides[slideIndex].images) slides[slideIndex].images = [];
                slides[slideIndex].images[position] = { url: data.url };
            }
            
            console.log('Updated slide:', slides[slideIndex]);
            
            // ИСПРАВЛЕНИЕ: Сбросить значение input для повторной загрузки
            inputElement.value = '';
            
            refreshSlide(slideIndex);
            showSaved();
            hasUnsavedChanges = true;
            triggerAutoSave();
        } else {
            // ИСПРАВЛЕНИЕ: Сбросить значение даже при ошибке
            inputElement.value = '';
            showError();
            alert('Ошибка загрузки: ' + (data.message || data.error || 'Неизвестная ошибка'));
        }
    } catch (error) {
        console.error('Upload error:', error);
        // ИСПРАВЛЕНИЕ: Сбросить значение даже при ошибке
        inputElement.value = '';
        showError();
        alert('Ошибка загрузки изображения: ' + error.message);
    }
}

function refreshSlide(index) {
    const slideElement = document.querySelector(`[data-slide-index="${index}"]`);
    if (slideElement && swiper) {
        const newHtml = generateSlideHTML(slides[index], index);
        const temp = document.createElement('div');
        temp.innerHTML = newHtml;
        slideElement.replaceWith(temp.firstElementChild);
        swiper.update();
        attachSlideEvents();
    }
}

function addCharacteristic(slideIndex) {
    if (!slides[slideIndex].items) slides[slideIndex].items = [];
    slides[slideIndex].items.push({ label: 'Название характеристики', value: 'Значение' });
    refreshSlide(slideIndex);
    hasUnsavedChanges = true;
    triggerAutoSave();
}

function removeCharacteristic(slideIndex, itemIndex) {
    if (confirm('Удалить эту характеристику?')) {
        slides[slideIndex].items.splice(itemIndex, 1);
        refreshSlide(slideIndex);
        hasUnsavedChanges = true;
        triggerAutoSave();
    }
}

function addFeature(slideIndex) {
    if (!slides[slideIndex].items) slides[slideIndex].items = [];
    slides[slideIndex].items.push({ text: 'НОВАЯ ОСОБЕННОСТЬ' });
    refreshSlide(slideIndex);
    hasUnsavedChanges = true;
    triggerAutoSave();
}

function removeFeature(slideIndex, itemIndex) {
    if (confirm('Удалить эту особенность?')) {
        slides[slideIndex].items.splice(itemIndex, 1);
        refreshSlide(slideIndex);
        hasUnsavedChanges = true;
        triggerAutoSave();
    }
}

function moveSlide(index, direction) {
    const newIndex = index + direction;
    if (newIndex < 0 || newIndex >= slides.length) return;
    [slides[index], slides[newIndex]] = [slides[newIndex], slides[index]];
    renderSlides();
    swiper.destroy();
    initSwiper();
    swiper.slideTo(newIndex);
    hasUnsavedChanges = true;
    triggerAutoSave();
}

function toggleSlideVisibility(index) {
    slides[index].hidden = !slides[index].hidden;
    refreshSlide(index);
    hasUnsavedChanges = true;
    triggerAutoSave();
}

function duplicateSlide(index) {
    const newSlide = JSON.parse(JSON.stringify(slides[index]));
    slides.splice(index + 1, 0, newSlide);
    renderSlides();
    swiper.destroy();
    initSwiper();
    swiper.slideTo(index + 1);
    hasUnsavedChanges = true;
    triggerAutoSave();
}

function deleteSlide(index) {
    if (slides.length === 1) {
        alert('Нельзя удалить последний слайд');
        return;
    }
    if (confirm('Удалить этот слайд?')) {
        slides.splice(index, 1);
        renderSlides();
        swiper.destroy();
        initSwiper();
        hasUnsavedChanges = true;
        triggerAutoSave();
    }
}

function showAddSlideDialog() {
    document.getElementById('addSlideModal').classList.add('active');
}

function closeAddSlideDialog() {
    document.getElementById('addSlideModal').classList.remove('active');
}

function addSlideOfType(type) {
    const defaultSlides = {
        'cover': { 
            type, 
            title: 'ЭКСКЛЮЗИВНОЕ<br>ПРЕДЛОЖЕНИЕ', 
            subtitle: 'АБСОЛЮТНО НОВЫЙ ТАУНХАУС<br>НА ПЕРВОЙ ЛИНИИ', 
            deal_type: 'Аренда', 
            price: '11 000 000 ₽', 
            price_value: 11000000,
            currency: 'RUB',
            background_image: '', 
            hidden: false 
        },
        'description': { 
            type, 
            title: 'ОПИСАНИЕ', 
            content: 'Подробно опишите о своем объекте - транспортная доступность, местоположение, подробная планировка, особенности.', 
            images: [], 
            hidden: false 
        },
        'infrastructure': { 
            type, 
            title: 'ИНФРАСТРУКТУРА', 
            content: 'Подробно опишите, что находится вблизи вашего объекта - детский сад, школа, магазины, торговые центры...', 
            images: [], 
            hidden: false 
        },
        'features': { 
            type, 
            title: 'ОСОБЕННОСТИ', 
            items: [{ text: 'ПАНОРАМНЫЙ ВИД' }], 
            images: [], 
            hidden: false 
        },
        'location': { 
            type, 
            title: 'МЕСТОПОЛОЖЕНИЕ', 
            location_name: 'ЖК "Новый"', 
            location_address: 'Адрес объекта', 
            hidden: false 
        },
        'contacts': { 
            type, 
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
            type, 
            image: '', 
            hidden: false 
        },
        'gallery': { 
            type, 
            images: [], 
            hidden: false 
        },
        'grid': { 
            type, 
            images: [], 
            hidden: false 
        },
        'characteristics': { 
            type, 
            title: 'ХАРАКТЕРИСТИКИ КВАРТИРЫ', 
            items: [
                { label: 'Площадь квартиры:', value: '350 кв.м.' },
                { label: 'Количество комнат:', value: '5' }
            ], 
            image: '', 
            hidden: false 
        }
    };
    
    const newSlide = defaultSlides[type] || { type, hidden: false };
    slides.push(newSlide);
    
    renderSlides();
    swiper.destroy();
    initSwiper();
    swiper.slideTo(slides.length - 1);
    
    closeAddSlideDialog();
    hasUnsavedChanges = true;
    triggerAutoSave();
}

function initAutoSave() {
    const titleInput = document.getElementById('presentationTitle');
    if (titleInput) {
        titleInput.addEventListener('input', () => {
            hasUnsavedChanges = true;
            debounce('title', () => triggerAutoSave(), 1500);
        });
    }
}

function triggerAutoSave() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(() => {
        if (hasUnsavedChanges) {
            savePresentation(true);
        }
    }, AUTOSAVE_DELAY);
}

async function savePresentation(isAuto = false) {
    const btnSave = document.getElementById('btnSave');
    if (!isAuto && btnSave) {
        btnSave.disabled = true;
        btnSave.innerHTML = '<div class="spinner"></div> Сохранение...';
    }
    showSaving();
    
    const formData = new FormData();
    formData.append('id', presentationId);
    formData.append('title', document.getElementById('presentationTitle').value);
    formData.append('slides_data', JSON.stringify(slides));
    formData.append('theme_color', themeColor);
    formData.append('status', isAuto ? 'draft' : 'published');
    formData.append('csrf_token', csrfToken);
    
    try {
        const response = await fetch('/api.php?action=update_presentation', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            hasUnsavedChanges = false;
            showSaved();
        } else {
            showError();
            if (!isAuto) alert('Ошибка сохранения: ' + (data.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Save error:', error);
        showError();
        if (!isAuto) alert('Ошибка сохранения: ' + error.message);
    } finally {
        if (!isAuto && btnSave) {
            btnSave.disabled = false;
            btnSave.innerHTML = '<i class="fas fa-save"></i> Сохранить';
        }
    }
}

function preventDataLoss() {
    window.addEventListener('beforeunload', (e) => {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = '';
            return '';
        }
    });
}

function showSaving() {
    const indicator = document.getElementById('saveIndicator');
    const autoIndicator = document.getElementById('autoSaveIndicator');
    if (indicator) {
        indicator.className = 'save-indicator saving';
        indicator.style.display = 'block';
        document.getElementById('saveText').innerHTML = 'Сохранение...';
    }
    if (autoIndicator) {
        autoIndicator.innerHTML = '<div class="spinner" style="width: 14px; height: 14px; border-width: 2px;"></div> <span>Сохранение...</span>';
    }
}

function showSaved() {
    const indicator = document.getElementById('saveIndicator');
    const autoIndicator = document.getElementById('autoSaveIndicator');
    if (indicator) {
        indicator.className = 'save-indicator saved';
        document.getElementById('saveText').innerHTML = '<i class="fas fa-check"></i> Сохранено';
        setTimeout(() => indicator.style.display = 'none', 2500);
    }
    if (autoIndicator) {
        autoIndicator.innerHTML = '<i class="fas fa-check-circle"></i> <span>Сохранено</span>';
    }
}

function showError() {
    const indicator = document.getElementById('saveIndicator');
    const autoIndicator = document.getElementById('autoSaveIndicator');
    if (indicator) {
        indicator.className = 'save-indicator error';
        document.getElementById('saveText').innerHTML = '<i class="fas fa-exclamation-triangle"></i> Ошибка';
        setTimeout(() => indicator.style.display = 'none', 3000);
    }
    if (autoIndicator) {
        autoIndicator.innerHTML = '<i class="fas fa-exclamation-triangle"></i> <span>Ошибка</span>';
    }
}

function previewPresentation() {
    savePresentation(false).then(() => {
        window.open(`/api.php?action=generate_presentation&id=${presentationId}`, '_blank');
    });
}