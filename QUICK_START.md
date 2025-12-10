# Быстрый Начало - PDFShift Integration

## Что добавлено

### Файлы

| Файл | Описание |
|------|----------|
| `api-data/export.php` | Обновленно для PDFShift |
| `assets/js/pdf-export.js` | НОВО: JavaScript модуль |
| `PDFSHIFT_INTEGRATION.md` | Полная документация |
| `PDFSHIFT_EXAMPLES.md` | 10 примеров использования |
| `IMPLEMENTATION_SUMMARY.md` | ОТКЛЮЧЕНО и резюме |

## Краткие шаги

### 1. Основная Онстройка Готова ✓

PDFShift API ключ уже интегрирован:
```php
const PDFSHIFT_API_KEY = 'sk_e0c5392da71907de6f292079d650be00a0d55caf';
```

### 2. Добавить Кнопку и Скрипты

```html
<!-- В ваше HTML-темплейте -->

<!-- Подключите js модуль -->
<script src="/assets/js/pdf-export.js"></script>

<!-- Кнопка -->
<button onclick="exportPresentationPDF(presentationId)" class="btn btn-primary">
    <i class="fas fa-file-pdf"></i> Скачать PDF
</button>
```

### 3. Ответы АПИ

**Успех (200)**
```
PDF binary file -> автоматически скачиваются
```

**Ошибки**
```json
{
  "error": "Error message",
  "message": "Detailed message"
}
```

## Коды Ошибок

| Код | Ответ |
|-----|--------|
| 200 | Ок |
| 400 | ID не требуется |
| 401 | Не авторизован |
| 403 | Доступ запрещён или тариф |
| 404 | През не найдена |
| 500 | PDFShift ошибка |

## Простое Тестирование

### Запустить в JavaScript консоли

```javascript
// Тест экспорта
exportPresentationPDF(1);

// Тест с обработкой ошибок
window.pdfExporter.exportPDF(1, {
    onSuccess: () => console.log('ПРОКБ!'),
    onError: (e) => console.error("ОШО!", e.message)
});
```

### cURL нля Отключи REST-клиента

```bash
# 1. Найдите сессию
curl -c cookies.txt -d "email=user@test.com&password=pass" http://localhost/auth/login.php

# 2. Экспортируйте PDF
curl -b cookies.txt http://localhost/api.php?action=export_pdf&id=1 -o result.pdf
```

## Частые Проблемы

### Ошибка: "Requires authentication"

**Причина**: Не авторизован

**Решение**: Необходима авторизация. Отредирект на `/auth/login.php`

```javascript
if (error.message.includes('Требуется')) {
    window.location.href = '/auth/login.php';
}
```

### Ошибка: "Tariff doesn't allow export"

**Причина**: Бесплатный тариф

**Решение**: Директ на расчетные

```javascript
if (error.message.includes('тариф')) {
    window.location.href = '/tariffs.php';
}
```

### Ошибка: "Access denied"

**Причина**: Не владелец презентации

**Решение**: Проверьте права доступа

## Конфигурация

### API Ключ PDFShift

Внутри `api-data/export.php`:

```php
const PDFSHIFT_API_KEY = 'sk_e0c5392da71907de6f292079d650be00a0d55caf';
const PDFSHIFT_API_URL = 'https://api.pdfshift.io/v3/convert/html';
```

### Настройки PDF

```php
'options' => [
    'page_size' => 'A4',           // A4
    'page_width' => '297mm',       // Горизонталь
    'page_height' => '210mm',      // Landscape
    'margin_top' => 0,             // Но марж
    'margin_bottom' => 0,
    'margin_left' => 0,
    'margin_right' => 0
]
```

## Пример Минимального Кода

### HTML

```html
<!DOCTYPE html>
<html>
<head>
    <title>Презентация</title>
</head>
<body>
    <!-- На прегнания -->
    <button onclick="exportPresentationPDF(123)" class="btn">
        <i class="fas fa-file-pdf"></i> PDF
    </button>

    <!-- Подконнектите либрарию -->
    <script src="/assets/js/pdf-export.js"></script>
</body>
</html>
```

### JavaScript

```javascript
// Экспортируем в PDF
exportPresentationPDF(presentationId);
```

## Понимание Квоты

### Бесплатные Пользователи (Бесплатные Тариф)
- ❌ НО Остановки PDF Export

### Платные Пользователи
- ✅ Да: Полный Остановка PDF Export

### PDFShift API
- Бесплатные: 50 конверсий/месяц
- Платные: Как где скавнють

## Наркова Проверка

### 1. Авторизация
```php
requireAuth(); // На API export_pdf
```

### 2. Доступ
```php
canAccessPresentation($id, $userId) // Морая быть владелецом
```

### 3. Тариф
```php
canUserPrint($userId) // только платные
```

## Пюязская Жидкость

### ГрафикСнема

Ты рендерируешь и до ссат: исользования все модели (актуальная, нагрузка, остановтля)

User Login
   ↓

Request PDF
   ↓ 

Check Auth
   ↓

Check Access
   ↓

Check Tariff
   ↓

Generate HTML
   ↓

Send to PDFShift
   ↓

Get PDF
   ↓

Download

## Наследование Слайдов

Наследование типов слайдов для PDF:

- ✅ `cover` - Обложка с ценой
- ✅ `image` - Полные изображение
- ✅ `gallery` - Галерея
- ✅ `grid` - Нестки
- ✅ `characteristics` - Таблица хар
- ✅ `description` - Описание
- ✅ `infrastructure` - Инфраструктура
- ✅ `features` - Особенности
- ✅ `location` - Местоположение
- ✅ `contacts` - Контакты

## НАВИГАЦИЯ

### Нарэдованные Выдео

1. **Что самаян есть**: `IMPLEMENTATION_SUMMARY.md`
2. **Как использовать**: `QUICK_START.md` (this file)
3. **Глубокая док**: `PDFSHIFT_INTEGRATION.md`
4. **Контенты**: `PDFSHIFT_EXAMPLES.md`

## Ударяые

Для дополнительной на своего PDFShift:

https://pdfshift.io/docs
