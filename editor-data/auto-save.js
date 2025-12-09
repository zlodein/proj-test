// Функции автосохранения

// Сохранение презентации
async function savePresentation(isAuto = false, status = 'draft') {
    const btnSave = document.getElementById('btnSave');
    const btnSaveDraft = document.querySelector('.btn-save-draft');
    const isPublishing = status === 'published' && !isAuto;
    
    if (isPublishing && btnSave) {
        btnSave.disabled = true;
        btnSave.innerHTML = '<div class="spinner"></div> Публикация...';
    }
    
    if (status === 'draft' && !isAuto && btnSaveDraft) {
        btnSaveDraft.disabled = true;
        btnSaveDraft.innerHTML = '<div class="spinner"></div> Сохранение...';
    }
    
    showSaving();
    
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
    formData.append('title', document.getElementById('presentationTitle').value);
    formData.append('slides_data', JSON.stringify(slides));
    formData.append('theme_color', currentThemeColor);
    formData.append('status', status);
    formData.append('csrf_token', csrfToken);
    formData.append('show_all_currencies', document.getElementById('showAllCurrencies').checked);
    
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
            showSaved();
            
            if (isPublishing) {
                showNotification('Презентация успешно опубликована!', 'success');
                if (btnSave) {
                    setTimeout(() => {
                        btnSave.disabled = false;
                        btnSave.innerHTML = '<i class="fas fa-save"></i> Опубликовать';
                    }, 500);
                }
            } else if (status === 'draft' && !isAuto) {
                showNotification('Черновик сохранен', 'success');
                if (btnSaveDraft) {
                    setTimeout(() => {
                        btnSaveDraft.disabled = false;
                        btnSaveDraft.innerHTML = '<i class="fas fa-file-alt"></i> Сохранить черновик';
                    }, 500);
                }
            }
        } else {
            showError();
            if (!isAuto) {
                showNotification('Ошибка сохранения: ' + (data.message || 'Unknown error'), 'error');
                if (isPublishing && btnSave) {
                    btnSave.disabled = false;
                    btnSave.innerHTML = '<i class="fas fa-save"></i> Опубликовать';
                }
                if (status === 'draft' && !isAuto && btnSaveDraft) {
                    btnSaveDraft.disabled = false;
                    btnSaveDraft.innerHTML = '<i class="fas fa-file-alt"></i> Сохранить черновик';
                }
            }
        }
    } catch (error) {
        console.error('Save error:', error);
        showError();
        if (!isAuto) {
            showNotification('Ошибка соединения с сервером', 'error');
            if (isPublishing && btnSave) {
                btnSave.disabled = false;
                btnSave.innerHTML = '<i class="fas fa-save"></i> Опубликовать';
            }
            if (status === 'draft' && !isAuto && btnSaveDraft) {
                btnSaveDraft.disabled = false;
                btnSaveDraft.innerHTML = '<i class="fas fa-file-alt"></i> Сохранить черновик';
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

// Показать статус сохранения
function showSaving() {
    const indicator = document.getElementById('saveIndicator');
    const autoIndicator = document.getElementById('autoSaveIndicator');
    
    indicator.className = 'save-indicator saving';
    indicator.style.display = 'block';
    document.getElementById('saveText').innerHTML = 'Сохранение...';
    
    autoIndicator.innerHTML = '<div class="spinner" style="width: 14px; height: 14px; border-width: 2px;"></div> <span>Сохранение...</span>';
}

// Показать статус сохранено
function showSaved() {
    const indicator = document.getElementById('saveIndicator');
    const autoIndicator = document.getElementById('autoSaveIndicator');
    
    indicator.className = 'save-indicator saved';
    document.getElementById('saveText').innerHTML = '<i class="fas fa-check"></i> Сохранено';
    
    autoIndicator.innerHTML = '<i class="fas fa-check-circle"></i> <span>Сохранено</span>';
    
    setTimeout(() => indicator.style.display = 'none', 2500);
}

// Показать ошибку сохранения
function showError() {
    const indicator = document.getElementById('saveIndicator');
    const autoIndicator = document.getElementById('autoSaveIndicator');
    
    indicator.className = 'save-indicator error';
    document.getElementById('saveText').innerHTML = '<i class="fas fa-exclamation-triangle"></i> Ошибка';
    
    autoIndicator.innerHTML = '<i class="fas fa-exclamation-triangle"></i> <span>Ошибка</span>';
    
    setTimeout(() => indicator.style.display = 'none', 3000);
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