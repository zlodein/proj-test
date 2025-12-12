// Функции управления выпадающим меню для мобильной версии

// Открыть выпадающее меню
function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenuDropdown');
    if (menu) {
        menu.classList.toggle('open');
        if (menu.classList.contains('open')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
}

// Закрыть выпадающее меню
function closeMobileMenu() {
    const menu = document.getElementById('mobileMenuDropdown');
    if (menu) {
        menu.classList.remove('open');
        document.body.style.overflow = '';
    }
}

// Открыть модальное окно добавления слайда
function openAddSlideModal() {
    closeMobileMenu();
    const modal = document.getElementById('mobAddSlideModal');
    if (modal) {
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
}

// Закрыть модальное окно добавления слайда
function closeAddSlideModal() {
    const modal = document.getElementById('mobAddSlideModal');
    if (modal) {
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }
}

// Добавить слайд определенного типа - ПРЯМАЯ РЕАЛИЗАЦИЯ
function addSlideOfType(slideType) {
    closeAddSlideModal();
    
    console.log('addSlideOfType called with:', slideType);
    console.log('defaultSlides:', typeof defaultSlides !== 'undefined' ? 'exists' : 'undefined');
    console.log('slides:', typeof slides !== 'undefined' ? slides.length : 'undefined');
    console.log('currentSlideIndex:', typeof currentSlideIndex !== 'undefined' ? currentSlideIndex : 'undefined');
    
    // Проверяем наличие необходимых переменных
    if (typeof slides === 'undefined') {
        alert('Ошибка: массив slides не определен');
        return;
    }
    
    if (typeof defaultSlides === 'undefined') {
        alert('Ошибка: объект defaultSlides не определен');
        return;
    }
    
    if (!defaultSlides[slideType]) {
        alert('Ошибка: шаблон для типа "' + slideType + '" не найден');
        return;
    }
    
    try {
        // Создаем новый слайд из шаблона
        const newSlide = JSON.parse(JSON.stringify(defaultSlides[slideType]));
        console.log('Created new slide:', newSlide);
        
        // Определяем позицию для вставки
        let insertIndex;
        if (typeof currentSlideIndex !== 'undefined' && currentSlideIndex >= 0) {
            insertIndex = currentSlideIndex + 1;
        } else if (typeof mobEditorSwiper !== 'undefined' && mobEditorSwiper) {
            insertIndex = mobEditorSwiper.activeIndex + 1;
        } else {
            insertIndex = slides.length;
        }
        
        console.log('Insert index:', insertIndex);
        
        // Добавляем слайд
        slides.splice(insertIndex, 0, newSlide);
        console.log('Slide added. Total slides:', slides.length);
        
        // Обновляем навигацию
        if (typeof renderMobileNavigation === 'function') {
            console.log('Calling renderMobileNavigation');
            renderMobileNavigation();
        }
        
        // Обновляем слайды
        if (typeof renderMobileSlides === 'function') {
            console.log('Calling renderMobileSlides');
            renderMobileSlides();
        }
        
        // Обновляем Swiper
        if (typeof mobEditorSwiper !== 'undefined' && mobEditorSwiper) {
            console.log('Updating mobEditorSwiper');
            mobEditorSwiper.update();
        }
        
        if (typeof mobNavSwiper !== 'undefined' && mobNavSwiper) {
            console.log('Updating mobNavSwiper');
            mobNavSwiper.update();
        }
        
        // Переходим на новый слайд
        setTimeout(() => {
            console.log('Switching to slide:', insertIndex);
            if (typeof switchToSlide === 'function') {
                switchToSlide(insertIndex);
            } else if (mobEditorSwiper) {
                mobEditorSwiper.slideTo(insertIndex);
            }
            
            if (typeof updateButtons === 'function') {
                updateButtons();
            }
        }, 100);
        
        // Помечаем изменения
        if (typeof hasUnsavedChanges !== 'undefined') {
            hasUnsavedChanges = true;
        }
        
        // Показываем уведомление
        if (typeof showMobileNotification === 'function') {
            showMobileNotification('Слайд добавлен');
        } else {
            alert('Слайд добавлен');
        }
        
        // Автосохранение
        if (typeof triggerAutoSave === 'function') {
            triggerAutoSave();
        }
        
        console.log('addSlideOfType completed successfully');
        
    } catch (error) {
        console.error('Error in addSlideOfType:', error);
        alert('Ошибка при добавлении слайда: ' + error.message);
    }
}

// Удалить текущий слайд
function deleteCurrentSlide() {
    closeMobileMenu();
    
    if (typeof deleteMobileSlide === 'function') {
        deleteMobileSlide();
        return;
    }
    
    if (typeof slides === 'undefined' || typeof mobEditorSwiper === 'undefined') {
        showMobileNotification('Ошибка удаления', 'error');
        return;
    }
    
    if (slides.length <= 1) {
        showMobileNotification('Нельзя удалить единственный слайд', 'error');
        return;
    }
    
    if (!confirm('Вы уверены, что хотите удалить этот слайд?')) {
        return;
    }
    
    const currentIndex = mobEditorSwiper.activeIndex;
    slides.splice(currentIndex, 1);
    
    if (typeof renderMobileNavigation === 'function') {
        renderMobileNavigation();
    }
    
    if (typeof renderMobileSlides === 'function') {
        renderMobileSlides();
    }
    
    if (mobEditorSwiper) {
        mobEditorSwiper.update();
    }
    
    if (typeof mobNavSwiper !== 'undefined' && mobNavSwiper) {
        mobNavSwiper.update();
    }
    
    const newIndex = Math.min(currentIndex, slides.length - 1);
    setTimeout(() => {
        if (typeof switchToSlide === 'function') {
            switchToSlide(newIndex);
        }
        if (typeof updateButtons === 'function') {
            updateButtons();
        }
    }, 100);
    
    if (typeof hasUnsavedChanges !== 'undefined') {
        hasUnsavedChanges = true;
    }
    
    showMobileNotification('Слайд удален');
    
    if (typeof triggerAutoSave === 'function') {
        triggerAutoSave();
    }
}

// Переместить слайд назад
function moveSlideBackward() {
    closeMobileMenu();
    
    if (typeof moveSlideMobileLeft === 'function') {
        moveSlideMobileLeft();
        return;
    }
    
    if (typeof slides === 'undefined' || typeof mobEditorSwiper === 'undefined') {
        showMobileNotification('Ошибка перемещения', 'error');
        return;
    }
    
    const currentIndex = mobEditorSwiper.activeIndex;
    
    if (currentIndex === 0) {
        showMobileNotification('Слайд уже первый', 'error');
        return;
    }
    
    const temp = slides[currentIndex];
    slides[currentIndex] = slides[currentIndex - 1];
    slides[currentIndex - 1] = temp;
    
    if (typeof renderMobileNavigation === 'function') {
        renderMobileNavigation();
    }
    
    if (typeof renderMobileSlides === 'function') {
        renderMobileSlides();
    }
    
    if (mobEditorSwiper) {
        mobEditorSwiper.update();
    }
    
    if (typeof mobNavSwiper !== 'undefined' && mobNavSwiper) {
        mobNavSwiper.update();
    }
    
    setTimeout(() => {
        if (typeof switchToSlide === 'function') {
            switchToSlide(currentIndex - 1);
        }
        if (typeof updateButtons === 'function') {
            updateButtons();
        }
    }, 100);
    
    if (typeof hasUnsavedChanges !== 'undefined') {
        hasUnsavedChanges = true;
    }
    
    showMobileNotification('Слайд перемещен назад');
    
    if (typeof triggerAutoSave === 'function') {
        triggerAutoSave();
    }
}

// Переместить слайд вперед
function moveSlideForward() {
    closeMobileMenu();
    
    if (typeof moveSlideMobileRight === 'function') {
        moveSlideMobileRight();
        return;
    }
    
    if (typeof slides === 'undefined' || typeof mobEditorSwiper === 'undefined') {
        showMobileNotification('Ошибка перемещения', 'error');
        return;
    }
    
    const currentIndex = mobEditorSwiper.activeIndex;
    
    if (currentIndex === slides.length - 1) {
        showMobileNotification('Слайд уже последний', 'error');
        return;
    }
    
    const temp = slides[currentIndex];
    slides[currentIndex] = slides[currentIndex + 1];
    slides[currentIndex + 1] = temp;
    
    if (typeof renderMobileNavigation === 'function') {
        renderMobileNavigation();
    }
    
    if (typeof renderMobileSlides === 'function') {
        renderMobileSlides();
    }
    
    if (mobEditorSwiper) {
        mobEditorSwiper.update();
    }
    
    if (typeof mobNavSwiper !== 'undefined' && mobNavSwiper) {
        mobNavSwiper.update();
    }
    
    setTimeout(() => {
        if (typeof switchToSlide === 'function') {
            switchToSlide(currentIndex + 1);
        }
        if (typeof updateButtons === 'function') {
            updateButtons();
        }
    }, 100);
    
    if (typeof hasUnsavedChanges !== 'undefined') {
        hasUnsavedChanges = true;
    }
    
    showMobileNotification('Слайд перемещен вперед');
    
    if (typeof triggerAutoSave === 'function') {
        triggerAutoSave();
    }
}

// Переключить отображение валют
function toggleCurrencyDisplay() {
    closeMobileMenu();
    
    const checkbox = document.getElementById('showAllCurrencies');
    if (checkbox) {
        checkbox.checked = !checkbox.checked;
        
        const event = new Event('change', { bubbles: true });
        checkbox.dispatchEvent(event);
        
        const menuText = document.getElementById('currencyToggleText');
        if (menuText) {
            menuText.textContent = checkbox.checked ? 'Скрыть валюты' : 'Показывать валюты в презентации';
        }
        
        showMobileNotification(
            checkbox.checked ? 'Все валюты будут показаны' : 'Будет показана только основная валюта'
        );
        
        if (typeof hasUnsavedChanges !== 'undefined') {
            hasUnsavedChanges = true;
        }
        
        if (typeof triggerAutoSave === 'function') {
            setTimeout(() => triggerAutoSave(), 500);
        }
    }
}

// Открыть выбор цвета темы
function openThemeColorPicker() {
    closeMobileMenu();
    
    const colorPicker = document.getElementById('themeColorPicker');
    if (colorPicker) {
        colorPicker.click();
    }
}

// Очистить поля текущего слайда
function clearCurrentSlide() {
    closeMobileMenu();
    
    if (typeof slides === 'undefined' || typeof mobEditorSwiper === 'undefined') {
        showMobileNotification('Ошибка очистки', 'error');
        return;
    }
    
    if (!confirm('Вы уверены, что хотите очистить все поля слайда?')) {
        return;
    }
    
    const currentIndex = mobEditorSwiper.activeIndex;
    const currentSlide = slides[currentIndex];
    const slideType = currentSlide.type;
    
    if (typeof defaultSlides !== 'undefined' && defaultSlides[slideType]) {
        slides[currentIndex] = JSON.parse(JSON.stringify(defaultSlides[slideType]));
    } else {
        slides[currentIndex] = { type: slideType, hidden: false };
    }
    
    if (typeof renderMobileSlides === 'function') {
        renderMobileSlides();
    }
    
    if (mobEditorSwiper) {
        mobEditorSwiper.update();
        
        setTimeout(() => {
            mobEditorSwiper.slideTo(currentIndex);
            if (typeof updateNavigation === 'function') {
                updateNavigation();
            }
        }, 100);
    }
    
    if (typeof hasUnsavedChanges !== 'undefined') {
        hasUnsavedChanges = true;
    }
    
    showMobileNotification('Поля очищены');
    
    if (typeof triggerAutoSave === 'function') {
        setTimeout(() => triggerAutoSave(), 500);
    }
}

// Показать уведомление
function showMobileNotification(message, type = 'success') {
    const existing = document.querySelector('.mobile-notification');
    if (existing) {
        existing.remove();
    }
    
    const notification = document.createElement('div');
    notification.className = `mobile-notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    console.log('mobile-menu-functions.js loaded');
    console.log('defaultSlides available:', typeof defaultSlides !== 'undefined');
    console.log('slides available:', typeof slides !== 'undefined');
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeMobileMenu();
            closeAddSlideModal();
        }
    });
    
    const colorPicker = document.getElementById('themeColorPicker');
    if (colorPicker) {
        colorPicker.addEventListener('change', function() {
            document.documentElement.style.setProperty('--theme-main-color', this.value);
            showMobileNotification('Цвет темы изменен');
            
            if (typeof hasUnsavedChanges !== 'undefined') {
                hasUnsavedChanges = true;
            }
            
            if (typeof triggerAutoSave === 'function') {
                setTimeout(() => triggerAutoSave(), 500);
            }
        });
    }
});