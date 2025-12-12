# Интеграция с Яндекс.Картами

Этот документ описывает интеграцию Яндекс.Карт в проект.

## Файлы интеграции

- **`assets/js/yandex-maps-integration.js`** - JavaScript-модуль для клиентской части
- **`includes/yandex-maps-helper.php`** - PHP-хелпер для серверной части
- **`assets/images/map-pin.svg`** - Кастомный пин для карты (необходимо создать)

## API Ключ

API ключ Яндекс.Карт: `0d3f02e3-3a2a-426d-a3a1-9952dcb199b9`

## Интеграция в режим редактора (editor.php)

### Шаг 1: Подключение файлов

В `editor-data/head.php` или `editor-data/scripts.php` добавьте:

```html
<!-- Подключение JS-модуля -->
<script src="/assets/js/yandex-maps-integration.js"></script>
```

В `editor.php` или `includes/functions.php` добавьте:

```php
<?php
require_once __DIR__ . '/includes/yandex-maps-helper.php';
```

### Шаг 2: Добавление блока местоположения в редактор

В том месте, где должен быть блок местоположения (например, в слайде редактора):

```php
<?php
$yandexMaps = getYandexMapsHelper();

// Получение сохраненных данных из базы
$savedCoords = [
    $slide['location_lat'] ?? 55.751574,  // Москва по умолчанию
    $slide['location_lng'] ?? 37.573856
];
$savedAddress = $slide['location_address'] ?? '';

echo $yandexMaps->renderEditorMap(
    'locationMap',          // ID контейнера карты
    'locationAddress',      // ID поля ввода адреса
    $savedCoords,           // Координаты [lat, lng]
    $savedAddress           // Адрес
);
?>
```

### Шаг 3: Сохранение данных в базу

Добавьте поля в таблицу слайдов (если их еще нет):

```sql
ALTER TABLE slides 
ADD COLUMN location_lat DECIMAL(10, 8) DEFAULT NULL,
ADD COLUMN location_lng DECIMAL(11, 8) DEFAULT NULL,
ADD COLUMN location_address VARCHAR(500) DEFAULT NULL;
```

В файле сохранения слайдов (API или форма):

```php
<?php
$locationAddress = $_POST['locationAddress'] ?? '';
$locationLat = $_POST['locationAddress_lat'] ?? null;
$locationLng = $_POST['locationAddress_lng'] ?? null;

// Сохранение в базу
$stmt = $pdo->prepare("
    UPDATE slides 
    SET location_address = ?, location_lat = ?, location_lng = ?
    WHERE id = ?
");
$stmt->execute([$locationAddress, $locationLat, $locationLng, $slideId]);
```

### Шаг 4: JavaScript для автосохранения

Добавьте в скрипты редактора:

```javascript
// Автосохранение при изменении местоположения
document.addEventListener('yandexMapLocationUpdate', function(e) {
    // Триггер сохранения слайда
    saveSlideData({
        location_address: e.detail.address,
        location_lat: e.detail.lat,
        location_lng: e.detail.lng
    });
});
```

## Интеграция в режим просмотра (view.php)

### Вариант 1: Интерактивная карта

```php
<?php
$yandexMaps = getYandexMapsHelper();

$coords = [$slide['location_lat'], $slide['location_lng']];
$address = $slide['location_address'];

echo $yandexMaps->renderViewMap(
    'locationMapView',     // ID контейнера
    $coords,               // Координаты [lat, lng]
    $address,              // Адрес
    false                  // false = интерактивная карта
);
?>
```

### Вариант 2: Статичное изображение

```php
<?php
$yandexMaps = getYandexMapsHelper();

$coords = [$slide['location_lat'], $slide['location_lng']];
$address = $slide['location_address'];

echo $yandexMaps->renderViewMap(
    'locationMapView',
    $coords,
    $address,
    true                   // true = статичное изображение
);
?>
```

## Интеграция в PDF

В файле генерации PDF (например, с TCPDF или mPDF):

```php
<?php
$yandexMaps = getYandexMapsHelper();

$coords = [$slide['location_lat'], $slide['location_lng']];

// Скачиваем статичную карту
$mapImagePath = __DIR__ . '/cache/maps/' . md5(implode(',', $coords)) . '.png';

if (!file_exists($mapImagePath)) {
    $yandexMaps->downloadStaticMap(
        $coords[0], 
        $coords[1], 
        $mapImagePath,
        '800,600',  // Размер для PDF
        15          // Zoom
    );
}

// Добавляем изображение в PDF
$pdf->Image($mapImagePath, 10, 50, 180, 120);

// Или для mPDF:
$html = '<img src="' . $mapImagePath . '" style="width: 100%; height: auto;" />';
$mpdf->WriteHTML($html);
```

## Стили CSS

Добавьте в ваш CSS-файл:

```css
.location-block {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin: 20px 0;
}

.location-map-container {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.location-info-container {
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.location-address {
    font-size: 16px;
    line-height: 1.6;
    color: #333;
}

.yandex-map {
    width: 100%;
    height: 400px;
    border-radius: 8px;
}

/* Мобильная версия */
@media (max-width: 768px) {
    .location-block {
        grid-template-columns: 1fr;
    }
    
    .yandex-map {
        height: 300px;
    }
}
```

## Создание кастомного пина

Создайте файл `assets/images/map-pin.svg` с вашим дизайном пина.

Пример простого SVG-пина:

```xml
<svg width="40" height="50" xmlns="http://www.w3.org/2000/svg">
    <path d="M20 0C12.268 0 6 6.268 6 14c0 10.5 14 36 14 36s14-25.5 14-36c0-7.732-6.268-14-14-14z" 
          fill="#FF4444" stroke="#FFF" stroke-width="2"/>
    <circle cx="20" cy="14" r="6" fill="#FFF"/>
</svg>
```

## Примеры использования

### Программное установление местоположения

```javascript
const yandexMaps = new YandexMapsIntegration('0d3f02e3-3a2a-426d-a3a1-9952dcb199b9', 'editor');

// Установить местоположение по адресу
await yandexMaps.init();
await yandexMaps.geocodeAddress('Москва, Красная площадь');

const coords = yandexMaps.getCurrentCoords();
const address = yandexMaps.getCurrentAddress();
```

### Получение статичной карты

```javascript
const staticMapUrl = YandexMapsIntegration.getStaticMapUrl(
    [55.751574, 37.573856], 
    '0d3f02e3-3a2a-426d-a3a1-9952dcb199b9',
    '600,400',
    15
);

// Используем URL для <img> или скачивания
```

## Устранение неполадок

### Карта не отображается

1. Проверьте, что `yandex-maps-integration.js` подключен
2. Проверьте консоль браузера на наличие ошибок
3. Убедитесь, что API ключ валиден

### Подсказки не работают

1. Убедитесь, что `initSuggest()` вызывается после инициализации API
2. Проверьте, что ID поля ввода совпадает

### Статичная карта не загружается

1. Проверьте логи PHP
2. Убедитесь, что есть права на запись в директорию cache/maps/
3. Проверьте, что `file_get_contents()` разрешен для URL

## Оптимизация

### Кеширование статичных карт

Статичные карты автоматически кешируются по координатам. Создайте директорию:

```bash
mkdir -p cache/maps
chmod 755 cache/maps
```

### Lazy Loading

Для оптимизации загрузки страницы можно использовать отложенную инициализацию:

```javascript
// Инициализировать карту только при прокрутке до нее
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            initMap();
            observer.disconnect();
        }
    });
});

observer.observe(document.getElementById('locationMap'));
```

## Дополнительные возможности

- Подсказки адресов при вводе
- Перетаскиваемый пин в режиме редактора
- Клик по карте для выбора местоположения
- Автоматическое сохранение изменений
- Статичные карты для PDF и просмотра
- Кастомные пины

## Лицензия

Использование Яндекс.Карт регулируется [Пользовательским соглашением Яндекса](https://yandex.ru/legal/maps_api/).
