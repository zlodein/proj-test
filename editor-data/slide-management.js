// Управление слайдами
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

// Переключение видимости слайда
function toggleSlideVisibility(index) {
    slides[index].hidden = !slides[index].hidden;
    refreshSlide(index);
    hasUnsavedChanges = true;
    triggerAutoSave();
}

// Дублирование слайда
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

// Удаление слайда
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

// Открытие диалога добавления слайда
function showAddSlideDialog() {
    document.getElementById('addSlideModal').classList.add('active');
}

// Закрытие диалога добавления слайда
function closeAddSlideDialog() {
    document.getElementById('addSlideModal').classList.remove('active');
}

// Добавление слайда определенного типа
function addSlideOfType(type) {
    const newSlide = defaultSlides[type] || { type: type, hidden: false };
    slides.push(newSlide);
    
    renderSlides();
    if (swiper) swiper.destroy();
    initSwiper();
    swiper.slideTo(slides.length - 1);
    
    closeAddSlideDialog();
    hasUnsavedChanges = true;
    triggerAutoSave();
}

// Функции для работы с индикатором сохранения
function updateSaveIndicator(state, message) {
    const saveIndicator = document.getElementById('saveIndicator');
    const saveText = document.getElementById('saveText');
    
    if (saveIndicator && saveText) {
        saveIndicator.style.display = 'block';
        saveIndicator.className = `save-indicator ${state}`;
        saveText.innerHTML = message;
        
        if (state === 'saved' || state === 'error') {
            setTimeout(() => {
                saveIndicator.style.display = 'none';
            }, state === 'saved' ? 2500 : 3000);
        }
    }
}

// Загрузка файла
async function handleFileUpload(file, inputElement) {
    if (!file) return { success: false, error: 'Файл не выбран' };
    
    const slideIndex = parseInt(inputElement.dataset.slide);
    const uploadType = inputElement.dataset.uploadType;
    const position = parseInt(inputElement.dataset.position);
    
    const formData = new FormData();
    formData.append('image', file);
    formData.append('presentation_id', presentationId);
    formData.append('csrf_token', csrfToken);
    
    // Показываем индикатор загрузки
    updateSaveIndicator('saving', 'Загрузка изображения...');
    
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
            // Обновляем данные слайда
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
            
            // Показываем успех
            updateSaveIndicator('saved', '<i class="fas fa-check"></i> Изображение загружено');
            
            hasUnsavedChanges = true;
            triggerAutoSave();
            
            // Возвращаем результат
            return {
                success: true,
                url: data.url,
                slideIndex: slideIndex,
                uploadType: uploadType,
                position: position
            };
        } else {
            // Показываем ошибку
            updateSaveIndicator('error', '<i class="fas fa-exclamation-triangle"></i> Ошибка загрузки');
            alert('Ошибка загрузки: ' + (data.message || data.error || 'Неизвестная ошибка'));
            return { success: false, error: data.message || data.error };
        }
    } catch (error) {
        console.error('Upload error:', error);
        updateSaveIndicator('error', '<i class="fas fa-exclamation-triangle"></i> Ошибка');
        alert('Ошибка загрузки изображения: ' + error.message);
        return { success: false, error: error.message };
    }
}