# Интеграция PDFShift - ОТКЛЮЧЕНО

## Что было реализовано

Проект успешно интегрирован с PDFShift для бессерверного экспорта презентаций.

## Модифицированные файлы

### 1. **api-data/export.php** (Обновлен)
- Новая функция `convertToPDFWithPDFShift()` - основная интеграция
- Новый API endpoint: `export_pdf` - скачивание PDF
- Новая функция `generatePresentationHTML()` - экстракт HTML
- Новая функция `renderSlideContent()` - рендеринг слайдов

**Ключевые характеристики:**
- При выражении export_pdf требуется авторизация
- Контроль доступа - пользователь должен быть владельцем
- Проверка тарифа - только у платных тарифов
- Обработка ошибок с относительными статус кодами

### 2. **assets/js/pdf-export.js** (НОВЫЙ)
Полнофункциональный JavaScript модуль для работы с PDF:

```javascript
class PDFExporter {
    // - Отправка запроса к API
    // - Обработка прогресса скачивания
    // - Автоматические повторные попытки (3 по умолчанию)
    // - Визуальные индикаторы загрузки
    // - Обработка ошибок с красивыми уведомлениями
    // - Поддержка callback'ов (onSuccess, onError, onProgress)
}
```

**API:**
```javascript
// Простое использование
exportPresentationPDF(123);

// С опциями
window.pdfExporter.exportPDF(123, {
    onSuccess: () => console.log('Done'),
    onError: (err) => console.error(err),
    onProgress: (percent) => console.log(percent)
});
```

### 3. **PDFSHIFT_INTEGRATION.md** (ДОКУМЕНТАЦИЯ)
Полная документация интеграции:
- Конфигурация PDFShift
- API endpoints с примерами
- Проверки безопасности
- Обработка ошибок
- Требования и ограничения
- Решение проблем

### 4. **PDFSHIFT_EXAMPLES.md** (ПРИМЕРЫ)
Примеры использования для:
- Ванильного JavaScript
- React
- Vue.js
- Пакетного экспорта
- Отслеживания истории
- Публичных презентаций

## API Endpoints

### Generate Presentation (HTML)
```
GET /api.php?action=generate_presentation&id={id}
```
- Формирует HTML для просмотра
- Используется internally для PDF-конвертера
- Доступно для владельца, публичных и по хешу

### Export PDF
```
GET /api.php?action=export_pdf&id={id}
```
- **ТРЕБУЕТ АВТОРИЗАЦИЮ**
- Возвращает binary PDF file (application/pdf)
- Проверяет доступ пользователя
- Проверяет тариф пользователя
- Параметры PDF: A4 landscape, 0 марж, горизонтальная ориентация

## Статус Коды

| Код | Описание |
|-----|----------|
| 200 | Успешный экспорт |
| 400 | ID не указан |
| 401 | Требуется авторизация |
| 403 | Доступ запрещён или тариф не позволяет |
| 404 | Презентация не найдена |
| 500 | Ошибка при генерации PDF |

## Проверки Безопасности

✅ **Авторизация**: `isAuthenticated()` - требуется для export_pdf
✅ **Доступ**: `canAccessPresentation()` - проверка владельца
✅ **Тариф**: `canUserPrint()` - только платные тарифы
✅ **Валидация**: `filter_input()` для всех параметров
✅ **Экранирование**: `htmlspecialchars()` для вывода
✅ **Логирование**: Все события логируются

## Параметры PDF

```php
'options' => [
    'page_size' => 'A4',           // Размер страницы
    'page_width' => '297mm',       // Ширина (landscape)
    'page_height' => '210mm',      // Высота (landscape)
    'margin_top' => 0,             // Без полей
    'margin_bottom' => 0,
    'margin_left' => 0,
    'margin_right' => 0
]
```

## Расшифровка Слайдов

Поддерживаемые типы слайдов:
- ✅ `cover` - Обложка с ценой
- ✅ `image` - Полноразмерное изображение
- ✅ `gallery` - Галерея 3x1
- ✅ `grid` - Сетка 2x2
- ✅ `characteristics` - Характеристики с таблицей
- ✅ `description` - Описание с изображениями
- ✅ `infrastructure` - Инфраструктура
- ✅ `features` - Особенности
- ✅ `location` - Местоположение
- ✅ `contacts` - Контактная информация

## Возможные Ошибки и Решения

### 401 Unauthorized
**Причина**: Пользователь не авторизован
**Решение**: Перейти на страницу входа

### 403 Forbidden (Тариф)
**Причина**: Бесплатный тариф не позволяет экспорт
**Решение**: Предложить обновить тариф на `/tariffs.php`

### 403 Forbidden (Доступ)
**Причина**: Пользователь не владелец презентации
**Решение**: Убедиться, что это его презентация

### 500 PDFShift Error
**Причина**: Проблема с API или HTML-конвертером
**Решение**: Проверить логи в `/logs/`

## Использование

### Минимальный пример

```html
<!-- HTML -->
<script src="/assets/js/pdf-export.js"></script>
<button onclick="exportPresentationPDF(123)">PDF</button>
```

### С обработкой ошибок

```javascript
exportPresentationPDF(123)
    .catch(err => {
        if (err.message.includes('Требуется')) {
            window.location.href = '/auth/login.php';
        } else if (err.message.includes('тариф')) {
            window.location.href = '/tariffs.php';
        } else {
            alert('Ошибка: ' + err.message);
        }
    });
```

## Тестирование

### С помощью cURL

```bash
# Получить HTML
curl "http://localhost/api.php?action=generate_presentation&id=1"

# Скачать PDF (требуется cookie сессии)
curl -b "PHPSESSID=your_session" \
     "http://localhost/api.php?action=export_pdf&id=1" \
     -o presentation.pdf
```

### С помощью JavaScript

```javascript
// Тест в консоли
fetch('/api.php?action=export_pdf&id=1', {
    credentials: 'same-origin'
})
.then(r => r.blob())
.then(blob => {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'test.pdf';
    a.click();
});
```

## Лимиты

- **PDFShift**: 50 конверсий/месяц (бесплатно)
- **Таймаут**: 60 секунд на конверсию
- **Размер**: 5MB по умолчанию (платные планы - неограниченно)
- **Поток**: 1 конверсия в секунду рекомендуется

## Мониторинг

### Логи
```php
error_log("API Request: $action | User: " . getCurrentUserId());
error_log("PDF Export Error: " . $e->getMessage());
```

### Метрики для отслеживания
- Количество экспортов в день
- Средний размер PDF
- Количество ошибок по типам
- Время генерации

## Рекомендации

1. **Кэширование**: Рассмотреть кэширование одинаковых презентаций
2. **Асинхронность**: Использовать очередь для большого количества экспортов
3. **Уведомления**: Добавить email-уведомления при завершении
4. **История**: Сохранять историю экспортов для аналитики
5. **Резервная копия**: Хранить экспортированные PDF для повторного скачивания

## Поддержка

Документация PDFShift: https://pdfshift.io/docs
API Reference: https://api.pdfshift.io/v3/

## История Изменений

**v1.0** (10.12.2025)
- ✅ Интеграция PDFShift API
- ✅ Серверный экспорт PDF
- ✅ Проверки безопасности
- ✅ JavaScript модуль
- ✅ Документация и примеры

## Статус: ✅ ГОТОВО К ИСПОЛЬЗОВАНИЮ

Все компоненты интегрированы, протестированы и готовы к использованию в production.
