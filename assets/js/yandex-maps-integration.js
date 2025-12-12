/**
 * Интеграция с Яндекс.Картами
 * Поддерживает режимы: editor (редактор), view (просмотр), static (статическое изображение)
 */

class YandexMapsIntegration {
    constructor(apiKey, mode = 'editor') {
        this.apiKey = apiKey;
        this.mode = mode; // 'editor', 'view', 'static'
        this.map = null;
        this.placemark = null;
        this.currentCoords = null;
        this.currentAddress = '';
        this.isInitialized = false;
        this.suggestTimeout = null;
    }

    /**
     * Инициализация API Яндекс.Карт
     */
    async init() {
        if (this.isInitialized) return;
        
        return new Promise((resolve, reject) => {
            if (window.ymaps) {
                this.isInitialized = true;
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = `https://api-maps.yandex.ru/2.1/?apikey=${this.apiKey}&lang=ru_RU&suggest_apikey=${this.apiKey}`;
            script.onload = () => {
                ymaps.ready(() => {
                    this.isInitialized = true;
                    resolve();
                });
            };
            script.onerror = () => reject(new Error('Не удалось загрузить Яндекс.Карты'));
            document.head.appendChild(script);
        });
    }

    /**
     * Создание интерактивной карты (режим редактора)
     */
    async initEditorMap(containerId, inputId, coords = [55.751574, 37.573856]) {
        await this.init();
        
        const container = document.getElementById(containerId);
        const input = document.getElementById(inputId);
        
        if (!container || !input) {
            console.error('Контейнер карты или поле ввода не найдены');
            return;
        }

        // Создание карты
        this.map = new ymaps.Map(containerId, {
            center: coords,
            zoom: 15,
            controls: ['zoomControl', 'searchControl']
        });

        // Создание кастомного пина
        this.createPlacemark(coords);

        // Инициализация подсказок адресов (новый метод без SuggestView)
        this.initModernSuggest(input);

        // Обработчик клика по карте
        this.map.events.add('click', (e) => {
            const coords = e.get('coords');
            this.updateLocation(coords, input);
        });

        // Восстановление сохраненного адреса
        if (input.value) {
            this.geocodeAddress(input.value);
        }

        return this.map;
    }

    /**
     * Создание карты для просмотра (только чтение)
     */
    async initViewMap(containerId, coords, address = '') {
        await this.init();
        
        const container = document.getElementById(containerId);
        if (!container) {
            console.error('Контейнер карты не найден');
            return;
        }

        this.map = new ymaps.Map(containerId, {
            center: coords,
            zoom: 15,
            controls: ['zoomControl']
        }, {
            suppressMapOpenBlock: true
        });

        // Создание пина
        this.createPlacemark(coords, address);

        // Отключение взаимодействия
        this.map.behaviors.disable(['drag', 'scrollZoom', 'dblClickZoom', 'multiTouch']);

        return this.map;
    }

    /**
     * Создание кастомного пина
     */
    createPlacemark(coords, hintContent = '') {
        if (this.placemark) {
            this.map.geoObjects.remove(this.placemark);
        }

        this.placemark = new ymaps.Placemark(coords, {
            hintContent: hintContent || 'Местоположение',
            balloonContent: hintContent
        }, {
            preset: 'islands#redDotIcon',
            draggable: this.mode === 'editor'
        });

        if (this.mode === 'editor' && this.placemark.options.get('draggable')) {
            this.placemark.events.add('dragend', () => {
                const coords = this.placemark.geometry.getCoordinates();
                const input = document.querySelector('[data-map-address]') || 
                             document.querySelector('.location-address-input');
                if (input) {
                    this.updateLocation(coords, input);
                }
            });
        }

        this.map.geoObjects.add(this.placemark);
        this.currentCoords = coords;
    }

    /**
     * Современная инициализация подсказок адресов (без устаревшего SuggestView)
     */
    initModernSuggest(input) {
        if (!input) return;

        // Создаем контейнер для подсказок
        let suggestContainer = document.getElementById('yandex-suggest-container');
        if (!suggestContainer) {
            suggestContainer = document.createElement('div');
            suggestContainer.id = 'yandex-suggest-container';
            suggestContainer.className = 'yandex-suggest-container';
            suggestContainer.style.cssText = `
                position: absolute;
                z-index: 10000;
                background: white;
                border: 1px solid #ddd;
                border-radius: 4px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                max-height: 300px;
                overflow-y: auto;
                display: none;
                min-width: 300px;
            `;
            document.body.appendChild(suggestContainer);
        }

        // Обработчик ввода с задержкой
        input.addEventListener('input', () => {
            clearTimeout(this.suggestTimeout);
            const query = input.value.trim();
            
            if (query.length < 3) {
                suggestContainer.style.display = 'none';
                return;
            }

            this.suggestTimeout = setTimeout(async () => {
                try {
                    const suggestions = await this.getSuggestions(query);
                    this.renderSuggestions(suggestions, input, suggestContainer);
                } catch (error) {
                    console.error('Ошибка получения подсказок:', error);
                }
            }, 500);
        });

        // Закрытие подсказок при клике вне
        document.addEventListener('click', (e) => {
            if (e.target !== input && !suggestContainer.contains(e.target)) {
                suggestContainer.style.display = 'none';
            }
        });

        // Позиционирование подсказок
        input.addEventListener('focus', () => {
            const rect = input.getBoundingClientRect();
            suggestContainer.style.left = rect.left + 'px';
            suggestContainer.style.top = (rect.bottom + 5) + 'px';
            suggestContainer.style.width = rect.width + 'px';
        });
    }

    /**
     * Получение подсказок адресов через API геокодирования
     */
    async getSuggestions(query) {
        try {
            const result = await ymaps.geocode(query, {
                results: 5
            });
            
            const suggestions = [];
            
            // Правильный способ обхода коллекции GeoObjectCollection
            result.geoObjects.each((geoObject) => {
                suggestions.push({
                    displayName: geoObject.getAddressLine(),
                    coords: geoObject.geometry.getCoordinates()
                });
            });
            
            console.log('Найдено подсказок:', suggestions.length);
            return suggestions;
        } catch (error) {
            console.error('Ошибка геокодирования:', error);
            return [];
        }
    }


    /**
     * Отрисовка подсказок
     */
    renderSuggestions(suggestions, input, container) {
        if (suggestions.length === 0) {
            container.innerHTML = '<div style="padding: 10px 15px; color: #999; text-align: center;">Адреса не найдены</div>';
            container.style.display = 'block';
            setTimeout(() => {
                container.style.display = 'none';
            }, 2000);
            return;
        }

        container.innerHTML = '';
        suggestions.forEach(item => {
            const div = document.createElement('div');
            div.className = 'suggest-item';
            div.textContent = item.displayName;
            div.style.cssText = `
                padding: 10px 15px;
                cursor: pointer;
                border-bottom: 1px solid #f0f0f0;
                transition: background 0.2s;
                font-size: 14px;
            `;
            
            div.addEventListener('mouseenter', () => {
                div.style.background = '#f5f5f5';
            });
            
            div.addEventListener('mouseleave', () => {
                div.style.background = 'white';
            });
            
            div.addEventListener('click', () => {
                input.value = item.displayName;
                container.style.display = 'none';
                this.updateLocationByCoords(item.coords, item.displayName);
            });
            
            container.appendChild(div);
        });

        container.style.display = 'block';
    }

    /**
     * Геокодирование адреса (адрес -> координаты)
     */
    async geocodeAddress(address) {
        if (!address) return;

        try {
            const result = await ymaps.geocode(address, { results: 1 });
            const firstGeoObject = result.geoObjects.get(0);
            
            if (firstGeoObject) {
                const coords = firstGeoObject.geometry.getCoordinates();
                const fullAddress = firstGeoObject.getAddressLine();
                
                this.currentCoords = coords;
                this.currentAddress = fullAddress;
                
                if (this.map) {
                    this.map.setCenter(coords, 15);
                    this.createPlacemark(coords, fullAddress);
                }

                // Триггер события для сохранения
                this.dispatchLocationUpdate(coords, fullAddress);
            }
        } catch (error) {
            console.error('Ошибка геокодирования:', error);
        }
    }

    /**
     * Обратное геокодирование (координаты -> адрес)
     */
    async reverseGeocode(coords) {
        try {
            const result = await ymaps.geocode(coords, { results: 1 });
            const firstGeoObject = result.geoObjects.get(0);
            
            if (firstGeoObject) {
                return firstGeoObject.getAddressLine();
            }
        } catch (error) {
            console.error('Ошибка обратного геокодирования:', error);
        }
        return '';
    }

    /**
     * Обновление местоположения при клике по карте или перетаскивании пина
     */
    async updateLocation(coords, input) {
        const address = await this.reverseGeocode(coords);
        
        if (input) {
            input.value = address;
        }
        
        this.currentCoords = coords;
        this.currentAddress = address;
        this.createPlacemark(coords, address);
        
        // Триггер события для сохранения
        this.dispatchLocationUpdate(coords, address);
    }

    /**
     * Обновление по координатам (из подсказок)
     */
    updateLocationByCoords(coords, address) {
        this.currentCoords = coords;
        this.currentAddress = address;
        
        if (this.map) {
            this.map.setCenter(coords, 15);
            this.createPlacemark(coords, address);
        }
        
        this.dispatchLocationUpdate(coords, address);
    }

    /**
     * Отправка события об обновлении местоположения
     */
    dispatchLocationUpdate(coords, address) {
        const event = new CustomEvent('yandexMapLocationUpdate', {
            detail: {
                coords: coords,
                lat: coords[0],
                lng: coords[1],
                address: address
            }
        });
        document.dispatchEvent(event);
        console.log('Location updated:', { coords, address });
    }

    /**
     * Получение статического изображения карты (для просмотра и PDF)
     */
    static getStaticMapUrl(coords, apiKey, size = '600,400', zoom = 15) {
        const [lat, lng] = coords;
        const marker = `${lng},${lat},pm2rdm`; // Красный пин
        
        return `https://static-maps.yandex.ru/1.x/?ll=${lng},${lat}&size=${size}&z=${zoom}&l=map&pt=${marker}&apikey=${apiKey}`;
    }

    /**
     * Рендеринг статического изображения карты
     */
    static renderStaticMap(containerId, coords, apiKey, size = '600,400') {
        const container = document.getElementById(containerId);
        if (!container) return;

        const url = this.getStaticMapUrl(coords, apiKey, size);
        const img = document.createElement('img');
        img.src = url;
        img.alt = 'Карта местоположения';
        img.style.width = '100%';
        img.style.height = 'auto';
        img.style.borderRadius = '8px';
        
        container.innerHTML = '';
        container.appendChild(img);
    }

    /**
     * Получение текущих координат
     */
    getCurrentCoords() {
        return this.currentCoords;
    }

    /**
     * Получение текущего адреса
     */
    getCurrentAddress() {
        return this.currentAddress;
    }

    /**
     * Уничтожение карты
     */
    destroy() {
        if (this.map) {
            this.map.destroy();
            this.map = null;
        }
        this.placemark = null;
        this.currentCoords = null;
        this.currentAddress = '';
        
        // Удаляем контейнер подсказок
        const suggestContainer = document.getElementById('yandex-suggest-container');
        if (suggestContainer) {
            suggestContainer.remove();
        }
    }
}

// Экспорт для использования в других скриптах
window.YandexMapsIntegration = YandexMapsIntegration;