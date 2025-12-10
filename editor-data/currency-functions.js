// Функции для работы с валютами

// Конвертация валют
async function convertPrice(slideIndex, fromCurrency, toCurrency) {
    const slide = slides[slideIndex];
    const amount = slide.price_value || 0;
    
    if (amount <= 0) return;
    
    try {
        const formData = new FormData();
        formData.append('amount', amount);
        formData.append('from', fromCurrency);
        formData.append('to', toCurrency);
        formData.append('is_rent', slide.deal_type === 'Аренда');
        formData.append('csrf_token', csrfToken);
        
        const response = await fetch('/api.php?action=convert_currency', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            slide.price_value = Math.round(data.converted);
            refreshSlide(slideIndex);
        }
    } catch (error) {
        console.error('Currency conversion error:', error);
    }
}

// Загрузка курсов валют
async function loadCurrencyRates() {
    try {
        const response = await fetch('/api.php?action=get_currency_rates');
        const data = await response.json();
        
        if (data.success) {
            currencyRates = data.rates;
            currencySymbols = data.symbols;
            
            slides.forEach((slide, index) => {
                if (slide.type === 'cover') {
                    updateCurrencyConversions(index);
                }
            });
        }
    } catch (error) {
        console.error('Failed to load currency rates:', error);
    }
}

// Обновление конвертации валют
async function updateCurrencyConversions(slideIndex) {
    const slide = slides[slideIndex];
    const amount = slide.price_value || 0;
    const baseCurrency = slide.currency || 'RUB';
    const isRent = slide.deal_type === 'Аренда';
    
    if (!currencyRates || amount <= 0) return;
    
    const converterElement = document.getElementById(`currency-converter-${slideIndex}`);
    if (!converterElement) return;
    
    const pricesElement = converterElement.querySelector('.converted-prices');
    if (!pricesElement) return;
    
    // Проверяем наличие checkbox (только в десктопной версии)
    const showAllCheckbox = document.getElementById('showAllCurrencies');
    const showAllCurrencies = showAllCheckbox ? showAllCheckbox.checked : false;
    
    let html = '<span class="converted-label">≈ </span>';
    let addedCount = 0;
    
    for (const [currency, rate] of Object.entries(currencyRates)) {
        if (currency === baseCurrency) continue;
        if (!showAllCurrencies && addedCount >= 3) continue;
        
        const converted = amount * (currencyRates[baseCurrency] / rate);
        const formatted = formatNumber(Math.round(converted));
        const symbol = currencySymbols[currency] || currency;
        
        html += `<span class="converted-item">${formatted} ${symbol}${isRent ? ' / мес.' : ''}</span>`;
        addedCount++;
        
        if ((showAllCurrencies || addedCount < 3) && addedCount < Object.keys(currencyRates).length - 1) {
            html += '<span class="converted-separator"> • </span>';
        }
    }
    
    pricesElement.innerHTML = html;
}

// Обновление курсов валют
async function refreshCurrencyRates() {
    try {
        const response = await fetch('/api.php?action=get_currency_rates&force=true');
        const data = await response.json();
        
        if (data.success) {
            currencyRates = data.rates;
            showNotification('Курсы валют обновлены', 'success');
            
            slides.forEach((slide, index) => {
                if (slide.type === 'cover') {
                    updateCurrencyConversions(index);
                }
            });
        }
    } catch (error) {
        console.error('Failed to refresh currency rates:', error);
        showNotification('Ошибка обновления курсов', 'error');
    }
}