/**
 * Интеграция с Яндекс.Картами
 * Поддерживает режимы: editor (редактор), view (просмотр), static (статичное изображение)
 */

class YandexMapsIntegration {
    constructor(apiKey, mode = 'editor') {
        this.apiKey = apiKey;
        this.mode = mode; // 'editor', 'view', 'static'
        this.map = null;
        this.placemark = null;
        this.suggestView = null;
        this.currentCoords = null;
        this.currentAddress = '';
        this.isInitialized = false;
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
            script.src = `https://api-maps.yandex.ru/2.1/?apikey=${this.apiKey}&lang=ru_RU`;
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

        // Инициализация подсказок адресов
        this.initSuggest(inputId);

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
            hintContent: hintContent || 'Местоположение'
        }, {
            iconLayout: 'default#image',
            iconImageHref: '/assets/images/map-pin.svg', // Путь к вашему кастомному пину
            iconImageSize: [40, 50],
            iconImageOffset: [-20, -50],
            draggable: this.mode === 'editor'
        });

        if (this.mode === 'editor' && this.placemark.options.get('draggable')) {
            this.placemark.events.add('dragend', () => {
                const coords = this.placemark.geometry.getCoordinates();
                const input = document.querySelector('[data-map-address]');
                if (input) {
                    this.updateLocation(coords, input);
                }
            });
        }

        this.map.geoObjects.add(this.placemark);
        this.currentCoords = coords;
    }

    /**
     * Инициализация подсказок адресов
     */
    initSuggest(inputId) {
        const input = document.getElementById(inputId);
        if (!input) return;

        this.suggestView = new ymaps.SuggestView(inputId, {
            results: 5,
            offset: [0, 5]
        });

        // Обработчик выбора адреса из подсказок
        this.suggestView.events.add('select', (e) => {
            const selectedAddress = e.get('item').value;
            input.value = selectedAddress;
            this.geocodeAddress(selectedAddress);
        });

        // Обработчик ручного ввода с задержкой
        let timeout;
        input.addEventListener('input', () => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                if (input.value.length > 3) {
                    this.geocodeAddress(input.value);
                }
            }, 1000);
        });
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
    }

    /**
     * Получение статичного изображения карты (для просмотра и PDF)
     */
    static getStaticMapUrl(coords, apiKey, size = '600,400', zoom = 15) {
        const [lat, lng] = coords;
        const marker = `${lng},${lat},pm2rdm`; // Красный пин
        
        return `https://static-maps.yandex.ru/1.x/?ll=${lng},${lat}&size=${size}&z=${zoom}&l=map&pt=${marker}&apikey=${apiKey}`;
    }

    /**
     * Рендеринг статичного изображения карты
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
        if (this.suggestView) {
            this.suggestView = null;
        }
        this.placemark = null;
        this.currentCoords = null;
        this.currentAddress = '';
    }
}

// Экспорт для использования в других скриптах
window.YandexMapsIntegration = YandexMapsIntegration;
