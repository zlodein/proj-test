# PDFShift Integration Documentation

## Обзор

Проект интегрирован с сервисом [PDFShift](https://pdfshift.io/) для экспорта презентаций в PDF на серверной стороне. Это позволяет:

✅ Генерировать PDF на сервере (без диалога печати браузера)
✅ Обеспечить полный контроль над форматом и параметрами
✅ Сохранить горизонтальную ориентацию без разрывов страниц
✅ Защитить функционал через проверку авторизации и тарифов

## Конфигурация

### API Ключ PDFShift

API ключ хранится в `api-data/export.php`:

```php
const PDFSHIFT_API_KEY = 'sk_e0c5392da71907de6f292079d650be00a0d55caf';
const PDFSHIFT_API_URL = 'https://api.pdfshift.io/v3/convert/html';
```

### Параметры PDF

```php
$payload = [
    'html' => $htmlContent,
    'landscape' => true,  // Горизонтальная ориентация
    'options' => [
        'page_size' => 'A4',
        'page_width' => '297mm',
        'page_height' => '210mm',
        'margin_top' => 0,
        'margin_bottom' => 0,
        'margin_left' => 0,
        'margin_right' => 0
    ]
];
```

## API Endpoints

### 1. Просмотр HTML презентации

```
GET /api.php?action=generate_presentation&id={id}
```

**Описание:** Генерирует HTML версию презентации для просмотра

**Параметры:**
- `id` (int, required) - ID презентации
- `hash` (string, optional) - Публичный хеш для публичных презентаций

**Ответ:** HTML страница с презентацией

**Проверки доступа:**
- Владелец презентации
- Публичная презентация
- Публичный доступ по хешу

### 2. Экспорт в PDF

```
GET /api.php?action=export_pdf&id={id}
```

**Описание:** Экспортирует презентацию в PDF через PDFShift

**Параметры:**
- `id` (int, required) - ID презентации

**Ответ:** PDF файл (application/pdf)

**Требования:**
- ✅ Пользователь должен быть авторизован
- ✅ Пользователь должен быть владельцем презентации
- ✅ Пользователь должен иметь тариф, позволяющий печать

**Ошибки:**
- `401` - Требуется авторизация
- `403` - Доступ запрещён или тариф не позволяет экспортировать
- `404` - Презентация не найдена
- `500` - Ошибка при генерации PDF

## Интеграция в фронтенд

### Кнопка экспорта PDF

```html
<button onclick="exportPresentationPDF(presentationId)">
    <i class="fas fa-file-pdf"></i> Скачать PDF
</button>
```

### JavaScript функция

```javascript
function exportPresentationPDF(id) {
    // Показываем индикатор загрузки
    const loadingIndicator = showLoadingIndicator('Генерирование PDF...');
    
    fetch(`/api.php?action=export_pdf&id=${id}`)
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.message || 'Ошибка при экспорте');
                });
            }
            return response.blob();
        })
        .then(blob => {
            // Скачиваем PDF
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `presentation_${id}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            a.remove();
            
            hideLoadingIndicator(loadingIndicator);
        })
        .catch(error => {
            hideLoadingIndicator(loadingIndicator);
            showError(error.message);
        });
}
```

## Проверки безопасности

### 1. Авторизация

```php
if (!isAuthenticated()) {
    jsonResponse(['error' => 'Требуется авторизация'], 401);
}
```

### 2. Доступ к презентации

```php
if (!canAccessPresentation($id, $userId)) {
    jsonResponse(['error' => 'Доступ запрещён'], 403);
}
```

### 3. Проверка тарифа

```php
if (!canUserPrint($userId)) {
    jsonResponse([
        'error' => 'Экспорт недоступен',
        'message' => 'Ваш тариф не позволяет экспортировать в PDF. Обновите тариф.'
    ], 403);
}
```

## Обработка ошибок

### PDFShift ошибки

Если PDFShift вернёт ошибку (HTTP код != 200):

```php
if ($httpCode !== 200) {
    $responseData = json_decode($response, true);
    error_log("PDFShift Error [$httpCode]: " . json_encode($responseData));
    throw new Exception("PDFShift API Error: " . ($responseData['message'] ?? 'Unknown error'));
}
```

### Общие ошибки

```php
try {
    // Генерирование PDF
    $pdfContent = convertToPDFWithPDFShift($htmlContent, $presentation['title']);
} catch (Exception $e) {
    error_log("PDF Export Error: " . $e->getMessage());
    jsonResponse([
        'error' => 'Ошибка при генерации PDF',
        'message' => APP_ENV === 'development' ? $e->getMessage() : 'Попробуйте позже'
    ], 500);
}
```

## Структура HTML для PDF

HTML должна быть оптимизирована для PDFShift:

```html
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Презентация</title>
    <style>
        /* Гарантируем горизонтальную ориентацию */
        .js-preview-page { 
            width: 297mm; 
            height: 210mm; 
            page-break-after: always;
        }
    </style>
</head>
<body>
    <!-- Слайды -->
</body>
</html>
```

## Требования

### Серверные

- PHP >= 7.4
- cURL включён
- Доступ в интернет к api.pdfshift.io

### Клиентские

- Современный браузер (Chrome, Firefox, Safari, Edge)
- JavaScript включён
- Авторизация пользователя
- Активный тариф с поддержкой печати

## Мониторинг

### Логирование

Ошибки логируются в `logs/` папку:

```php
error_log("PDF Export Error: " . $e->getMessage());
```

### Отслеживание использования

Каждый запрос логируется в `api.php`:

```php
error_log("API Request: $action | Method: $method | User: " . (getCurrentUserId() ?? 'guest'));
```

## Лимиты и ограничения

### PDFShift

- Бесплатный план: 50 конверсий/месяц
- Максимум файла: 5MB по умолчанию, неограниченно в платных планах
- Таймаут: 30 секунд

### Приложение

- Горизонтальная ориентация: Фиксирована (A4 landscape)
- Без разрывов страниц: Все слайды в одном PDF
- Маржи: 0мм со всех сторон

## Примеры использования

### Простой экспорт

```bash
# Скачать PDF
curl -H "Cookie: PHPSESSID=your_session" \
  "http://localhost/api.php?action=export_pdf&id=123" \
  -o presentation.pdf
```

### С обработкой ошибок

```javascript
async function exportPDF(id) {
    try {
        const response = await fetch(`/api.php?action=export_pdf&id=${id}`);
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message);
        }
        
        const blob = await response.blob();
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `presentation_${id}.pdf`;
        link.click();
        URL.revokeObjectURL(url);
        
    } catch (error) {
        console.error('Export failed:', error.message);
        alert(`Ошибка: ${error.message}`);
    }
}
```

## Решение проблем

### PDF пуст или содержит только текст

**Причина:** Изображения не загружаются из-за CORS или неверных URL

**Решение:** Используйте абсолютные URL для изображений

```html
<!-- ❌ Неправильно -->
<img src="/uploads/image.jpg">

<!-- ✅ Правильно -->
<img src="https://example.com/uploads/image.jpg">
```

### "Требуется авторизация"

**Причина:** Сессия истекла или пользователь не авторизован

**Решение:** Переавторизуйтесь и повторите попытку

### "Тариф не позволяет экспортировать"

**Причина:** Пользователь имеет бесплатный тариф

**Решение:** Обновите тариф на платный план

### Ошибка 500: "PDFShift API Error"

**Причина:** Проблема с API ключом или HTML содержимым

**Решение:** 
1. Проверьте, верный ли API ключ в `api-data/export.php`
2. Убедитесь, что HTML валидна
3. Проверьте логи ошибок в `logs/`

## Будущие улучшения

- [ ] Кэширование PDF для одинаковых презентаций
- [ ] Пакетный экспорт нескольких презентаций
- [ ] Асинхронный экспорт с нотификациями
- [ ] Экспорт в другие форматы (PNG, SVG, DOCX)
- [ ] Кастомизация параметров PDF

## Поддержка

Для вопросов и проблем:

1. Проверьте документацию PDFShift: https://pdfshift.io/docs
2. Посмотрите логи в `logs/error.log`
3. Создайте issue в репозитории

## Лицензия

MIT
