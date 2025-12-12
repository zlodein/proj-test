<?php
function handleCurrencyRequest($action) {
    switch ($action) {
        case 'get_currency_rates':
            requireAuth();
            
            $forceUpdate = isset($_GET['force']) && $_GET['force'] == 'true';
            $rates = CurrencyConverter::getRates($forceUpdate);
            $lastUpdate = CurrencyConverter::getLastUpdate();
            
            jsonResponse([
                'success' => true,
                'rates' => $rates,
                'last_update' => $lastUpdate,
                'last_update_human' => $lastUpdate ? date('d.m.Y H:i', $lastUpdate) : null,
                'symbols' => [
                    'RUB' => '₽',
                    'USD' => '$',
                    'EUR' => '€',
                    'CNY' => '¥',
                    'KZT' => '₸'
                ]
            ]);
            break;
            
        case 'convert_currency':
            requireAuth();
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['error' => 'Метод не разрешён'], 405);
            }
            
            $amount = floatval($_POST['amount'] ?? 0);
            $from = $_POST['from'] ?? 'RUB';
            $to = $_POST['to'] ?? 'USD';
            $isRent = filter_var($_POST['is_rent'] ?? false, FILTER_VALIDATE_BOOLEAN);
            
            if ($amount <= 0) {
                jsonResponse(['error' => 'Неверная сумма'], 400);
            }
            
            $converted = CurrencyConverter::convert($amount, $from, $to);
            $formatted = CurrencyConverter::formatPrice($converted, $to, $isRent);
            
            jsonResponse([
                'success' => true,
                'converted' => $converted,
                'formatted' => $formatted,
                'original_formatted' => CurrencyConverter::formatPrice($amount, $from, $isRent)
            ]);
            break;
            
        default:
            jsonResponse(['error' => 'Неизвестное действие валют: ' . $action], 400);
    }
}
?>