const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const presentationId = parseInt(document.querySelector('meta[name="presentation-id"]').content);
let slides = JSON.parse(document.querySelector('#slides-data').textContent);
let swiper = null;
let autoSaveTimer = null;

document.addEventListener('DOMContentLoaded', () => {
    initSwiper();
    initAutoSave();
});

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
                console.log('Active slide:', this.activeIndex);
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
                            <button type="button" class="editor-btn editor-btn-hide ${slide.hidden ? '' : 'active'}" onclick="toggleSlideVisibility(${index})">
                            </button>
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
                <div class="editor__inner-content">
                    ${content}
                </div>
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
                                <div contenteditable="true" class="booklet-main__price editor-field" data-field="price" data-slide="${index}">${slide.price || '1 000 000 ₽ / месяц'}</div>
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

function attachSlideEvents() {
    document.querySelectorAll('[contenteditable="true"][data-field]').forEach(field => {
        field.addEventListener('input', function() {
            const slideIndex = parseInt(this.dataset.slide);
            const fieldName = this.dataset.field;
            slides[slideIndex][fieldName] = this.innerHTML;
            triggerAutoSave();
        });
        field.addEventListener('blur', function() {
            const slideIndex = parseInt(this.dataset.slide);
            const fieldName = this.dataset.field;
            slides[slideIndex][fieldName] = this.innerHTML;
            triggerAutoSave();
        });
    });
    
    document.querySelectorAll('[data-char-field]').forEach(field => {
        field.addEventListener('input', function() {
            const slideIndex = parseInt(this.dataset.slide);
            const itemIndex = parseInt(this.dataset.item);
            const fieldName = this.dataset.charField;
            if (!slides[slideIndex].items) slides[slideIndex].items = [];
            if (!slides[slideIndex].items[itemIndex]) slides[slideIndex].items[itemIndex] = {};
            slides[slideIndex].items[itemIndex][fieldName] = this.textContent;
            triggerAutoSave();
        });
        field.addEventListener('blur', function() {
            const slideIndex = parseInt(this.dataset.slide);
            const itemIndex = parseInt(this.dataset.item);
            const fieldName = this.dataset.charField;
            if (!slides[slideIndex].items) slides[slideIndex].items = [];
            if (!slides[slideIndex].items[itemIndex]) slides[slideIndex].items[itemIndex] = {};
            slides[slideIndex].items[itemIndex][fieldName] = this.textContent;
            triggerAutoSave();
        });
    });
    
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                handleFileUpload(e.target.files[0], this);
            }
        });
    });
}

async function handleFileUpload(file, inputElement) {
    if (!file) return;
    const slideIndex = parseInt(inputElement.dataset.slide);
    const uploadType = inputElement.dataset.uploadType;
    const position = parseInt(inputElement.dataset.position);
    
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
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const data = await response.json();
        
        if (data.success && data.url) {
            if (uploadType === 'background') slides[slideIndex].background_image = data.url;
            else if (uploadType === 'single') slides[slideIndex].image = data.url;
            else if (uploadType === 'char-image') slides[slideIndex].image = data.url;
            else if (uploadType === 'gallery') {
                if (!slides[slideIndex].images) slides[slideIndex].images = [];
                slides[slideIndex].images[position] = { url: data.url };
            } else if (uploadType === 'grid') {
                if (!slides[slideIndex].images) slides[slideIndex].images = [];
                slides[slideIndex].images[position] = { url: data.url };
            }
            refreshSlide(slideIndex);
            showSaved();
            triggerAutoSave();
        } else {
            showError();
            alert('Ошибка загрузки: ' + (data.message || data.error || 'Неизвестная ошибка'));
        }
    } catch (error) {
        console.error('Upload error:', error);
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
    triggerAutoSave();
}

function removeCharacteristic(slideIndex, itemIndex) {
    if (confirm('Удалить эту характеристику?')) {
        slides[slideIndex].items.splice(itemIndex, 1);
        refreshSlide(slideIndex);
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
    triggerAutoSave();
}

function toggleSlideVisibility(index) {
    slides[index].hidden = !slides[index].hidden;
    refreshSlide(index);
    triggerAutoSave();
}

function duplicateSlide(index) {
    const newSlide = JSON.parse(JSON.stringify(slides[index]));
    slides.splice(index + 1, 0, newSlide);
    renderSlides();
    swiper.destroy();
    initSwiper();
    swiper.slideTo(index + 1);
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
    const newSlide = {
        type: type,
        title: '',
        subtitle: '',
        deal_type: '',
        price: '',
        background_image: '',
        image: '',
        content: '',
        items: type === 'characteristics' ? [
            { label: 'Площадь квартиры:', value: '350 кв.м.' },
            { label: 'Количество комнат:', value: '5' }
        ] : [],
        images: [],
        hidden: false
    };
    slides.push(newSlide);
    renderSlides();
    swiper.destroy();
    initSwiper();
    swiper.slideTo(slides.length - 1);
    closeAddSlideDialog();
    triggerAutoSave();
}

function initAutoSave() {
    document.getElementById('presentationTitle').addEventListener('input', triggerAutoSave);
}

function triggerAutoSave() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(() => savePresentation(true), 2000);
}

async function savePresentation(isAuto = false) {
    const btnSave = document.getElementById('btnSave');
    if (!isAuto) {
        btnSave.disabled = true;
        btnSave.innerHTML = '<div class="spinner"></div> Сохранение...';
    }
    showSaving();
    
    const formData = new FormData();
    formData.append('id', presentationId);
    formData.append('title', document.getElementById('presentationTitle').value);
    formData.append('slides_data', JSON.stringify(slides));
    formData.append('csrf_token', csrfToken);
    
    try {
        const response = await fetch('/api.php?action=update_presentation', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
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
        if (!isAuto) {
            btnSave.disabled = false;
            btnSave.innerHTML = '<i class="fas fa-save"></i> Сохранить';
        }
    }
}

function showSaving() {
    const indicator = document.getElementById('saveIndicator');
    const autoIndicator = document.getElementById('autoSaveIndicator');
    indicator.className = 'save-indicator saving';
    document.getElementById('saveText').innerHTML = 'Сохранение...';
    autoIndicator.innerHTML = '<div class="spinner" style="width: 14px; height: 14px; border-width: 2px;"></div> <span>Сохранение...</span>';
}

function showSaved() {
    const indicator = document.getElementById('saveIndicator');
    const autoIndicator = document.getElementById('autoSaveIndicator');
    indicator.className = 'save-indicator saved';
    document.getElementById('saveText').innerHTML = '<i class="fas fa-check"></i> Сохранено';
    autoIndicator.innerHTML = '<i class="fas fa-check-circle"></i> <span>Сохранено</span>';
    setTimeout(() => indicator.style.display = 'none', 2500);
}

function showError() {
    const indicator = document.getElementById('saveIndicator');
    const autoIndicator = document.getElementById('autoSaveIndicator');
    indicator.className = 'save-indicator error';
    document.getElementById('saveText').innerHTML = '<i class="fas fa-exclamation-triangle"></i> Ошибка';
    autoIndicator.innerHTML = '<i class="fas fa-exclamation-triangle"></i> <span>Ошибка</span>';
    setTimeout(() => indicator.style.display = 'none', 3000);
}

function previewPresentation() {
    savePresentation(false).then(() => {
        window.open(`/api.php?action=generate_presentation&id=${presentationId}`, '_blank');
    });
}
