# PDFShift Integration - Примеры использования

## 1. Базовое использование

### HTML Кнопка экспорта

```html
<!-- Подключите модуль экспорта -->
<script src="/assets/js/pdf-export.js"></script>

<!-- Кнопка экспорта PDF -->
<button onclick="exportPresentationPDF(123)" class="btn btn-primary">
    <i class="fas fa-file-pdf"></i> Скачать PDF
</button>
```

### JavaScript вызов

```javascript
// Простой экспорт
exportPresentationPDF(presentationId);

// С обработкой коллбеков
window.pdfExporter.exportPDF(presentationId, {
    showLoading: true,
    onSuccess: () => {
        console.log('Послеовно та PDF редь!');
    },
    onError: (error) => {
        console.error('Ошибка:', error.message);
    }
});
```

## 2. Отражение прогресса скачивания

### HTML с прогрессбаром

```html
<div id="pdf-export-container">
    <button onclick="exportWithProgress(123)" class="btn">
        Скачать PDF
    </button>
    <div class="progress" style="display: none;">
        <div class="progress-bar" id="pdf-progress" style="width: 0%"></div>
    </div>
</div>
```

### JavaScript с отслеживанием прогресса

```javascript
function exportWithProgress(presentationId) {
    const progressContainer = document.querySelector('.progress');
    const progressBar = document.getElementById('pdf-progress');
    
    progressContainer.style.display = 'block';
    progressBar.style.width = '0%';
    
    window.pdfExporter.exportPDF(presentationId, {
        onProgress: (progress) => {
            progressBar.style.width = progress + '%';
        },
        onSuccess: () => {
            progressBar.style.width = '100%';
            setTimeout(() => {
                progressContainer.style.display = 'none';
            }, 1000);
        },
        onError: (error) => {
            progressContainer.style.display = 'none';
            alert('Ошибка: ' + error.message);
        }
    });
}
```

## 3. Интеграция в React

```jsx
import React, { useState } from 'react';

const PDFExportButton = ({ presentationId }) => {
    const [loading, setLoading] = useState(false);
    const [progress, setProgress] = useState(0);
    const [error, setError] = useState(null);

    const handleExport = async () => {
        setLoading(true);
        setError(null);
        
        try {
            const response = await fetch(
                `/api.php?action=export_pdf&id=${presentationId}`,
                { credentials: 'same-origin' }
            );

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message);
            }

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `presentation_${presentationId}.pdf`;
            link.click();
            window.URL.revokeObjectURL(url);

        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
            setProgress(0);
        }
    };

    return (
        <div>
            <button 
                onClick={handleExport} 
                disabled={loading}
                className="btn btn-primary"
            >
                {loading ? 'Генерирование...' : 'Скачать PDF'}
            </button>
            {error && <div className="alert alert-error">{error}</div>}
        </div>
    );
};

export default PDFExportButton;
```

## 4. Интеграция в Vue

```vue
<template>
    <div>
        <button 
            @click="exportPDF" 
            :disabled="loading"
            class="btn btn-primary"
        >
            <i class="fas fa-file-pdf"></i>
            {{ loading ? 'Генерирование...' : 'Скачать PDF' }}
        </button>
        
        <div v-if="error" class="alert alert-error">
            {{ error }}
        </div>
        
        <div v-if="progress > 0" class="progress">
            <div class="progress-bar" :style="{ width: progress + '%' }"></div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        presentationId: {
            type: Number,
            required: true
        }
    },
    data() {
        return {
            loading: false,
            progress: 0,
            error: null
        };
    },
    methods: {
        async exportPDF() {
            this.loading = true;
            this.error = null;
            
            try {
                const response = await fetch(
                    `/api.php?action=export_pdf&id=${this.presentationId}`,
                    { credentials: 'same-origin' }
                );

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message);
                }

                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `presentation_${this.presentationId}.pdf`;
                link.click();
                window.URL.revokeObjectURL(url);

            } catch (err) {
                this.error = err.message;
            } finally {
                this.loading = false;
                this.progress = 0;
            }
        }
    }
};
</script>
```

## 5. Множественные экспорты

```javascript
class BatchPDFExporter {
    constructor(options = {}) {
        this.concurrency = options.concurrency || 2;
        this.delay = options.delay || 1000;
    }

    async exportMultiple(presentationIds) {
        const results = [];
        let currentIndex = 0;

        const exportNext = async () => {
            while (currentIndex < presentationIds.length) {
                const id = presentationIds[currentIndex++];
                try {
                    await window.pdfExporter.exportPDF(id);
                    results.push({ id, success: true });
                } catch (error) {
                    results.push({ id, success: false, error: error.message });
                }
                await new Promise(resolve => setTimeout(resolve, this.delay));
            }
        };

        // рун конкурентных экспортов
        const workers = [];
        for (let i = 0; i < this.concurrency; i++) {
            workers.push(exportNext());
        }

        await Promise.all(workers);
        return results;
    }
}

// Использование
const batchExporter = new BatchPDFExporter({ concurrency: 3 });
const results = await batchExporter.exportMultiple([1, 2, 3, 4, 5]);
console.log(results);
```

## 6. Обработка ошибок

```javascript
window.pdfExporter.exportPDF(presentationId, {
    onError: (error) => {
        // При неавторизации
        if (error.message.includes('Требуется')) {
            window.location.href = '/auth/login.php';
            return;
        }

        // При острыо тарифа
        if (error.message.includes('тариф')) {
            window.location.href = '/tariffs.php';
            return;
        }

        // По умолчанию - вывести ошибку
        console.error('Ошибка экспорта PDF:', error);
    }
});
```

## 7. Проверка возможности экспорта

### PHP проверка

```php
<?php
// При загружении страницы презентации

require_once __DIR__ . '/api-data/export.php';

$userId = getCurrentUserId();
$canExportPDF = canUserPrint($userId);

?>

<script>
    window.canExportPDF = <?php echo json_encode($canExportPDF); ?>;
</script>
```

### JavaScript денаблинг

```javascript
const exportButton = document.querySelector('[data-export-pdf]');

if (!window.canExportPDF) {
    exportButton.disabled = true;
    exportButton.title = 'Обновите тариф для экспорта PDF';
}
```

## 8. Кустомные параметры

```javascript
// Модифицируем объект экспортера
const exporter = new PDFExporter({
    timeout: 120000,     // 2 минуты
    retries: 5,          // Пытки в случае ошибки
    retryDelay: 2000     // 2 секунд между пытками
});

exporter.exportPDF(123);
```

## 9. Отслеживание экспортов

```javascript
// Логируем все экспорты
const exportHistory = [];

function trackExport(presentationId) {
    exportHistory.push({
        id: presentationId,
        timestamp: new Date(),
        status: 'pending'
    });

    window.pdfExporter.exportPDF(presentationId, {
        onSuccess: () => {
            const record = exportHistory.find(r => r.id === presentationId);
            if (record) record.status = 'success';
            
            // Отправляем аналитику
            fetch('/api.php?action=log_export', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    presentation_id: presentationId,
                    exported_at: new Date().toISOString()
                })
            });
        },
        onError: (error) => {
            const record = exportHistory.find(r => r.id === presentationId);
            if (record) {
                record.status = 'error';
                record.error = error.message;
            }
        }
    });
}
```

## 10. Доступ к публичным презентациям

### Маркап для публичной страницы

```html
<!-- Публичная презентация -->
<button onclick="downloadPublicPDF()" class="btn btn-primary">
    Скачать PDF
</button>

<script>
    // Для публичных презентаций не требуется авторизация
    // По умолчанию экспорт таких презентаций денайблен
</script>
```

## Необходимые поновки в HTML

```html
<!-- В начало body -->
<script src="/assets/js/pdf-export.js"></script>

<!-- Font Awesome для иконок -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
```

## Оръзательно

ФУНКЦиональность экспорта PDF:

1. ✅ Генерируется на сервере (без диалога печати)
2. ✅ Охраняет горизонтальную ориентацию
3. ✅ Нет разрывов страниц
4. ✅ Требует авторизацию (для не публичных)
5. ✅ Проверяет тариф пользователя
