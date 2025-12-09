// Генерация слайда "Обложка"
function generateCoverSlide(slide, index) {
    const bgImage = slide.background_image || '';
    const dealType = slide.deal_type || 'Аренда';
    const currency = slide.currency || 'RUB';
    const priceValue = slide.price_value || 1000000;
    const isRent = dealType === 'Аренда';
    
    const formattedPrice = formatNumber(priceValue);
    const placeholder = isRent ? '0 000 000 / месяц' : '0 000 000';
    
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
                                <div class="deal-type-selector">
                                    <select class="deal-type-select editor-field" data-field="deal_type" data-slide="${index}" onchange="updateDealType(${index}, this.value)">
                                        <option value="Аренда" ${dealType === 'Аренда' ? 'selected' : ''}>Аренда</option>
                                        <option value="Продажа" ${dealType === 'Продажа' ? 'selected' : ''}>Продажа</option>
                                    </select>
                                </div>
                                <div class="price-input-wrapper">
                                    <div class="price-input-group">
                                        <input type="text" 
                                               class="price-input editor-field" 
                                               data-field="price_value" 
                                               data-slide="${index}"
                                               value="${formattedPrice}"
                                               oninput="formatPriceInput(this)"
                                               onblur="updatePriceValue(${index}, this.value)"
                                               placeholder="${placeholder}">
                                        <div class="currency-selector">
                                            <select class="currency-select editor-field" data-field="currency" data-slide="${index}" onchange="updateCurrency(${index}, this.value)">
                                                <option value="RUB" ${currency === 'RUB' ? 'selected' : ''}>₽</option>
                                                <option value="USD" ${currency === 'USD' ? 'selected' : ''}>$</option>
                                                <option value="EUR" ${currency === 'EUR' ? 'selected' : ''}>€</option>
                                                <option value="CNY" ${currency === 'CNY' ? 'selected' : ''}>¥</option>
                                                <option value="KZT" ${currency === 'KZT' ? 'selected' : ''}>₸</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="currency-converter" id="currency-converter-${index}">
                                        <span class="converted-prices"></span>
                                        <button type="button" class="refresh-rates-btn" onclick="refreshCurrencyRates()" title="Обновить курсы">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Генерация слайда "Изображение"
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

// Генерация слайда "Галерея"
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
                    <div class="booklet-galery__wrap">
                        ${imagesHtml}
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Генерация слайда "Характеристики"
function generateCharacteristicsSlide(slide, index) {
    const items = slide.items || [];
    const displayItems = items.slice(0, 12);
    
    const itemsHtml = displayItems.map((item, itemIndex) => `
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
    const squareHeight = displayItems.length * 36 || 200;
    
    return `
        <div class="booklet-page page-cover">
            <div class="booklet-page__inner">
                <div class="booklet-content booklet-char">
                    <div class="booklet-char__wrap">
                        <div contenteditable="true" class="booklet-char__title editor-field" data-field="title" data-slide="${index}">${slide.title || 'ХАРАКТЕРИСТИКИ КВАРТИРЫ'}</div>
                        ${items.length >= 12 ? '<div class="limit-warning">Достигнут лимит 12 строк</div>' : ''}
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
                                ${items.length < 12 ? `
                                    <div class="add-row" onclick="addCharacteristic(${index})">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22">
                                            <path d="M16.357 16.416C19.2372 13.5358 19.2372 8.86625 16.357 5.98614C13.4769 3.10602 8.80734 3.10602 5.92722 5.98614C3.04711 8.86625 3.04711 13.5358 5.92722 16.416C8.80734 19.2961 13.4769 19.2961 16.357 16.416Z"></path>
                                            <path d="M11.1422 7.18958V15.2125" stroke="white" stroke-linecap="round" stroke-linejoin="round"></path>
                                            <path d="M7.13071 11.201H15.1536" stroke="white" stroke-linecap="round" stroke-linejoin="round"></path>
                                        </svg>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Генерация слайда "Сетка"
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
                    <div class="booklet-grid__wrap">
                        ${imagesHtml}
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Генерация слайда "Описание"
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

// Генерация слайда "Инфраструктура"
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
                        <div class="booklet-stroen__block booklet-stroen__content">
                            <div contenteditable="true" class="booklet-stroen__title editor-field" data-field="title" data-slide="${index}">${slide.title || 'ИНФРАСТРУКТУРА'}</div>
                            <div class="booklet-stroen__text">
                                <span contenteditable="true" class="editor-field editor-textarea" data-field="content" data-slide="${index}">${slide.content || 'Подробно опишите, что находится вблизи вашего объекта - детский сад, школа, магазины, торговые центры...'}</span>
                            </div>
                        </div>
                        <div class="booklet-stroen__grid">
                            <div class="booklet-stroen__block booklet-stroen__img">
                                <input type="file" accept="image/*" class="hide-input" id="infra-img1-${index}" data-slide="${index}" data-position="0" data-upload-type="gallery">
                                <button class="btn-edit-bg btn-edit-bg--center" onclick="document.getElementById('infra-img1-${index}').click()"></button>
                                ${img1 ? `<img src="${img1}" alt="">` : ''}
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
        </div>
    `;
}

// Генерация слайда "Особенности"
function generateFeaturesSlide(slide, index) {
    const items = slide.items || [];
    const displayItems = items.slice(0, 9);
    
    const itemsHtml = displayItems.map((item, itemIndex) => `
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
                            ${items.length >= 9 ? '<div class="limit-warning">Достигнут лимит 9 строк</div>' : ''}
                            <div class="booklet-osobenn__list">
                                ${itemsHtml}
                            </div>
                            ${items.length < 9 ? `
                                <div class="add-feature-btn" onclick="addFeature(${index})">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22">
                                        <path d="M16.357 16.416C19.2372 13.5358 19.2372 8.86625 16.357 5.98614C13.4769 3.10602 8.80734 3.10602 5.92722 5.98614C3.04711 8.86625 3.04711 13.5358 5.92722 16.416C8.80734 19.2961 13.4769 19.2961 16.357 16.416Z"></path>
                                        <path d="M11.1422 7.18958V15.2125" stroke="white" stroke-linecap="round" stroke-linejoin="round"></path>
                                        <path d="M7.13071 11.201H15.1536" stroke="white" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                </div>
                            ` : ''}
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

// Генерация слайда "Местоположение"
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

// Генерация слайда "Контакты"
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
                        <div class="booklet-contacts-grid">
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

// Генерация HTML для слайда с оберткой
function generateSlideHTML(slide, index) {
    const generator = slideGenerators[slide.type] || generateCoverSlide;
    
    try {
        const content = generator(slide, index);
        return `
            <div class="swiper-slide editor__slide" data-slide-index="${index}" data-slide-type="${slide.type}">
                <div class="editor__inner">
                    <div class="editor__inner-toolbar">
                        <div class="editor__control-list">
                            <div class="editor__control-item">
                                <button type="button" class="editor-btn" onclick="moveSlide(${index}, 1)" ${index >= slides.length - 1 ? 'disabled' : ''} data-tooltip="Переместить вниз">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                            <div class="editor__control-item">
                                <button type="button" class="editor-btn" onclick="moveSlide(${index}, -1)" ${index === 0 ? 'disabled' : ''} data-tooltip="Переместить вверх">
                                    <i class="fas fa-chevron-up"></i>
                                </button>
                            </div>
                            <div class="editor__control-item">
                                <button type="button" class="editor-btn editor-btn-hide ${slide.hidden ? 'active' : ''}" onclick="toggleSlideVisibility(${index})" data-tooltip="${slide.hidden ? 'Показать слайд' : 'Скрыть слайд'}">
                                    <i class="fas ${slide.hidden ? 'fa-eye-slash' : 'fa-eye'}"></i>
                                </button>
                            </div>
                            <div class="editor__control-item">
                                <button type="button" class="editor-btn" onclick="duplicateSlide(${index})" data-tooltip="Дублировать слайд">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <div class="editor__control-item">
                                <button type="button" class="editor-btn" onclick="deleteSlide(${index})" data-tooltip="Удалить слайд">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="slide-type-badge" style="background: #666; color: white; padding: 2px 6px; border-radius: 3px; font-size: 10px; margin-top: 5px;">
                            ${slide.type}
                        </div>
                    </div>
                    <div class="editor__inner-content">
                        ${content}
                    </div>
                </div>
            </div>
        `;
    } catch (error) {
        return `
            <div class="swiper-slide editor__slide" data-slide-index="${index}">
                <div style="padding: 40px; background: #ffcccc; border-radius: 10px; color: #cc0000;">
                    <h3>Ошибка генерации слайда</h3>
                    <p>Тип: ${slide.type}</p>
                    <p>Ошибка: ${error.message}</p>
                </div>
            </div>
        `;
    }
}