<?php

class CurrencyConverter {
    private static $rates = [];
    private static $lastUpdate = null;
    private const CACHE_FILE = __DIR__ . '/../cache/currency_rates.json';
    private const CACHE_DURATION = 3600;
    private const DEFAULT_RATES = [
        'RUB' => 1.0,
        'USD' => 90.0,
        'EUR' => 100.0,
        'CNY' => 12.0,
        'KZT' => 0.067  // ПРАВИЛЬНО: 1 KZT = 0.067 RUB
    ];
    
    public static function getRates($forceUpdate = false) {
        if (!empty(self::$rates) && !$forceUpdate) {
            return self::$rates;
        }
        
        if (file_exists(self::CACHE_FILE)) {
            $cached = json_decode(file_get_contents(self::CACHE_FILE), true);
            if ($cached && time() - $cached['timestamp'] < self::CACHE_DURATION) {
                self::$rates = $cached['rates'];
                self::$lastUpdate = $cached['timestamp'];
                return self::$rates;
            }
        }
        
        $rates = self::fetchRates();
        
        if (empty($rates)) {
            $rates = self::DEFAULT_RATES;
        }
        
        if (isset($rates['KZT']) && $rates['KZT'] > 1) {
            $rates['KZT'] = 0.15;
        } elseif (!isset($rates['KZT'])) {
            $rates['KZT'] = 0.15;
        }
        
        self::$rates = $rates;
        
        $cacheDir = dirname(self::CACHE_FILE);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        file_put_contents(self::CACHE_FILE, json_encode([
            'rates' => $rates,
            'timestamp' => time()
        ]));
        
        return $rates;
    }
    
    private static function fetchRates() {
        $sources = [
            'cbr' => 'https://www.cbr-xml-daily.ru/daily_json.js',
            'exchangerate' => 'https://api.exchangerate-api.com/v4/latest/RUB'
        ];
        
        foreach ($sources as $source => $url) {
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200 && $response) {
                    $data = json_decode($response, true);
                    
                    if ($source === 'cbr' && isset($data['Valute'])) {
                        return [
                            'RUB' => 1.0,
                            'USD' => $data['Valute']['USD']['Value'] ?? 90.0,
                            'EUR' => $data['Valute']['EUR']['Value'] ?? 100.0,
                            'CNY' => $data['Valute']['CNY']['Value'] ?? 12.0,
                            'KZT' => ($data['Valute']['KZT']['Value'] ?? 15.0) / 100
                        ];
                    } elseif ($source === 'exchangerate' && isset($data['rates'])) {
                        return [
                            'RUB' => 1.0,
                            'USD' => $data['rates']['USD'] ?? 90.0,
                            'EUR' => $data['rates']['EUR'] ?? 100.0,
                            'CNY' => $data['rates']['CNY'] ?? 12.0,
                            'KZT' => $data['rates']['KZT'] ?? 0.15
                        ];
                    }
                }
            } catch (Exception $e) {
                error_log("Currency fetch error: " . $e->getMessage());
                continue;
            }
        }
        
        return [];
    }
    
    public static function convert($amount, $fromCurrency, $toCurrency) {
        $rates = self::getRates();
        
        if (!isset($rates[$fromCurrency]) || !isset($rates[$toCurrency])) {
            return $amount;
        }
        
        $amountInRub = $amount * $rates[$fromCurrency];
        return $amountInRub / $rates[$toCurrency];
    }
    
    public static function getSymbol($currency) {
        $symbols = [
            'RUB' => '₽',
            'USD' => '$',
            'EUR' => '€',
            'CNY' => '¥',
            'KZT' => '₸'
        ];
        
        return $symbols[$currency] ?? $currency;
    }
    
    public static function formatPrice($amount, $currency = 'RUB', $isRent = false) {
        $symbol = self::getSymbol($currency);
        $formatted = number_format($amount, 0, '.', ' ');
        $result = $formatted . ' ' . $symbol;
        
        if ($isRent) {
            $result .= ' / месяц';
        }
        
        return $result;
    }
    
    public static function getLastUpdate() {
        return self::$lastUpdate;
    }
    
    public static function getAllCurrencies() {
        return [
            'RUB' => ['symbol' => '₽', 'name' => 'Российский рубль'],
            'USD' => ['symbol' => '$', 'name' => 'Доллар США'],
            'EUR' => ['symbol' => '€', 'name' => 'Евро'],
            'CNY' => ['symbol' => '¥', 'name' => 'Китайский юань'],
            'KZT' => ['symbol' => '₸', 'name' => 'Казахстанский тенге']
        ];
    }
}